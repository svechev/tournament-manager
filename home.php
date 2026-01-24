<?php
include('handlers/require_login.php');
require "db.php";
require_once __DIR__ . '/handlers/ensure_due_tournaments_started.php';
ensureDueTournamentsStarted($conn);

$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        t.id,
        t.name,
        t.description,
        t.category,
        t.start_datetime,
        t.capacity,
        t.spots_taken,
        t.status
     FROM Tournament t
     WHERE t.status != 'finished'
     ORDER BY t.start_datetime"
);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$tournaments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tournaments[] = $row;
}

mysqli_stmt_close($stmt);

$categories = ['Образователни','Шах', 'Спорт', 'Видеоигри', 'Настолни игри','Други'];
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
    <div class="logo"><a href="home.php">TournamentHub</a></div>
    <nav>
        <a href="home.php">Tournaments</a>
        <a href="user.php">Profile</a>
        <a href="create_tournament.php">Create Tournament</a>
        <a href="handlers/logout_handler.php">Logout</a>
    </nav>
</header>

<main class="container">
    <h1>Предстоящи и активни турнири</h1>

        <div class="filters">
        <label for="category-filter">Категория:</label>
        <select id="category-filter">
            <option value="">Всички</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="status-filter">Статут:</label>
        <select id="status-filter">
            <option value="">Всички</option>
            <option value="ongoing">Активни</option>
            <option value="upcoming">Предстоящи</option>
        </select>

        <label for="sort-date">Сортирай по дата:</label>
        <select id="sort-date">
            <option value="asc">↑ Възходящ</option>
            <option value="desc">↓ Низходящ</option>
        </select>
        </div>

    <div id="tournament-grid" class="tournament-grid">
        <?php foreach ($tournaments as $t): ?>
            <div class="tournament-card"
             data-date="<?= strtotime($t['start_datetime']) ?>"
             data-category="<?= htmlspecialchars($t['category']) ?>"
             data-status="<?= $t['status'] ?>">
                <div class="card-header">
                    <h2><?= htmlspecialchars($t['name']) ?></h2>

                    <?php
                    $status = $t['status'];
                    if ($status === 'upcoming') {
                    $label = 'Статут: Предстоящ';
                    } elseif ($status === 'ongoing') {
                    $label = 'Статут: Активен';
                    } else {
                    $label = htmlspecialchars($status);
                    }
                    ?>

                    <span class="tag"><?= htmlspecialchars($t['category']) ?></span><br>
                    <span class="tag"><?= $label ?></span>
                </div>

                <p class="description">
                    <?= htmlspecialchars($t['description']) ?>
                </p>

                <div class="meta">
                    <span>📅 <?= date("d.m.Y H:i", strtotime($t['start_datetime'])) ?></span>
                    <span>👥 <?= (int)$t['spots_taken'] ?> / <?= (int)$t['capacity'] ?></span>
                </div>

                <div class="actions">
                    
                    <?php
                    $status = $t['status'];
                    if ($status === 'upcoming') {
                    $button_text = 'Разгледай/ Присъедини се';
                    } elseif ($status === 'ongoing') {
                    $button_text = 'Разгледай';
                    } else {
                    $button_text = htmlspecialchars($status);
                    }
                    ?>
                    <a class="btn"
                       href="tournament.php?id=<?= (int)$t['id'] ?>"><?= $button_text ?></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="scripts/home.js"></script>
</main>

</body>
</html>
