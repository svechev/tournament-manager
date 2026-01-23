<?php
include('handlers/require_login.php');
require "db.php";

$tournament_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_id = (int)$_SESSION['user_id'];

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
        t.is_team_based,
        t.creator_user_id
     FROM Tournament t
     WHERE t.id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $tournament_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$t = mysqli_fetch_assoc($result);

$is_creator = false;
//check if user is the creator
if ((int)$t['creator_user_id'] == $user_id) {
    $is_creator = true;
}

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

mysqli_stmt_bind_param($stmt, "ii", $user_id, $tournament_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$participates = (bool)$row['participates'];

// get matches of the tournament
$matches = [];
$rounds = [];
$stmt = mysqli_prepare(
    $conn,
    "SELECT *
        FROM Matches
        WHERE tournament_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $tournament_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $matches[] = $row;
    $round = (int)$row['current_round'];
    if (!in_array($round, $rounds)) {
        $rounds[] = $round;
    }
}
rsort($rounds);



mysqli_stmt_close($stmt);

$error = $_SESSION['error'] ?? null;
$update_score_error = $_SESSION['update_score_error'] ?? null;
$success = $_SESSION['success'] ?? null;

unset($_SESSION['error']);
unset($_SESSION['update_score_error']);
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
    <div class="logo"><a href="home.php">TournamentHub</a></div>
    <nav>
        <a href="home.php">Tournaments</a>
        <a href="user.php">Profile</a>
        <a href="create_tournament.php">Create Tournament</a>
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

                <?php if ($t['status'] === 'finished' || $t['status'] === 'ongoing'): ?>
                    <form method="get" action="handlers/export_csv_handler.php">
                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                        <button type="submit" class="btn secondary">Изтегли CSV с класирането</button>
                    </form>
                <?php endif; ?>

                <?php if ($t['status'] == 'upcoming'): ?>
                    <?php if (!$participates): ?>
                        <div class="join-form">
                            <?php if ((int)$t['spots_taken'] < (int)$t['capacity']): ?>
                                
                                <form method="post" action="handlers/join_tournament_handler.php">
                                    <?php if ((bool)$t['is_team_based'] == false): ?>
                                        <label>
                                            Прякор
                                            <input type="text" name="team_name"
                                                value="<?= htmlspecialchars($prev['team_name'] ?? '') ?>"
                                                required>
                                        </label>

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
        <?php if ($update_score_error): ?>
                <div class="input-group error"><?= htmlspecialchars($update_score_error) ?></div>
        <?php endif; ?>

        <?php if ($t['status'] === 'upcoming'): ?>
            <div>Няма налична схема</div>
        <?php else: ?>
            <label>Избери рунд</label>
            <select name="round-picker" id="round-picker">
                <?php foreach ($rounds as $round): ?>
                    <option value="<?= htmlspecialchars($round) ?>">
                        <?php
                            if ($round === 1) {
                                $text = 'Финал';
                            } else {
                                $text = '1/' . $round . " финал";
                            }
                        ?>    
                    <?= htmlspecialchars($text) ?>
                    </option>
                <?php endforeach; ?>
            </select>


            <div class="match-grid">
                <?php foreach ($matches as $m): ?>
                <div class="match-card" data-round="<?= (int)$m['current_round'] ?>">
                    <div class="header">
                        <h2>
                            <?php
                                $round = (int)$m['current_round'];
                                if ($round === 1) {
                                    $text = 'Финал';
                                } else {
                                    $text = '1/' . $round . " финал";
                                }
                            ?>    
                        <?= htmlspecialchars($text) ?></h2>
                    </div>

                    <div class="meta">
                        <span>📅 <?= date("d.m.Y H:i", strtotime($m['match_date'])) ?></span>
                    </div>

                    <div class="score">
                        <span>
                            <?= $m['side1_nickname'] != null ? htmlspecialchars($m['side1_nickname']) : 'TBD'?>
                        </span>
                        
                        <span>
                            <?= $m['score'] != null ? htmlspecialchars($m['score']) : '-'?>
                        </span>

                        <span>
                            <?= $m['side2_nickname'] != null ? htmlspecialchars($m['side2_nickname']) : 'TBD'?>
                        </span>
                    </div>

                    <?php if ($is_creator && $t['status'] === 'ongoing' 
                            && $m['score'] === null
                            && $m['side1_nickname'] != null
                            && $m['side2_nickname'] != null): ?>
                        <div class="actions">
                            <form method="post" action="handlers/update_score_handler.php">
                                <input class="input-score" type="number" name="player1_score" min="0" required>
                                <label>-</label>
                                <input class="input-score" type="number" name="player2_score" min="0" required>

                                <input type="hidden" name="match_id"
                                        value="<?= (int)$m['match_id'] ?>">
                                <input type="hidden" name="tournament_id"
                                        value="<?= (int)$t['id'] ?>">
                                <button class="btn" type="submit">Обнови резултат</button>
                            </form>
                        </div>
                    <?php endif; ?>

                </div>
                
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
<script src="scripts/tournament.js"></script>
<script src="scripts/display_rounds.js"></script>
</html>