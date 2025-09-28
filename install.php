<?php
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
    $db_name = 'victor_arena_db'; // This will be created if it doesn't exist
}

// Establish the initial connection WITHOUT specifying a database yet
$conn = new mysqli($db_host, $db_user, $db_pass);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Create the database
$conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
$conn->select_db($db_name);

// --- COMPLETE SQL TO CREATE ALL TABLES ---
$sql_tables = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT, `username` varchar(50) NOT NULL, `email` varchar(100) NOT NULL, `password` varchar(255) NOT NULL,
        `wallet_balance` decimal(10,2) NOT NULL DEFAULT '0.00', `status` enum('active','blocked') NOT NULL DEFAULT 'active', `upi_id` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `username` (`username`), UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `admin` (
        `id` int(11) NOT NULL AUTO_INCREMENT, `username` varchar(50) NOT NULL, `password` varchar(255) NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `tournaments` (
        `id` int(11) NOT NULL AUTO_INCREMENT, `title` varchar(100) NOT NULL, `game_name` varchar(50) NOT NULL, `entry_fee` decimal(10,2) NOT NULL,
        `prize_pool` decimal(10,2) NOT NULL, `max_slots` int(11) NOT NULL DEFAULT 25, `total_matches` int(11) NOT NULL DEFAULT 1,
        `match_time` datetime NOT NULL, `status` enum('Upcoming','Live','Completed') NOT NULL DEFAULT 'Upcoming', `winner_1st_id` int(11) DEFAULT NULL,
        `prize_1st` decimal(10,2) DEFAULT NULL, `winner_2nd_id` int(11) DEFAULT NULL, `prize_2nd` decimal(10,2) DEFAULT NULL,
        `winner_3rd_id` int(11) DEFAULT NULL, `prize_3rd` decimal(10,2) DEFAULT NULL, `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `participants` (
        `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `tournament_id` int(11) NOT NULL, PRIMARY KEY (`id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS `matches` (
      `id` INT AUTO_INCREMENT PRIMARY KEY, `tournament_id` INT NOT NULL, `match_number` INT NOT NULL, `room_id` VARCHAR(100) NULL,
      `room_password` VARCHAR(100) NULL, `status` ENUM('Pending', 'Live', 'Completed') NOT NULL DEFAULT 'Pending',
      FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `match_results` (
      `id` INT AUTO_INCREMENT PRIMARY KEY, `match_id` INT NOT NULL, `participant_id` INT NOT NULL, `rank` INT NULL,
      `kills` INT NOT NULL DEFAULT 0, FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`participant_id`) REFERENCES `participants`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `unique_match_participant` (`match_id`, `participant_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS `transactions` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `amount` decimal(10,2) NOT NULL,
      `type` enum('credit','debit') NOT NULL, `description` varchar(255) NOT NULL, `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`), FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS `deposits` (
      `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL, `amount` DECIMAL(10, 2) NOT NULL,
      `transaction_id` VARCHAR(255) NOT NULL, `status` ENUM('Pending', 'Completed', 'Rejected') NOT NULL DEFAULT 'Pending',
      `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `withdrawals` (
      `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL, `amount` DECIMAL(10, 2) NOT NULL, `upi_id` VARCHAR(255) NOT NULL,
      `status` ENUM('Pending', 'Completed', 'Rejected') NOT NULL DEFAULT 'Pending', `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `settings` (
      `id` INT AUTO_INCREMENT PRIMARY KEY, `setting_key` VARCHAR(50) NOT NULL UNIQUE, `setting_value` TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `contact_messages` (
      `id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL, `subject` VARCHAR(255) NOT NULL, `message` TEXT NOT NULL,
      `status` ENUM('Pending', 'Read') NOT NULL DEFAULT 'Pending', `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->multi_query($sql_tables)) {
    while ($conn->next_result()) {;} // Clear results
    
    // Insert default admin
    $admin_user = 'admin';
    $admin_pass = password_hash('Levi@exe', PASSWORD_DEFAULT);
    $check_admin = $conn->prepare("SELECT id FROM admin WHERE username = ?");
    $check_admin->bind_param("s", $admin_user);
    $check_admin->execute();
    if ($check_admin->get_result()->num_rows == 0) {
        $insert_admin = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $insert_admin->bind_param("ss", $admin_user, $admin_pass);
        $insert_admin->execute();
    }

    $conn->close();
    header("Location: login.php");
    exit();
} else {
    echo "Error creating tables: " . $conn->error;
}
?>
