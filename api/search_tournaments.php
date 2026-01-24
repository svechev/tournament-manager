<?php
require_once __DIR__ . '/../db.php';

$token = $_GET['token'];

$envLines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($envLines as $line) {
    if (strpos($line, 'CSV_API_TOKEN=') === 0) {
        $csvToken = substr($line, strlen('CSV_API_TOKEN='));
    }
}

if ($token !== $csvToken) {
    http_response_code(401);
    exit('Unauthorized');
}

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    http_response_code(400);
    exit('Missing search query');
}

$sql = "
    SELECT id, name
    FROM Tournament
    WHERE name LIKE ?
";
$stmt = mysqli_prepare($conn, $sql);

$like = '%' . $q . '%';
mysqli_stmt_bind_param($stmt, 's', $like);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="tournaments.csv"');

echo "\xEF\xBB\xBF";

echo "ID,Име\n";

while ($row = mysqli_fetch_assoc($result)) {
    $id = (int)$row['id'];
    $name = str_replace('"', '""', $row['name']);
    echo "$id,\"$name\"\n";
}