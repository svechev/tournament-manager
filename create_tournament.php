<?php
include('handlers/require_login.php');
require "db.php";

$minDatetime = date('Y-m-d\T00:00', strtotime('+1 day'));

$error = $_SESSION['error'] ?? null;
$prev = $_SESSION['prev'] ?? [];
$success = $_SESSION['success'] ?? null;

unset($_SESSION['error']);
unset($_SESSION['success']);
unset($_SESSION['prev']);

$categories = ['Шах', 'Спорт', 'Електронни спортове', 'Настолни игри'];
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>CreateTournament | TournamentHub</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <h2>Създай нов турнир</h2>

        <form action="handlers/create_tournament_handler.php" method="post"
            enctype="multipart/form-data">
            <?php if ($error): ?>
                <div class="input-group error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
        
            <div class="input-group">
                <label>Име</label>
                <input type="text" name="name" value="<?= htmlspecialchars($prev['name'] ?? '') ?>" required>
            </div>

            <div class="input-group">
                <label>Описание</label>
                <textarea name="description" rows="4" required>
                    <?= htmlspecialchars($prev['description'] ?? '') ?>
                </textarea>
            </div>

            <div class="input-group">
                <label>Категория</label>
                <select name="category" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category) ?>"
                            <?= ($category === ($prev['category'] ?? '')) ? 'selected' : '' ?>>>
                                <?= htmlspecialchars($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <label>Начало</label>
                <input type="datetime-local" name="start-datetime" min="<?= $minDatetime ?>" value="<?= htmlspecialchars($prev['start-datetime'] ?? '') ?>" required>
            </div>

            <div class="input-group date-time">
                <label>Край</label>
                <input type="datetime-local" name="end-datetime" min="<?= $minDatetime ?>" value="<?= htmlspecialchars($prev['end-datetime'] ?? '') ?>" required>
            </div>

            <div class="input-group">
                <label>Капацитет</label>
                <input type="number" name="capacity" min="2" value="<?= htmlspecialchars($prev['capacity'] ?? '') ?>" required>
            </div>

            <div class="input-group">
                <input type="radio" name="is-team-based" value="team"
                    <?= (($prev['is_team_based'] ?? '') === 'team') ? 'checked' : '' ?>>
                <label for="html">Отборен</label><br>

                <input type="radio" name="is-team-based" value="individual"
                <?= (($prev['is_team_based'] ?? '') === 'individual') ? 'checked' : '' ?>>
                <label for="css">Индивидуален</label><br>

                <input type="file" name="participants_csv" accept=".csv">
                <label>Участници (CSV файл, по избор)</label><br>

            </div>

            <button type="submit" class="btn primary">Създай</button>

            <?php if ($success): ?>
                <div class="input-group success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
