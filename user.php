<?php
include("handlers/require_login.php");
require "db.php";

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
    "SELECT t.name, p.result_position
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
                    <?= htmlspecialchars($t['name']) ?>
                    – position <?= htmlspecialchars((string)$t['result_position']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>

</body>
</html>
