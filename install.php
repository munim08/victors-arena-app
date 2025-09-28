<?php
// --- THIS IS THE FIX ---
// Make this script smart, just like config.php

// Read the database credentials from the Render environment
$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');

// If running locally, fall back to local credentials
if (empty($db_host)) {
    $db_host = '127.0.0.1';
    $db_user = 'root';
    $db_pass = 'root';
    $db_name = 'adept_play_db'; // This will be created if it doesn't exist
}

// --- END OF FIX ---

// Establish the initial connection WITHOUT specifying a database yet
$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the database (it will use the $db_name variable from above)
$sql_create_db = "CREATE DATABASE IF NOT EXISTS `$db_name`";
if ($conn->query($sql_create_db) === FALSE) {
    die("Error creating database: " . $conn->error);
}

// Now, select the database to use
$conn->select_db($db_name);

// --- The rest of your install script (creating tables, etc.) remains the same ---

// (Your existing SQL queries for creating tables go here)
$sql_tables = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `email` varchar(100) NOT NULL,
        `password` varchar(255) NOT NULL,
        `wallet_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
        `status` enum('active','blocked') NOT NULL DEFAULT 'active',
        `upi_id` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- ... and so on for all your other CREATE TABLE statements ...
";

if ($conn->multi_query($sql_tables)) {
    // Clear results from multi_query
    while ($conn->next_result()) {;}

    // Insert default admin
    $admin_user = 'admin';
    $admin_pass = password_hash('Levi@exe', PASSWORD_DEFAULT); // Using your specified password
    
    $check_admin = $conn->prepare("SELECT id FROM admin WHERE username = ?");
    $check_admin->bind_param("s", $admin_user);
    $check_admin->execute();
    if ($check_admin->get_result()->num_rows == 0) {
        $insert_admin = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $insert_admin->bind_param("ss", $admin_user, $admin_pass);
        $insert_admin->execute();
    }

    $conn->close();
    
    // Redirect to the login page
    header("Location: login.php");
    exit();
} else {
    echo "Error creating tables: " . $conn->error;
}
?>
