<?php
require_once __DIR__ . '/ensure_tournament_started.php';

function ensureDueTournamentsStarted(mysqli $conn): void
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id
         FROM Tournament
         WHERE status='upcoming'
           AND start_datetime <= NOW()
           AND spots_taken >= 2"
    );
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res)) {
        ensureTournamentStarted($conn, (int)$row['id']);
    }
}
