<?php
include('handlers/require_login.php');
require "db.php";

$tournament_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($tournament_id === false || $tournament_id === null) {
    http_response_code(400);
    echo 'Invalid tournament ID';
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        t.id,
        t.name,
        t.description,
        t.category,
        t.start_datetime,
        t.end_datetime,
        t.status,
        t.capacity,
        t.spots_taken,
        t.is_team_based
     FROM Tournament t
     WHERE t.id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $tournament_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$t = mysqli_fetch_assoc($result);

// get a list of the teams
if ((bool)$t['is_team_based'] == true) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT DISTINCT p.team_name
        FROM Participates p
        WHERE p.tournament_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $tournament_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $teams = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $teams[] = $row['team_name'];
    }
}

// check if user is in tournament already
$stmt = mysqli_prepare(
    $conn,
    "SELECT EXISTS (
        SELECT 1
        FROM Participates
        WHERE user_id = ? AND tournament_id = ?
    ) AS participates"
);

$user_id = (int)$_SESSION['user_id'];

mysqli_stmt_bind_param($stmt, "ii", $user_id, $tournament_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$participates = (bool)$row['participates'];



mysqli_stmt_close($stmt);

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

unset($_SESSION['error']);
unset($_SESSION['success']);
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
        <a href="handlers/logout_handler.php">Logout</a>
    </nav>
</header>

<main class="container">
    <div class="tournament-information">
        <div class="tournament-card">
                <div class="card-header">
                    <h2><?= htmlspecialchars($t['name']) ?></h2>
                    <span class="tag"><?= htmlspecialchars($t['category']) ?></span><br>
                    
                    <?php
                    $status = $t['status'];
                    if ($status === 'upcoming') {
                    $label = 'Статут: Предстоящ';
                    } elseif ($status === 'ongoing') {
                    $label = 'Статут: Активен';
                    } else {
                    $label = 'Статут: Завършил';
                    }
                    ?>

                    <span class="tag"><?= $label ?></span>
                </div>

                <p class="description">
                    <?= htmlspecialchars($t['description']) ?>
                </p>

                <div class="meta">
                    <span>📅 <?= date("d.m.Y H:i", strtotime($t['start_datetime'])) ?></span>
                    <span>👥 <?= (int)$t['spots_taken'] ?> / <?= (int)$t['capacity'] ?></span>
                </div>

                <?php if ($t['status'] == 'upcoming'): ?>
                    <?php if (!$participates): ?>
                        <div class="join-form">
                            <?php if ((int)$t['spots_taken'] < (int)$t['capacity']): ?>
                                
                                <form method="post" action="handlers/join_tournament_handler.php">
                                    <?php if ((bool)$t['is_team_based'] == false): ?>
                                
                                        <input type="text" name="team_name"
                                            value="<?= htmlspecialchars($prev['team_name'] ?? '') ?>"
                                            required>

                                    <?php else: ?>
                                        <div class="team-choice">
                                            <label>
                                                <input type="radio" name="new_team" value="create" checked>
                                                Създай отбор
                                            </label>

                                            <label>
                                                <input type="radio" name="new_team" value="join">
                                                Избери отбор
                                            </label>
                                        </div>

                                        <div id="create-team-box">
                                            <label>Име на отбор</label>
                                            <input type="text"
                                                name="new_team_name">
                                        </div>

                                        <div id="join-team-box" style="display: none;">
                                            <label>Избери отбор</label>
                                            <select name="team_name">
                                                <?php foreach ($teams as $team): ?>
                                                    <option value="<?= htmlspecialchars($team) ?>">
                                                        <?= htmlspecialchars($team) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <input type="hidden" name="tournament_id"
                                        value="<?= (int)$t['id'] ?>">
                                    <button type="submit" class="btn primary">Присъединяване</button>
                                </form>

                            <?php else: ?>
                                <button class="btn secondary" disabled>Пълен</button>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="success"><?= htmlspecialchars($success) ?></div>
                            <?php endif; ?>

                            <?php if ($error): ?>
                                <div class="error"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="leave-form">  
                            <form method="post" action="handlers/leave_tournament_handler.php">
                                <input type="hidden" name="tournament_id"
                                    value="<?= $tournament_id ?>">
                                <button type="submit" class="btn primary">Напускане</button>
                            </form>

                            <?php if ($success): ?>
                                <div class="success"><?= htmlspecialchars($success) ?></div>
                            <?php endif; ?>

                            <?php if ($error): ?>
                                <div class="error"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                        </div>
                     <?php endif; ?>
                <?php endif; ?>
            </div>
    </div>
    <div class="tournament-bracket">
        <?php if ($t['status'] === 'upcoming'): ?>
            <div>Няма налична схема</div>
        <?php else: ?>
            <!-- тук рисуваме схема -->
        <?php endif; ?>
    </div>
</main>

</body>
<script src="scripts/tournament.js"></script>
</html>