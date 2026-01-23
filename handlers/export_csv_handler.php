<?php
include('require_login.php');
require '../db.php';

$tournament_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$tournament_id) {
    http_response_code(400);
    exit('Invalid tournament');
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
    fputcsv($out, [$row['team_name'], $pos]);
}

fclose($out);
exit();