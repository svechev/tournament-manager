<?php

function joinTournament(
    mysqli $conn,
    int $user_id,
    int $tournament_id,
    string $team_name,
): void {
    try {
        // checks for inserting a new/old team in a team tournament
        $stmt = mysqli_prepare(
            $conn,
            "SELECT t.is_team_based FROM Tournament t WHERE t.id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $tournament_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        $is_team_based = (bool)$row['is_team_based'];

        // 2. Determine $old_team
        $old_team = false;

        if ($is_team_based) {
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

            $old_team = mysqli_num_rows($res) > 0;
        }


        // insert
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO Participates (user_id, tournament_id, team_name)
             VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iis", $user_id, $tournament_id, $team_name);
        
        try {
            mysqli_stmt_execute($stmt);
        } 
        catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                throw new RuntimeException('ALREADY_JOINED');
            }
            throw $e;
        }

        // update tournament
        if (!$old_team) {
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

    } catch (Throwable $e) {
        throw $e;
    }
}