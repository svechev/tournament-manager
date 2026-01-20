<?php
include('../handlers/require_login.php');
require '../db.php';

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


mysqli_begin_transaction($conn);

try {

    // join
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO Participates (user_id, tournament_id, team_name)
         VALUES (?, ?, ?)"
    );

    mysqli_stmt_bind_param($stmt, "iis",
        $user_id,
        $tournament_id,
        $team_name
    );

    try {
        mysqli_stmt_execute($stmt);
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($conn);

        if ($e->getCode() === 1062) {
            $_SESSION['error'] = 'Вече сте в турнира!';
        } else {
            $_SESSION['error'] = 'Грешка при присъединяването!';
        }

        header('Location: ../tournament.php?id=' . $tournament_id);
        exit;
    }

    // update tournament
    if ($new_team != 'join') {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE Tournament
            SET spots_taken = spots_taken + 1
            WHERE id = ? AND spots_taken < capacity"
        );

        mysqli_stmt_bind_param($stmt, "i", $tournament_id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) !== 1) {
            mysqli_rollback($conn);
            $_SESSION['error'] = 'Турнирът вече е запълнен!';
            header('Location: ../tournament.php?id=' . $tournament_id);
            exit;
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_commit($conn);

    // success
    $_SESSION['success'] = 'Успешно присъединяване!';
    header('Location: ../tournament.php?id=' . $tournament_id);
    exit;

} catch (Throwable $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = 'Неочаквана грешка!';
    header('Location: ../tournament.php?id=' . $tournament_id);
    exit;
}