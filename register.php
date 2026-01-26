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
    <title>Регистрация | TournamentHub</title>
    <link rel="stylesheet" href="styles/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <h1 class="logo">TournamentHub</h1>
        <h2>Създай Акаунт</h2>

        <form action="handlers/auth/register_handler.php" method="post">
            <?php if ($error): ?>
                <div class="input-group error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
        
            <div class="input-group">
                <label>Потребителско Име</label>
                <input type="text" name="username" value="<?= htmlspecialchars($prev['username'] ?? '') ?>" required>
            </div>

            <div class="input-group">
                <label>Имейл Адрес</label>
                <input type="email" name="email" value="<?= htmlspecialchars($prev['email'] ?? '') ?>" required>
            </div>

            <div class="input-group">
                <label>Парола</label>
                <input type="password" name="password" required>
            </div>

            <div class="input-group">
                <label>Потвърди Парола</label>
                <input type="password" name="confirm-password" required>
            </div>

            <button type="submit" class="btn primary">Регистрация</button>
        </form>

        <p class="switch">
            Вече имаш акаунт?
            <a href="login.php">Вход</a>
        </p>
    </div>
</div>

<script src="auth.js"></script>
</body>
</html>
