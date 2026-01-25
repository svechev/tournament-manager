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


require '../config/db.php';

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
            'lifetime' => 60 * 60 * 2
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
