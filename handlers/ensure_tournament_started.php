<?php
require_once __DIR__ . '/generate_matches_function.php';

function ensureTournamentStarted(mysqli $conn, int $tournament_id): void
{
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE Tournament
             SET status='ongoing'
             WHERE id = ?
               AND status='upcoming'
               AND spots_taken >= 2
               AND start_datetime <= NOW()"
        );
        mysqli_stmt_bind_param($stmt, "i", $tournament_id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) !== 1) {
            mysqli_commit($conn);
            return;
        }

        generateMatches($conn, $tournament_id);

        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
    }
}
