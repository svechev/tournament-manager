<?php

function generateMatches(mysqli $conn, int $tournament_id): void
{
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM Matches WHERE tournament_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $tournament_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    if ((int)$row['c'] > 0) return;

    $stmt = mysqli_prepare(
        $conn,
        "SELECT start_datetime, is_team_based
         FROM Tournament
         WHERE id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $tournament_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $t = mysqli_fetch_assoc($res);
    if (!$t) return;

    $start_dt = $t['start_datetime'];
    $is_team_based = (bool)$t['is_team_based'];

    if ($is_team_based) {
        $sql = "
            SELECT p.team_name AS name, MIN(p.joined_at) AS joined_at
            FROM Participates p
            WHERE p.tournament_id = ?
            GROUP BY p.team_name
            ORDER BY joined_at ASC
        ";
    } else {
        $sql = "
            SELECT p.team_name AS name, p.joined_at
            FROM Participates p
            WHERE p.tournament_id = ?
            ORDER BY p.joined_at ASC
        ";
    }

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $tournament_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $players = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $name = trim((string)$r['name']);
        if ($name !== '') $players[] = $name;
    }

    $n = count($players);
    if ($n < 2) return;

    $bracketSize = 1;
    while ($bracketSize < $n) $bracketSize *= 2;

    $rounds = 0;
    for ($tmp=$bracketSize; $tmp>1; $tmp=(int)($tmp/2)) $rounds++;

    $matchIdsByRound = [];

    $insertMatch = function(int $round, ?int $next_match_id) use ($conn, $tournament_id, $start_dt): int {
        if ($next_match_id === null) {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO Matches (tournament_id, match_date, current_round, next_match_id)
                 VALUES (?, ?, ?, NULL)"
            );
            mysqli_stmt_bind_param($stmt, "isi", $tournament_id, $start_dt, $round);
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO Matches (tournament_id, match_date, current_round, next_match_id)
                 VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "isii", $tournament_id, $start_dt, $round, $next_match_id);
        }
        mysqli_stmt_execute($stmt);
        return (int)mysqli_insert_id($conn);
    };

    $finalId = $insertMatch(1, null);
    $matchIdsByRound[1] = [$finalId];

    for ($r=2; $r<=$rounds; $r++) {
        $numMatches = 1 << ($r-1);
        $matchIdsByRound[$r] = [];
        for ($i=0; $i<$numMatches; $i++) {
            $parentId = $matchIdsByRound[$r-1][intdiv($i,2)];
            $id = $insertMatch($r, $parentId);
            $matchIdsByRound[$r][$i] = $id;
        }
    }

    $bottomRound = $rounds;
    $numBottomMatches = 1 << ($bottomRound-1);
    $slotCount = $numBottomMatches * 2;

    while (count($players) < $slotCount) $players[] = null;

    $stmtSeed = mysqli_prepare(
        $conn,
        "UPDATE Matches SET side1_nickname = ?, side2_nickname = ? WHERE match_id = ?"
    );

    $p = 0;
    for ($i=0; $i<$numBottomMatches; $i++) {
        $s1 = $players[$p++]; $s2 = $players[$p++];
        $mid = $matchIdsByRound[$bottomRound][$i];

        mysqli_stmt_bind_param($stmtSeed, "ssi", $s1, $s2, $mid);
        mysqli_stmt_execute($stmtSeed);
    }
}
