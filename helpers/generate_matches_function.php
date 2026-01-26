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
            ORDER BY joined_at ASC
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
    for ($tmp = $bracketSize; $tmp > 1; $tmp = (int)($tmp / 2)) $rounds++;

    $matchIdsByRound = [];

    $insertMatch = function(int $phase, ?int $next_match_id) use ($conn, $tournament_id, $start_dt): int {
        if ($next_match_id === null) {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO Matches (tournament_id, match_date, current_round, next_match_id)
                 VALUES (?, ?, ?, NULL)"
            );
            mysqli_stmt_bind_param($stmt, "isi", $tournament_id, $start_dt, $phase);
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO Matches (tournament_id, match_date, current_round, next_match_id)
                 VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "isii", $tournament_id, $start_dt, $phase, $next_match_id);
        }
        mysqli_stmt_execute($stmt);
        return (int)mysqli_insert_id($conn);
    };

    $finalId = $insertMatch(1, null);
    $matchIdsByRound[1] = [$finalId];

    for ($r = 2; $r <= $rounds; $r++) {
        $phase = 1 << ($r - 1); // 2,4,8,16...
        $numMatches = $phase;

        $matchIdsByRound[$r] = [];
        for ($i = 0; $i < $numMatches; $i++) {
            $parentId = $matchIdsByRound[$r - 1][intdiv($i, 2)];
            $id = $insertMatch($phase, $parentId);
            $matchIdsByRound[$r][$i] = $id;
        }
    }

    $bottomRound = $rounds;
    $numBottomMatches = 1 << ($bottomRound - 1);
    $slotCount = $numBottomMatches * 2;

    while (count($players) < $slotCount) $players[] = null;

    $positions = [1, 2];
    for ($size = 4; $size <= $bracketSize; $size *= 2) {
        $new = [];
        foreach ($positions as $p) {
            $new[] = $p;
            $new[] = $size + 1 - $p;
        }
        $positions = $new;
    }

    $slots = array_fill(0, $slotCount, null);
    for ($i = 0; $i < $slotCount; $i++) {
        $seedPos = $positions[$i] - 1;
        $slots[$seedPos] = $players[$i];
    }

    $stmtSeed = mysqli_prepare(
        $conn,
        "UPDATE Matches SET side1_nickname = ?, side2_nickname = ? WHERE match_id = ?"
    );

    for ($i = 0; $i < $numBottomMatches; $i++) {
        $s1 = $slots[$i * 2];
        $s2 = $slots[$i * 2 + 1];
        $mid = $matchIdsByRound[$bottomRound][$i];

        mysqli_stmt_bind_param($stmtSeed, "ssi", $s1, $s2, $mid);
        mysqli_stmt_execute($stmtSeed);
    }

    $pushToNext = function(int $next_match_id, string $winnerName) use ($conn): void {
        $stmt = mysqli_prepare($conn,
            "UPDATE Matches
             SET side1_nickname = ?
             WHERE match_id = ? AND side1_nickname IS NULL"
        );
        mysqli_stmt_bind_param($stmt, "si", $winnerName, $next_match_id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) === 1) return;

        $stmt = mysqli_prepare($conn,
            "UPDATE Matches
             SET side2_nickname = ?
             WHERE match_id = ? AND side2_nickname IS NULL"
        );
        mysqli_stmt_bind_param($stmt, "si", $winnerName, $next_match_id);
        mysqli_stmt_execute($stmt);
    };

    $stmtSetWinnerBye = mysqli_prepare(
        $conn,
        "UPDATE Matches
         SET winner = ?, score = 'BYE'
         WHERE match_id = ? AND winner IS NULL"
    );

    $changed = true;
    while ($changed) {
        $changed = false;

        $stmt = mysqli_prepare(
            $conn,
            "SELECT match_id, side1_nickname, side2_nickname, next_match_id, winner
             FROM Matches
             WHERE tournament_id = ?
               AND winner IS NULL
               AND (
                    (side1_nickname IS NOT NULL AND side2_nickname IS NULL) OR
                    (side1_nickname IS NULL AND side2_nickname IS NOT NULL)
               )"
        );
        mysqli_stmt_bind_param($stmt, "i", $tournament_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        while ($m = mysqli_fetch_assoc($res)) {
            $mid = (int)$m['match_id'];

            $s1 = $m['side1_nickname'];
            $s2 = $m['side2_nickname'];
            $winnerName = ($s1 !== null) ? $s1 : $s2;

            $stmtChild = mysqli_prepare(
                $conn,
                "SELECT COUNT(*) AS c
                 FROM Matches
                 WHERE tournament_id = ?
                   AND next_match_id = ?
                   AND winner IS NULL
                   AND (side1_nickname IS NOT NULL OR side2_nickname IS NOT NULL)"
            );
            mysqli_stmt_bind_param($stmtChild, "ii", $tournament_id, $mid);
            mysqli_stmt_execute($stmtChild);
            $r2 = mysqli_stmt_get_result($stmtChild);
            $cnt = (int)mysqli_fetch_assoc($r2)['c'];

            if ($cnt > 0) {
                continue;
            }

            mysqli_stmt_bind_param($stmtSetWinnerBye, "si", $winnerName, $mid);
            mysqli_stmt_execute($stmtSetWinnerBye);

            if (!empty($m['next_match_id'])) {
                $pushToNext((int)$m['next_match_id'], $winnerName);
            }

            $changed = true;
        }
    }
}
