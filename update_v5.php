<?php
require_once 'common/config.php';

echo "<h3>Starting Database Update for Contact System...</h3>";

// --- 1. Create the 'contact_messages' table ---
$sql_messages = "
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('Pending', 'Read') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql_messages)) {
    echo "<p style='color:green;'>✅ Table 'contact_messages' created or already exists.</p>";
    echo "<hr><h2>✅ Database schema updated successfully! You can now safely delete this file.</h2>";
} else {
    echo "<p style='color:red;'>❌ Error creating 'contact_messages' table: " . $conn->error . "</p>";
}

$conn->close();
?>