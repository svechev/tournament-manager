<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username_email = trim($_POST['username-email']);                  
$password = $_POST['password'];

if ($username_email === '' || $password === '') {
    $_SESSION['error'] = 'Липсващи полета!';
    header('Location: ../login.php');
    exit;
}


$_SESSION['prev'] = ['username-email' => $username_email];


$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "tournament_db";
$conn = "";

try {
    $conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
}
catch (mysqli_sql_exception) {
    $_SESSION['error'] = 'Неуспешна връзка с базата данни!';
    header('Location: ../login.php');
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, username, email, password_hash
            FROM User
            WHERE username = ? OR email = ?"
);
mysqli_stmt_bind_param($stmt, "ss", $username_email, $username_email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result->num_rows == 0) {
    $_SESSION['error'] = 'Името или имейлът не съществува!';

    mysqli_stmt_close($stmt);
    header('Location: ../login.php');
    exit;
}
else {
    $row = $result->fetch_assoc();
    $hash = $row['password_hash'];

    mysqli_stmt_close($stmt);

    if (password_verify($password, $hash)) {
        
        session_unset();
        session_destroy();

        session_set_cookie_params([
            'lifetime' => 60 * 30
        ]);

        session_start();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        header('Location: ../home.php');
        exit;
    }
    else {
        $_SESSION['error'] = 'Неправилна парола!';
        header('Location: ../login.php');
        exit;
    }
}
