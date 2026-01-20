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
                    'capacity' => $capacity,
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

if (strlen($category) > 63) {
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


$csv = $_FILES['participants_csv'] ?? null;

// csv check
if ($csv && $csv['error'] === UPLOAD_ERR_OK) {

    if ($csv['size'] > 2 * 1024 * 1024) {
        $_SESSION['error'] = 'CSV файлът е твърде голям!';
        header('Location: ../create_tournament.php');
        exit;
    }

    $ext = strtolower(pathinfo($csv['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        $_SESSION['error'] = 'Невалиден файл!';
        header('Location: ../create_tournament.php');
        exit;
    }
}



require '../db.php';
require 'join_function.php';

$team = ($is_team_based === 'team') ? 1 : 0;
$user = $_SESSION['user_id'];
$start_db = $start->format('Y-m-d H:i:s');
$end_db   = $end->format('Y-m-d H:i:s');

try {
    mysqli_begin_transaction($conn);
    // create 
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO Tournament (name, description, category, start_datetime, end_datetime, capacity, is_team_based, creator_user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "sssssiii",
                                $name, $description, $category, $start_db, $end_db, $capacity, $team, $user);
                    
    try {
        mysqli_stmt_execute($stmt); 
        $tournament_id = mysqli_insert_id($conn);  
    }
    catch(mysqli_sql_exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = 'Грешка в базата данни!';
    
        header('Location: ../create_tournament.php');
        exit;
    }

    // insert
    if ($csv && $csv['error'] === UPLOAD_ERR_OK) {
        
        $fd = fopen($csv['tmp_name'], 'r');
        error_log('CSV OPENED');

        $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO Participates (user_id, tournament_id, team_name)
                    VALUES (?, ?, ?)"
        );

        while (($row = fgetcsv($fd, 150, ',')) !== false) {
            [$email_or_username, $team_name] = $row;

            $email_or_username = trim($email_or_username);
            $team_name = trim($team_name);   

            if (empty($email_or_username) || empty($team_name)) {
                continue; // invalid row
            }

            $stmt = mysqli_prepare(
                $conn,
                "SELECT u.id 
                        FROM User u 
                        WHERE u.username = ? OR u.email = ?"
            );
            mysqli_stmt_bind_param($stmt, "ss", 
                            $email_or_username, $email_or_username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                $user_id = $user['id'];
     
                try {
                    joinTournament($conn, $user_id, $tournament_id, $team_name);  
                }
                catch(Throwable $e) {
                    mysqli_rollback($conn);
                    $_SESSION['error'] = 'Грешка при вмъкването на потребители!';
                    $_SESSION['error'] = 'CSV ERROR: ' . $e->getMessage();

                    header('Location: ../create_tournament.php');
                    exit;
                }
            }
        }
        fclose($fd);
    }

    mysqli_stmt_close($stmt);
    mysqli_commit($conn);

    // success
    header('Location: ../home.php');
    exit;
} 
catch (Throwable $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = 'Неочаквана грешка!';
    header('Location: ../create_tournament.php');
    exit;
}
