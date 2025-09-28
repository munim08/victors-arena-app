<?php
require_once 'common/header.php';

$success_msg = '';
$error_msg = '';

// Handle Block/Unblock/Reset actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id_to_action = intval($_POST['user_id']);
    
    // Block User
    if (isset($_POST['block_user'])) {
        $stmt = $conn->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
        $stmt->bind_param("i", $user_id_to_action);
        if ($stmt->execute()) { $success_msg = "User has been blocked."; } 
        else { $error_msg = "Failed to block user."; }
    } 
    // Unblock User
    elseif (isset($_POST['unblock_user'])) {
        $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $user_id_to_action);
        if ($stmt->execute()) { $success_msg = "User has been unblocked."; } 
        else { $error_msg = "Failed to unblock user."; }
    }
    // --- NEW: Reset Password ---
    elseif (isset($_POST['reset_password'])) {
        $new_password = 'password123'; // The default reset password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id_to_action);
        if ($stmt->execute()) {
            $success_msg = "User's password has been reset to: <strong>password123</strong>";
        } else {
            $error_msg = "Failed to reset password.";
        }
    }
}

// Fetch all users
$users_result = $conn->query("SELECT id, username, email, wallet_balance, status FROM users ORDER BY created_at DESC");
?>

<div class="space-y-6">
    <h2 class="text-2xl font-bold">Manage Users</h2>
    <?php if ($error_msg): ?><div class="bg-red-500 text-white p-3 rounded-lg text-sm my-4"><?php echo $error_msg; ?></div><?php endif; ?>
    <?php if ($success_msg): ?><div class="bg-green-500 text-white p-3 rounded-lg text-sm my-4"><?php echo $success_msg; ?></div><?php endif; ?>

    <div class="space-y-3">
        <?php if ($users_result->num_rows > 0): while ($user = $users_result->fetch_assoc()): ?>
            <div class="bg-gray-700 p-4 rounded-lg">
                <div class="flex justify-between items-center">
                    <div><p class="font-bold text-lg"><?php echo htmlspecialchars($user['username']); ?></p><p class="text-sm text-gray-400"><?php echo htmlspecialchars($user['email']); ?></p></div>
                    <div class="text-right"><p class="font-semibold text-lg text-green-400">₹<?php echo number_format($user['wallet_balance'], 2); ?></p><span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo ($user['status'] == 'blocked' ? 'bg-red-500' : 'bg-green-500'); ?>"><?php echo ucfirst($user['status']); ?></span></div>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <!-- Block/Unblock Form -->
                    <form action="user.php" method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <?php if ($user['status'] == 'active'): ?>
                            <button type="submit" name="block_user" class="w-full text-center bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 rounded-md text-sm"><i class="fas fa-ban mr-1"></i> Block</button>
                        <?php else: ?>
                            <button type="submit" name="unblock_user" class="w-full text-center bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-3 rounded-md text-sm"><i class="fas fa-check-circle mr-1"></i> Unblock</button>
                        <?php endif; ?>
                    </form>
                    <!-- Reset Password Form -->
                    <form action="user.php" method="POST" onsubmit="return confirm('Are you sure you want to reset this user\'s password to \'password123\'?');">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" name="reset_password" class="w-full text-center bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-3 rounded-md text-sm"><i class="fas fa-key mr-1"></i> Reset Pass</button>
                    </form>
                </div>
            </div>
        <?php endwhile; endif; ?>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>