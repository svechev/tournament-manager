<?php
include('../handlers/require_login.php');


$name = filter_input(INPUT_POST, 'name', 
                        FILTER_SANITIZE_SPECIAL_CHARS);
$description = filter_input(INPUT_POST, 'description', 
                        FILTER_SANITIZE_SPECIAL_CHARS);   
$category = filter_input(INPUT_POST, 'category', 
                        FILTER_SANITIZE_SPECIAL_CHARS);                   

$start_datetime = trim($_POST['start-datetime'] ?? '');
$end_datetime = trim($_POST['end-datetime'] ?? '');
$capacity = filter_input(INPUT_POST, 'capacity', 
                        FILTER_VALIDATE_INT); 

$is_team_based = $_POST['is-team-based'] ?? null;

$_SESSION['prev'] = ['name' => $name,
                    'description' => $description,
                    'category' => $category,
                    'start-datetime' => $start_datetime,
                    'end-datetime' => $end_datetime,
                    'is_team_based' => $is_team_based,];

if ($name === '' || $description === '' || $category === '' 
    || $is_team_based === null || $start_datetime === '' || $end_datetime === '') {
    $_SESSION['error'] = 'Липсващи полета!';
    header('Location: ../create_tournament.php');
    exit;
}


if (strlen($name) > 63) {
    $_SESSION['error'] = 'Името на турнира е твърде дълго!';
    header('Location: ../create_tournament.php');
    exit;
}

if (strlen($description) > 255) {
    $_SESSION['error'] = 'Прекалено дълго описание!';
    header('Location: ../create_tournament.php');
    exit;
}

if (strlen($category) > 32) {
    $_SESSION['error'] = 'Името на категорията е твърде дълго!';
    header('Location: ../create_tournament.php');
    exit;
}

if ($capacity === false || $capacity < 2) {
    $_SESSION['error'] = 'В турнира трябва да има поне 2 участника!';
    header('Location: ../create_tournament.php');
    exit;
}

$start = DateTime::createFromFormat('Y-m-d\TH:i', $start_datetime);
$end = DateTime::createFromFormat('Y-m-d\TH:i', $end_datetime);

if (!$start || !$end) {
    $_SESSION['error'] = 'Невалиден формат на дата и час!';
    header('Location: ../create_tournament.php');
    exit;
}

if ($end <= $start) {
    $_SESSION['error'] = 'Неправилно зададено време на начало и край!';
    header('Location: ../create_tournament.php');
    exit;
}

require '../db.php';

$team = ($is_team_based === 'team') ? 1 : 0;
$user = $_SESSION['user_id'];
$start_db = $start->format('Y-m-d H:i:s');
$end_db   = $end->format('Y-m-d H:i:s');

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO Tournament (name, description, category, start_datetime, end_datetime, capacity, is_team_based, creator_user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, "sssssiii",
                            $name, $description, $category, $start_db, $end_db, $capacity, $team, $user);
                
try {
    mysqli_stmt_execute($stmt);   
}
catch(mysqli_sql_exception $e) {
    $_SESSION['error'] = 'Грешка в базата данни!';
 
    header('Location: ../create_tournament.php');
    exit;
}

// success
header('Location: ../home.php');
exit;