<?php
    
    session_start();


    $error = $_SESSION['error'] ?? null;
    $prev = $_SESSION['prev'] ?? [];

    unset($_SESSION['error']);
    unset($_SESSION['prev']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | TournamentHub</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <h1 class="logo">TournamentHub</h1>
        <h2>Sign in</h2>

        <form action="handlers/login_handler.php" method="post">
            <?php if ($error): ?>
                <div class="input-group error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="input-group">
                <label>Username or Email</label>
                <input type="text" name="username-email" value="<?= htmlspecialchars($prev['username-email'] ?? '') ?>" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn primary">Login</button>
        </form>

        <p class="switch">
            Don’t have an account?
            <a href="register.php">Register</a>
        </p>
    </div>
</div>

<script src="auth.js"></script>
</body>
</html>
