<?php
include('handlers/require_login.php');
require "db.php";

$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        t.id,
        t.name,
        t.description,
        t.category,
        t.start_datetime,
        t.capacity,
        t.spots_taken
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

$categories = ['Шах', 'Спорт', 'Електронни спортове', 'Настолни игри'];
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

        <div class="filters" style="margin-bottom:16px;">
        <label for="category-filter">Category:</label>
        <select id="category-filter">
            <option value="">Всички</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="sort-date" style="margin-left:16px;">Sort by date:</label>
        <select id="sort-date">
            <option value="asc">↑ Ascending</option>
            <option value="desc">↓ Descending</option>
        </select>
    </div>

    <div id="tournament-grid" class="tournament-grid">
        <?php foreach ($tournaments as $t): ?>
            <div class="tournament-card"
             data-date="<?= strtotime($t['start_datetime']) ?>"
             data-category="<?= htmlspecialchars($t['category']) ?>">
                <div class="card-header">
                    <h2><?= htmlspecialchars($t['name']) ?></h2>
                    <span class="tag"><?= htmlspecialchars($t['category']) ?></span>
                </div>

                <p class="description">
                    <?= htmlspecialchars($t['description']) ?>
                </p>

                <div class="meta">
                    <span>📅 <?= date("d.m.Y H:i", strtotime($t['start_datetime'])) ?></span>
                    <span>👥 <?= (int)$t['spots_taken'] ?> / <?= (int)$t['capacity'] ?></span>
                </div>

                <div class="actions">
                    <a class="btn secondary"
                       href="tournament.php?id=<?= (int)$t['id'] ?>">View</a>

                    <?php if ((int)$t['spots_taken'] < (int)$t['capacity']): ?>
                        <form method="post" action="join.php">
                            <input type="hidden" name="tournament_id"
                                   value="<?= (int)$t['id'] ?>">
                            <button class="btn primary">Join</button>
                        </form>
                    <?php else: ?>
                        <button class="btn secondary" disabled>Full</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    const grid = document.getElementById('tournament-grid');
    const cards = Array.from(grid.querySelectorAll('.tournament-card'));
    const categoryFilter = document.getElementById('category-filter');
    const sortDate = document.getElementById('sort-date');

   function updateTournaments() {
    const cat = categoryFilter.value;
    const order = sortDate.value;

    cards.forEach(card => {
        const cardCat = card.dataset.category;
        card.style.display = (!cat || cardCat === cat) ? 'block' : 'none';
    });

    const visibleCards = cards.filter(c => c.style.display !== 'none');
    visibleCards.sort((a, b) => {
        const aTime = parseInt(a.dataset.date);
        const bTime = parseInt(b.dataset.date);
        return order === 'asc' ? aTime - bTime : bTime - aTime;
    });

    visibleCards.forEach(c => grid.appendChild(c));
    }
    categoryFilter.addEventListener('change', updateTournaments);
    sortDate.addEventListener('change', updateTournaments);
    updateTournaments(); 
    </script>
</main>

</body>
</html>
