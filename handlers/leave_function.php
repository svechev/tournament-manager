<?php

function leaveTournament(
    mysqli $conn,
    int $user_id,
    int $tournament_id,
): void {
    try {
        // checks for deleting an old team in a team tournament
        $stmt = mysqli_prepare(
            $conn,
            "SELECT t.is_team_based FROM Tournament t WHERE t.id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $tournament_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        if (!$row) {
            throw new RuntimeException('INVALID_TOURNAMENT');
        }

        $is_team_based = (bool)$row['is_team_based'];
 
        // select before delete (also gets team name)
        $stmt = mysqli_prepare(
        $conn,
        "SELECT p.team_name
                FROM Participates p
                WHERE p.user_id = ? AND p.tournament_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $tournament_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        if (!$row) {
            throw new RuntimeException('NOT_IN_TOURNAMENT');
        }

        $team_name = $row['team_name'];

        $repeating_team = false;

        // the check if team name exists twice
        if ($is_team_based) {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT 2
                FROM Participates
                WHERE tournament_id = ? AND team_name = ?
                LIMIT 2"
            );
            mysqli_stmt_bind_param($stmt, "is", $tournament_id, $team_name);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $repeating_team = mysqli_num_rows($res) > 1;
        }

        
        // delete
        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM Participates
                    WHERE user_id = ? AND tournament_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $tournament_id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) !== 1) { // didn't delete anyone?
            throw new RuntimeException('NOT_IN_TOURNAMENT');
        }

        // update tournament
        if (!$repeating_team) {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE Tournament
                SET spots_taken = spots_taken - 1
                WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, "i", $tournament_id);
            mysqli_stmt_execute($stmt);
        }

    } catch (Throwable $e) {
        throw $e;
    }
}