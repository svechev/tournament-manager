<?php
require_once __DIR__ . '/../../config/config.php';

require_once ROOT_PATH . 'helpers/require_login.php';
require_once ROOT_PATH . 'config/db.php';

$tournament_id = filter_input(INPUT_POST, 'tournament_id', FILTER_VALIDATE_INT);
$match_id = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
$score1 = filter_input(INPUT_POST, 'player1_score', FILTER_VALIDATE_INT);
$score2 = filter_input(INPUT_POST, 'player2_score', FILTER_VALIDATE_INT);

$current_round = filter_input(INPUT_POST, 'current_round', FILTER_VALIDATE_INT);

$_SESSION['prev'] = ['current_round' => $current_round];


// проверки
if ($score1 === null || $score1 === false || $score2 === null || $score2 === false 
    || $score1 < 0 || $score2 < 0) {
    $_SESSION['update_score_error'] = 'Неправилно зададен резултат!';
    header("Location: ../../tournament.php?id=$tournament_id");
    exit;
}

if ($score1 === $score2) {
    $_SESSION['update_score_error'] = 'Избери победител!';
    header("Location: ../../tournament.php?id=$tournament_id");
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
$loser = null;
if ($score1 > $score2) {
    $winner = $match['side1_nickname'];
    $loser = $match['side2_nickname'];
}
else {
    $winner = $match['side2_nickname'];
    $loser = $match['side1_nickname'];
}
$score = $score1 . '-' . $score2;


mysqli_begin_transaction($conn);

try {
    // update current match
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
        header("Location: ../../tournament.php?id=$tournament_id");
        exit;
    }

    // update loser's position
    $position = (int)$match['current_round'] + 1;
    $stmt = mysqli_prepare(
    $conn,
    "UPDATE Participates
        SET result_position = ?
        WHERE tournament_id = ? AND team_name = ?"
    );
    mysqli_stmt_bind_param($stmt, "iis", 
                            $position, $tournament_id, $loser);
    mysqli_stmt_execute($stmt);


    // update next match if not final
    if ($match['current_round'] != "1") { // not final
        $next_match_id = (int)$match['next_match_id'];
    
        // check which player in next match is NULL
        $stmt = mysqli_prepare(
        $conn,
        "SELECT side1_nickname, side2_nickname
            FROM Matches
            WHERE match_id = ?
            LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $next_match_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $next_match = mysqli_fetch_assoc($result);
        
        // where to insert the winner in the next match
        $field = "";
        if ($next_match['side1_nickname'] === null) {
            $field = 'side1_nickname';
        }
        else {
            $field = 'side2_nickname';
        }

        // insert
        $stmt = mysqli_prepare(
        $conn,
        "UPDATE Matches
                SET " . $field . " = ?
                WHERE match_id = ?"
            );
        mysqli_stmt_bind_param($stmt, "si", $winner, $next_match_id);
        mysqli_stmt_execute($stmt);
    }

    // if final 
    if ($match['current_round'] == "1") {
        // set winner's position
        $position = 1;
        $stmt = mysqli_prepare(
        $conn,
        "UPDATE Participates
            SET result_position = ?
            WHERE tournament_id = ? AND team_name = ?"
        );
        mysqli_stmt_bind_param($stmt, "iis", 
                                $position, $tournament_id, $winner);
        mysqli_stmt_execute($stmt);

        // set tournament to 'finished'
        $stmt = mysqli_prepare(
        $conn,
        "UPDATE Tournament
                SET status = 'finished', end_datetime = NOW() 
                WHERE id = ?"
            );
        mysqli_stmt_bind_param($stmt, "i", $tournament_id);
        mysqli_stmt_execute($stmt);
    }

    //success
    mysqli_commit($conn);
} 
catch (Throwable $e) {
    mysqli_rollback($conn);
    $_SESSION['update_score_error'] = 'Неочаквана грешка!';
}

header("Location: ../../tournament.php?id=$tournament_id");
exit;
