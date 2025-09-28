<?php
require_once 'config.php';

// --- NEW: REAL-TIME BLOCK CHECK ON EVERY PAGE LOAD ---
// This check runs for any user who has an active session.
if (isset($_SESSION['user_id'])) {
    // Check the user's current status in the database
    $check_status_stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $check_status_stmt->bind_param("i", $_SESSION['user_id']);
    $check_status_stmt->execute();
    $status_result = $check_status_stmt->get_result()->fetch_assoc();

    // If the database status is 'blocked'
    if ($status_result && $status_result['status'] == 'blocked') {
        // Step 1: Store the specific block message in a temporary "flash" session variable.
        $_SESSION['block_message'] = "You have been blocked for violation of fair play.";
        
        // Step 2: Destroy the rest of the session to log them out completely.
        session_destroy();
        
        // Step 3: Redirect them to the login page.
        header("Location: login.php");
        exit(); // Stop the script immediately.
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Arena Victor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { -webkit-tap-highlight-color: transparent; } </style>
</head>
<body class="bg-gray-900 text-white select-none">
    <div class="container mx-auto max-w-lg min-h-screen bg-gray-800 pb-20">
        <header class="p-4 flex justify-between items-center bg-gray-900 sticky top-0 z-10 shadow-md">
            <h1 class="text-2xl font-bold">Arena Victor</h1>
            <?php if (isset($_SESSION['user_id'])):
                $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            ?>
            <div class="text-lg bg-gray-700 px-3 py-1 rounded-full">
                <i class="fas fa-wallet text-yellow-400"></i> ₹<?php echo number_format($user['wallet_balance'], 2); ?>
            </div>
            <?php endif; ?>
        </header>
        <main class="p-4">