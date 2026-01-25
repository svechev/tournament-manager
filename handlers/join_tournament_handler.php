<?php
include('../handlers/require_login.php');
require '../config/db.php';

$user_id = $_SESSION['user_id'];
$tournament_id = filter_input(INPUT_POST, 'tournament_id', FILTER_VALIDATE_INT);
$new_team = $_POST['new_team'] ?? null;

if ($new_team == 'create') {
    $team_name = trim(filter_input(INPUT_POST, 'new_team_name', FILTER_SANITIZE_SPECIAL_CHARS));
}
else {
    $team_name = trim(filter_input(INPUT_POST, 'team_name', FILTER_SANITIZE_SPECIAL_CHARS));
}
// team name за един човек си е реално неговия nickname за турнира



// проверки
if ($team_name === '') {
    $_SESSION['error'] = 'Липсва отбор!';
    header('Location: ../tournament.php?id=' . $tournament_id);
    exit;
}

if (strlen($team_name) > 63) {
    $_SESSION['error'] = 'Прекалено дълго име!';
    header('Location: ../tournament.php?id=' . $tournament_id);
    exit;
}
if ($new_team == 'create') {
    // казали сме 'нов отбор' но сме подали вече съществуващ 
    $stmt = mysqli_prepare(
        $conn,
        "SELECT DISTINCT p.team_name
        FROM Participates p
        WHERE p.tournament_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $tournament_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['team_name'] == $team_name) {
            $_SESSION['error'] = 'Този отбор вече съществува!';
            header('Location: ../tournament.php?id=' . $tournament_id);
            exit;
        }
    }
}

require 'join_function.php';

mysqli_begin_transaction($conn);

try {
    joinTournament(
        $conn,
        $user_id,
        $tournament_id,
        $team_name,
    );

    mysqli_commit($conn);
    $_SESSION['success'] = 'Успешно присъединяване!';
} 
catch (Throwable $e) {
    mysqli_rollback($conn);
    switch ($e->getMessage()) {
        case 'NAME_EXISTS':
            $_SESSION['error'] = 'Вече има потребител с това име в турнира!';
            break;
        case 'ALREADY_JOINED':
            $_SESSION['error'] = 'Вече сте в турнира!';
            break;
        case 'TOURNAMENT_FULL':
            $_SESSION['error'] = 'Турнирът вече е запълнен!';
            break;
        case 'TOURNAMENT_LOCKED':
            $_SESSION['error'] = 'Турнирът е заключен и не приема участници!';
            break;
        case 'TOURNAMENT_NOT_FOUND':
            $_SESSION['error'] = 'Турнирът не е намерен!';
            break;
        default:
            $_SESSION['error'] = 'Грешка при присъединяването!';
    }
}

header("Location: ../tournament.php?id=$tournament_id");
exit;
