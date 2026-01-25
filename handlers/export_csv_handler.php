<?php
require '../config/db.php';

$tournament_id =
    filter_input(INPUT_GET, 'tournament_id', FILTER_VALIDATE_INT)
    ?? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$tournament_id) {
    http_response_code(400);
    exit('Invalid tournament ID');
}

$is_api_call = isset($_GET['token']);

if ($is_api_call) {
    $envLines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos($line, 'CSV_API_TOKEN=') === 0) {
            $csvToken = substr($line, strlen('CSV_API_TOKEN='));
        }
    }
    if ($_GET['token'] !== $csvToken) {
        http_response_code(401);
        exit('Unauthorized');
    }
} else {
    include 'require_login.php';
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT status FROM Tournament WHERE id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $tournament_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$t = mysqli_fetch_assoc($res);

if (!$t || !in_array($t['status'], ['finished', 'ongoing'])) {
    http_response_code(403);
    exit('Not allowed');
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT team_name, result_position
     FROM Participates
     WHERE tournament_id = ?
     ORDER BY
     result_position IS NOT NULL,
     result_position ASC"
);
mysqli_stmt_bind_param($stmt, "i", $tournament_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="tournament' . $tournament_id . '_players.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['Участници', 'Позиция']);

while ($row = mysqli_fetch_assoc($res)) {
    $pos = $row['result_position'] ?? 'ще се определи';

     if (is_numeric($pos)) {
        $pos = (int)$pos;
        if ($pos === 1) {
            $pos = 'Първо място';
        } elseif ($pos === 2) {
            $pos = 'Второ място';
        } elseif ($pos === 3 || $pos === 4) {
            $pos = 'Полуфинал';
        } elseif ($pos > 4) {
            $pos = '1/' . ($pos - 1) . ' финал';
        }
    } else {
        $pos = 'ще се определи';
    }
    fputcsv($out, [$row['team_name'], $pos]);
}

fclose($out);
exit();