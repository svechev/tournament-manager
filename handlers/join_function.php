<?php

function joinTournament(
    mysqli $conn,
    int $user_id,
    int $tournament_id,
    string $team_name,
): void {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT status, start_datetime, spots_taken, capacity, is_team_based
         FROM Tournament
         WHERE id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $tournament_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $t = mysqli_fetch_assoc($res);

    if (!$t) {
        throw new RuntimeException('TOURNAMENT_NOT_FOUND');
    }

    if ($t['status'] !== 'upcoming') {
        throw new RuntimeException('TOURNAMENT_LOCKED');
    }

    $stmt = mysqli_prepare($conn, "SELECT (NOW() >= ?) AS locked");
    mysqli_stmt_bind_param($stmt, "s", $t['start_datetime']);
    mysqli_stmt_execute($stmt);
    $r2 = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($r2);
    if ((bool)$row['locked']) {
        throw new RuntimeException('TOURNAMENT_LOCKED');
    }


    if ((int)$t['spots_taken'] >= (int)$t['capacity']) {
        throw new RuntimeException('TOURNAMENT_FULL');
    }

    $is_team_based = (bool)$t['is_team_based'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT 1
         FROM Participates p
         WHERE p.tournament_id = ? AND p.team_name = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "is", $tournament_id, $team_name);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $name_exists = mysqli_num_rows($res) > 0;

    if ($name_exists && !$is_team_based) {
        throw new RuntimeException('NAME_EXISTS');
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO Participates (user_id, tournament_id, team_name)
         VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "iis", $user_id, $tournament_id, $team_name);

    try {
        mysqli_stmt_execute($stmt);
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            throw new RuntimeException('ALREADY_JOINED');
        }
        throw $e;
    }

    if (!$name_exists) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE Tournament
             SET spots_taken = spots_taken + 1
             WHERE id = ? AND spots_taken < capacity"
        );
        mysqli_stmt_bind_param($stmt, "i", $tournament_id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) !== 1) {
            throw new RuntimeException('TOURNAMENT_FULL');
        }
    }
}
