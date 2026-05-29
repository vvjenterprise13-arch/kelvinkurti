<?php
$host = 'zephyr.proxy.rlwy.net';
$port = 47728;
$user = 'root';
$pass = 'BwDuwWwWfQTARheGKcDxFArfbZqlXkUR';
$db   = 'railway';

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
