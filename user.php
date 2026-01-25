<?php
include("handlers/require_login.php");
require "config/db.php";

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: login.php");
    exit;
}

/* User info */
$stmt = mysqli_prepare(
    $conn,
    "SELECT username, email, description, created_at
     FROM User
     WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    header("Location: logout.php");
    exit;
}

/* Active tournaments */
$stmt = mysqli_prepare(
    $conn,
    "SELECT t.id, t.name, t.start_datetime
     FROM Tournament t
     JOIN Participates p ON p.tournament_id = t.id
     WHERE p.user_id = ?
       AND t.status != 'finished'"
);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$activeTournaments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $activeTournaments[] = $row;
}
mysqli_stmt_close($stmt);

/* Finished tournaments */
$stmt = mysqli_prepare(
    $conn,
    "SELECT t.id, t.name, t.category, p.result_position
     FROM Tournament t
     JOIN Participates p ON p.tournament_id = t.id
     WHERE p.user_id = ?
       AND t.status = 'finished'"
);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$finishedTournaments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $finishedTournaments[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Profile | TournamentHub</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>

<header class="navbar">
    <div class="logo"><a href="home.php">TournamentHub</a></div>
    <nav>
        <a href="home.php">Tournaments</a>
        <a href="user.php">Profile</a>
        <a href="create_tournament.php">Create Tournament</a>
        <a href="handlers/logout_handler.php">Logout</a>
    </nav>
</header>

<main class="container">
    <h1><?= htmlspecialchars($user['username']) ?></h1>
    <p><?= htmlspecialchars($user['email']) ?></p>
    <p><?= htmlspecialchars($user['description'] ?? '') ?></p>
    <p class="meta">Joined on <?= date("d.m.Y", strtotime($user['created_at'])) ?></p>

    <h2>Active tournaments</h2>
    <?php if (empty($activeTournaments)): ?>
        <p>No active tournaments.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($activeTournaments as $t): ?>
                <li>
                    <a href="tournament.php?id=<?= (int)$t['id'] ?>">
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
                    <a href="tournament.php?id=<?= (int)$t['id'] ?>">
                        <?= htmlspecialchars($t['name']) ?>
                    </a>
                    – position <?= htmlspecialchars((string)$t['result_position']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php
    $stats = [
        'participated' => count($finishedTournaments),
        'wins' => 0,
        'runner_ups' => 0,
        'position_7' => 0.
    ];

    foreach ($finishedTournaments as $t) {
        if ($t['result_position'] == 1) {
            $stats['wins']++;
        } elseif ($t['result_position'] == 2) {
            $stats['runner_ups']++;
        } elseif ($t['result_position'] == 7) {
            $stats['position_7']++;
        }
    }

   $badges = [];

    switch (true) {
        case $stats['wins'] >= 5:
            $badges[] = 'Легенда (5 победи)';
        case $stats['wins'] >= 3:
            $badges[] = 'Шампион (3 победи)';
        case $stats['wins'] >= 1:
            $badges[] = 'Победител (1 победа)';
            break;
    }

    switch (true) {
        case $stats['runner_ups'] >= 5:
            $badges[] = 'Вицешампион х5 (5 втори места, ауч)';
        case $stats['runner_ups'] >= 3:
            $badges[] = 'Вицешампион х3 (3 втори места)';
        case $stats['runner_ups'] >= 1:
            $badges[] = 'Вицешампион (1 второ място)';
            break;
    }

    if ($stats['position_7'] == 3) {
        $badges[] = '777 (3 седми места)';
    }

    $categoryCount = [];
    foreach ($finishedTournaments as $t) {
        $cat = $t['category'] ?? 'Няма категория';
        if (!isset($categoryCount[$cat])) {
            $categoryCount[$cat] = 0;
        }
        $categoryCount[$cat]++;
    }

    $favoriteCategory = null;
    if (!empty($categoryCount)) {
        arsort($categoryCount);
        $favoriteCategory = array_key_first($categoryCount);
    }
    ?>

    <h2>Статистики</h2>
    <ul>
        <li>Брой завършили турнири, в които съм участвал: <?= $stats['participated'] ?></li>
        <li>Спечелени турнири: <?= $stats['wins'] ?></li>
        <li>Втори места: <?= $stats['runner_ups'] ?></li>
        <li>Любима категория турнир: <?= htmlspecialchars($favoriteCategory ?? 'Няма') ?></li>
    </ul>

    <h2>Отличия</h2>
    <?php if (empty($badges)): ?>
        <p>Все още няма отличия.</p>
    <?php else: ?>
        <ul class="badges">
            <?php foreach ($badges as $badge): ?>
                <li><?= htmlspecialchars($badge) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>

</body>
</html>
