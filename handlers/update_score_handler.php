<?php
// ВАЖНО! актуализира само настоящия мач, не прехвърля нищо към next_match още
include('../handlers/require_login.php');
require '../db.php';

$tournament_id = filter_input(INPUT_POST, 'tournament_id', FILTER_VALIDATE_INT);
$match_id = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
$score1 = filter_input(INPUT_POST, 'player1_score', FILTER_VALIDATE_INT);
$score2 = filter_input(INPUT_POST, 'player2_score', FILTER_VALIDATE_INT);

// проверки
if ($score1 === null || $score1 === false || $score2 === null || $score2 === false 
    || $score1 < 0 || $score2 < 0) {
    $_SESSION['update_score_error'] = 'Неправилно зададен резултат!';
    header("Location: ../tournament.php?id=$tournament_id");
    exit;
}

if ($score1 === $score2) {
    $_SESSION['update_score_error'] = 'Избери победител!';
    header("Location: ../tournament.php?id=$tournament_id");
    exit;
}


$stmt = mysqli_prepare(
    $conn,
    "SELECT *
        FROM Matches
        WHERE match_id = ?
        LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $match_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$match = mysqli_fetch_assoc($result);


$winner = null;
if ($score1 > $score2) {
    $winner = $match['side1_nickname'];
}
else {
    $winner = $match['side2_nickname'];
}
$score = $score1 . '-' . $score2;


mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare(
    $conn,
    "UPDATE Matches
        SET winner = ?, score = ?
        WHERE match_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ssi", $winner, $score, $match_id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) !== 1) {
        $_SESSION['update_score_error'] = 'Неочаквана грешка!';
        header("Location: ../tournament.php?id=$tournament_id");
        exit;
    }

    //success
    mysqli_commit($conn);
} 
catch (Throwable $e) {
    mysqli_rollback($conn);
    $_SESSION['update_score_error'] = 'Неочаквана грешка!';
}

header("Location: ../tournament.php?id=$tournament_id");
exit;
