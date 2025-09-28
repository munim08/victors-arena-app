<?php
require_once 'common/header.php';

$error = '';
$success = '';
$admin_id = $_SESSION['admin_id'];

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Both password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $admin_id);
        if ($stmt->execute()) {
            $success = "Admin password updated successfully!";
        } else {
            $error = "Failed to update password.";
        }
        $stmt->close();
    }
}

// Fetch current admin username
$admin_user = $conn->query("SELECT username FROM admin WHERE id = $admin_id")->fetch_assoc();
?>

<div class="space-y-6">
    <h2 class="text-2xl font-bold">Admin Settings</h2>

    <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded-lg"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded-lg"><?php echo $success; ?></div><?php endif; ?>
    
    <!-- Admin Info -->
    <div class="bg-gray-700 p-4 rounded-lg">
        <h3 class="font-semibold text-lg">Admin Information</h3>
        <p class="mt-2 text-gray-300">Username: <span class="font-bold text-white"><?php echo htmlspecialchars($admin_user['username']); ?></span></p>
        <p class="text-xs text-gray-400 mt-1">Username cannot be changed from this panel.</p>
    </div>

    <!-- Change Password Form -->
    <form action="setting.php" method="POST" class="bg-gray-700 p-4 rounded-lg space-y-4">
        <input type="hidden" name="change_password" value="1">
        <h3 class="font-semibold text-lg">Change Password</h3>
        <div>
            <label for="new_password" class="block text-sm">New Password</label>
            <input type="password" name="new_password" required class="mt-1 block w-full bg-gray-800 border-gray-600 rounded-md p-2">
        </div>
        <div>
            <label for="confirm_password" class="block text-sm">Confirm New Password</label>
            <input type="password" name="confirm_password" required class="mt-1 block w-full bg-gray-800 border-gray-600 rounded-md p-2">
        </div>
        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-md">Update Password</button>
    </form>

     <!-- Logout Button -->
    <div class="pt-4">
        <a href="setting.php?action=logout" class="block w-full text-center bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>