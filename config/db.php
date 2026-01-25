<?php

$db_server = "localhost";
$db_user   = "root";
$db_pass   = "";
$db_name   = "tournament_db";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

    // Important for Bulgarian text
    mysqli_set_charset($conn, "utf8mb4");

} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo "<h1>Database connection failed</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
