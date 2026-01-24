<?php
include('../handlers/require_login.php');
require '../db.php';
require_once __DIR__ . '/generate_matches_function.php';

$tournament_id = filter_input(INPUT_POST, 'tournament_id', FILTER_VALIDATE_INT);
$user_id = (int)$_SESSION['user_id'];

if (!$tournament_id) {
    http_response_code(400);
    exit('Invalid tournament id');
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, creator_user_id, status, spots_taken
     FROM Tournament
     WHERE id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $tournament_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$t = mysqli_fetch_assoc($res);

if (!$t || (int)$t['creator_user_id'] !== $user_id) {
    http_response_code(403);
    exit('Forbidden');
}

if ($t['status'] !== 'upcoming') {
    $_SESSION['error'] = 'Турнирът вече е стартиран/завършен!';
    header("Location: ../tournament.php?id=$tournament_id");
    exit;
}

if ((int)$t['spots_taken'] < 2) {
    $_SESSION['error'] = 'Нужни са поне 2 участника/отбора!';
    header("Location: ../tournament.php?id=$tournament_id");
    exit;
}

mysqli_begin_transaction($conn);
try {
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE Tournament
         SET status='ongoing', start_datetime = NOW()
         WHERE id = ? AND status='upcoming'"
    );
    mysqli_stmt_bind_param($stmt, "i", $tournament_id);
    mysqli_stmt_execute($stmt);

    generateMatches($conn, $tournament_id);

    mysqli_commit($conn);
    $_SESSION['success'] = 'Турнирът беше стартиран!';
} catch (Throwable $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = 'Грешка при стартиране: ' . $e->getMessage(); // временно за дебъг
}

header("Location: ../tournament.php?id=$tournament_id");
exit;
