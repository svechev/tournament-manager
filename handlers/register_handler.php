<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = filter_input(INPUT_POST, 'username', 
                        FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', 
                        FILTER_SANITIZE_EMAIL);                      
$password = $_POST['password'];
$confirm_password = $_POST['confirm-password'];

$_SESSION['prev'] = ['username' => $username,
                        'email' => $email];

if ($username === '' || $email === '' || $password === '' || $confirm_password === '') {
    $_SESSION['error'] = 'Липсващи полета!';
    header('Location: ../register.php');
    exit;
}


if (strlen($username) > 63) {
    $_SESSION['error'] = 'Потребителското име е твърде дълго!';
    header('Location: ../register.php');
    exit;
}

if (strlen($email) > 255) {
    $_SESSION['error'] = 'Имейлът е твърде дълъг!';
    header('Location: ../register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Невалиден имейл!';
    header('Location: ../register.php');
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'Паролата трябва да е поне 6 символа!';
    header('Location: ../register.php');
    exit;
}

if ($password != $confirm_password) {
    $_SESSION['error'] = 'Паролите не съвпадат!';
    header('Location: ../register.php');
    exit;
}

require '../db.php';

$pass_hash = password_hash($password, PASSWORD_ARGON2ID);

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO User (username, email, password_hash)
            VALUES (?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, "sss",
                            $username, $email, $pass_hash);
                
try {
    mysqli_stmt_execute($stmt);   
}
catch(mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) { // "Duplicate entry '%s' for key %d" - от документацията
        $err_msg = $e->getMessage();

        if (strpos($err_msg, 'username') != false) {
            $_SESSION['error'] = 'Потребителското име вече съществува!';
        } elseif (strpos($err_msg, 'email') != false) {
            $_SESSION['error'] = 'Имейлът вече съществува!';
        }

    } else {
        $_SESSION['error'] = 'Грешка в базата данни!';
    }

    header('Location: ../register.php');
    exit;
}

// no errors straight up log in
session_unset();
session_destroy();

session_set_cookie_params([
    'lifetime' => 60 * 30
]);

session_start();
session_regenerate_id(true);

$new_user_id = mysqli_insert_id($conn);
$_SESSION['user_id'] = $new_user_id;
$_SESSION['username'] = $username;
header('Location: ../home.php');
exit;
