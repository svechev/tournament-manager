<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$stmt = $pdo->query("
    SELECT 
        t.id,
        t.name,
        t.description,
        t.category,
        t.start_datetime,
        t.capacity,
        t.spots_taken
    FROM Tournament t
    WHERE t.status != 'finished'
    ORDER BY t.start_datetime
");

$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Home | TournamentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="logo">TournamentHub</div>
    <nav>
        <a href="home.php">Tournaments</a>
        <a href="user.php">Profile</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">
    <h1>Upcoming & Ongoing Tournaments</h1>

    <div class="tournament-grid">
        <?php foreach ($tournaments as $t): ?>
            <div class="tournament-card">
                <div class="card-header">
                    <h2><?= htmlspecialchars($t['name']) ?></h2>
                    <span class="tag"><?= htmlspecialchars($t['category']) ?></span>
                </div>

                <p class="description">
                    <?= htmlspecialchars($t['description']) ?>
                </p>

                <div class="meta">
                    <span>📅 <?= date("d.m.Y H:i", strtotime($t['start_datetime'])) ?></span>
                    <span>👥 <?= $t['spots_taken'] ?> / <?= $t['capacity'] ?></span>
                </div>

                <div class="actions">
                    <a class="btn secondary"
                       href="tournament.php?id=<?= $t['id'] ?>">View</a>

                    <?php if ($t['spots_taken'] < $t['capacity']): ?>
                        <form method="post" action="join.php">
                            <input type="hidden" name="tournament_id"
                                   value="<?= $t['id'] ?>">
                            <button class="btn primary">Join</button>
                        </form>
                    <?php else: ?>
                        <button class="btn secondary" disabled>Full</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

</body>
</html>
