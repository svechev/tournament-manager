<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT username, email, description, created_at
    FROM User
    WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT t.id, t.name, t.start_datetime
    FROM Tournament t
    JOIN Participates p ON p.tournament_id = t.id
    WHERE p.user_id = ?
      AND t.status != 'finished'
");
$stmt->execute([$userId]);
$activeTournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT t.name, p.result_position
    FROM Tournament t
    JOIN Participates p ON p.tournament_id = t.id
    WHERE p.user_id = ?
      AND t.status = 'finished'
");
$stmt->execute([$userId]);
$finishedTournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Profile | TournamentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="logo">TournamentHub</div>
    <nav>
        <a href="home.php">Tournaments</a>
        <a href="user.php">Profile</a>
    </nav>
</header>

<main class="container">
    <h1><?= htmlspecialchars($user['username']) ?></h1>
    <p><?= htmlspecialchars($user['email']) ?></p>
    <p><?= htmlspecialchars($user['description']) ?></p>
    <p class="meta">Joined on <?= date("d.m.Y", strtotime($user['created_at'])) ?></p>

    <h2>Active tournaments</h2>
    <?php if (empty($activeTournaments)): ?>
        <p>No active tournaments.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($activeTournaments as $t): ?>
                <li>
                    <a href="tournament.php?id=<?= $t['id'] ?>">
                        <?= htmlspecialchars($t['name']) ?>
                    </a>
                    – <?= date("d.m.Y", strtotime($t['start_datetime'])) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h2>Finished tournaments</h2>
    <?php if (empty($finishedTournaments)): ?>
        <p>No finished tournaments.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($finishedTournaments as $t): ?>
                <li>
                    <?= htmlspecialchars($t['name']) ?>
                    – position <?= $t['result_position'] ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>

</body>
</html>
