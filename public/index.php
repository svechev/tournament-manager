<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = '/tournament-manager/public';

if (strpos($uri, $base) === 0) {
    $path = substr($uri, strlen($base));
} else {
    $path = $uri;
}

$path = trim($path, '/');

switch ($path) {
    case 'show-tournaments':
        require __DIR__ . '/../api/search_tournaments.php';
        break;

    case 'tournament-results':
        require __DIR__ . '/../handlers/export/export_csv_handler.php';
        break;

    default:
        http_response_code(404);
        echo 'Not found';
        break;
}