<?php
require_once '../common/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Panel - Victor Arena</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { -webkit-tap-highlight-color: transparent; } </style>
</head>
<body class="bg-gray-900 text-white select-none">
    <div class="container mx-auto max-w-lg min-h-screen bg-gray-800 pb-20">
        <header class="p-4 flex justify-between items-center bg-gray-900 shadow-md sticky top-0 z-10">
            <h1 class="text-xl font-bold">Admin Panel</h1>
            
            <!-- === NEW HEADER ICONS === -->
            <div class="flex items-center space-x-6">
                <!-- Messages Icon with Notification Badge -->
                <a href="messages.php" class="text-gray-300 hover:text-white relative" title="Messages">
                    <i class="fas fa-envelope fa-lg"></i>
                    <?php
                        $pending_count_query = $conn->query("SELECT COUNT(id) as count FROM contact_messages WHERE status = 'Pending'");
                        if ($pending_count_query) {
                            $pending_count = $pending_count_query->fetch_assoc()['count'];
                            if ($pending_count > 0) {
                                echo '<span class="absolute -top-2 -right-2 text-xs bg-red-500 text-white font-bold rounded-full px-1.5 py-0.5">' . $pending_count . '</span>';
                            }
                        }
                    ?>
                </a>
                
                <!-- Logout Icon -->
                <a href="setting.php?action=logout" class="text-red-400 hover:text-red-500" title="Logout">
                    <i class="fas fa-sign-out-alt fa-lg"></i>
                </a>
            </div>
        </header>
        <main class="p-4">