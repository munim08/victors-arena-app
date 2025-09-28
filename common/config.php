<?php
session_start();

$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'adept_play_db';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>