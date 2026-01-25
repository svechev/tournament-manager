<?php
include('../helpers/require_login.php');
require '../config/db.php';

$user_id = $_SESSION['user_id'];
$tournament_id = filter_input(INPUT_POST, 'tournament_id', FILTER_VALIDATE_INT);

require '../helpers/leave_function.php';

mysqli_begin_transaction($conn);

try {
    leaveTournament(
        $conn,
        $user_id,
        $tournament_id,
    );

    mysqli_commit($conn);
    $_SESSION['success'] = 'Напуснахте турнира!';
} 
catch (Throwable $e) {
    mysqli_rollback($conn);
    switch ($e->getMessage()) {
        case 'INVALID_TOURNAMENT':
            $_SESSION['error'] = 'Невалиден турнир!';
            break;
        case 'NOT_IN_TOURNAMENT':
            $_SESSION['error'] = 'Не сте в турнира!';
            break;
        default:
            $_SESSION['error'] = 'Грешка при напускането!';
            $_SESSION['error'] = get_class($e) . ': ' . $e->getMessage();
    }
}

header("Location: ../tournament.php?id=$tournament_id");
exit;