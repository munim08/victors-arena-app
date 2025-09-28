<?php
session_start();

// This code is smart: It reads the secret credentials from the Render environment.
$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');

// If it's running on your local server where those secrets don't exist,
// it falls back to your local credentials.
if (empty($db_host)) {
    $db_host = '127.0.0.1';        // Your local server IP
    $db_user = 'root';
    $db_pass = 'root';             // Your local password
    $db_name = 'adept_play_db';    // Your local database name
}

// Establish the database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check for connection errors
if ($conn->connect_error) {
    // On a live server, we don't want to show detailed errors to the user.
    // We log the error for the developer and show a generic message.
    error_log("Database connection failed: " . $conn->connect_error);
    http_response_code(500); // Internal Server Error
    die("Error: A connection to the service could not be established. Please try again later.");
}
?>
