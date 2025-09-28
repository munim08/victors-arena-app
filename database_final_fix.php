<?php
require_once 'common/config.php';

echo "<h3>Running Final Database Fix for Deleting Tournaments...</h3>";
$db_name = $conn->query("SELECT database()")->fetch_row()[0];

// Find the existing foreign key constraint name for participants -> tournaments
$find_fk_sql = "
    SELECT CONSTRAINT_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = ? 
      AND TABLE_NAME = 'participants' 
      AND COLUMN_NAME = 'tournament_id'
";
$stmt = $conn->prepare($find_fk_sql);
$stmt->bind_param("s", $db_name);
$stmt->execute();
$fk_result = $stmt->get_result();

if ($fk_result->num_rows > 0) {
    $fk_name = $fk_result->fetch_assoc()['CONSTRAINT_NAME'];

    // Drop the old constraint
    if ($conn->query("ALTER TABLE `participants` DROP FOREIGN KEY `$fk_name`")) {
        echo "<p style='color:green;'>✅ Successfully dropped old foreign key rule.</p>";
        
        // Add the new constraint with ON DELETE CASCADE
        $add_fk_sql = "
            ALTER TABLE `participants` 
            ADD CONSTRAINT `participants_ibfk_2` 
            FOREIGN KEY (`tournament_id`) 
            REFERENCES `tournaments`(`id`) 
            ON DELETE CASCADE ON UPDATE CASCADE
        ";
        if ($conn->query($add_fk_sql)) {
            echo "<p style='color:green;'>✅ Successfully added new CASCADE DELETE rule.</p>";
            echo "<hr><h2>✅ Database is now fixed. You can now safely delete this file.</h2>";
        } else {
            echo "<p style='color:red;'>❌ Error adding new rule: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Error dropping old rule: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:blue;'>✅ No existing rule found. The rule might already be correct.</p>";
}

$conn->close();
?>