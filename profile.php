<?php
require_once 'common/config.php';

// Security check must come before any HTML output
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle the logout action
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Now it's safe to include the header, which starts the HTML
require_once 'common/header.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle all form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle Profile Information Update
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']); $email = trim($_POST['email']);
        if (empty($username) || empty($email)) { $error = "Username and Email cannot be empty."; } 
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = "Invalid email format."; } 
        else {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt_check->bind_param("ssi", $username, $email, $user_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) { $error = "Username or email is already taken."; } 
            else {
                $stmt_update = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt_update->bind_param("ssi", $username, $email, $user_id);
                if ($stmt_update->execute()) { $success = "Profile updated successfully!"; } 
                else { $error = "Failed to update profile."; }
            }
        }
    }
    // Handle UPI ID Update
    if (isset($_POST['update_upi'])) {
        $upi_id = trim($_POST['upi_id']);
        if (empty($upi_id)) { $error = "UPI ID cannot be empty."; } 
        else {
            $stmt = $conn->prepare("UPDATE users SET upi_id = ? WHERE id = ?");
            $stmt->bind_param("si", $upi_id, $user_id);
            if ($stmt->execute()) { $success = "UPI ID updated successfully!"; } 
            else { $error = "Failed to update UPI ID."; }
        }
    }
    // Handle Password Change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password']; $new_password = $_POST['new_password']; $confirm_password = $_POST['confirm_password'];
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) { $error = "All password fields are required."; } 
        elseif ($new_password !== $confirm_password) { $error = "New passwords do not match."; } 
        else {
            $stmt_pass = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt_pass->bind_param("i", $user_id); $stmt_pass->execute();
            $user_pass = $stmt_pass->get_result()->fetch_assoc();
            if (password_verify($current_password, $user_pass['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_update_pass->bind_param("si", $hashed_password, $user_id);
                if ($stmt_update_pass->execute()) { $success = "Password changed successfully!"; } 
                else { $error = "Failed to change password."; }
            } else { $error = "Incorrect current password."; }
        }
    }
}

// Fetch current user data to display in the forms
$stmt_user = $conn->prepare("SELECT username, email, upi_id FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
?>

<div class="space-y-8">
    <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded-lg text-sm text-center"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded-lg text-sm text-center"><?php echo $success; ?></div><?php endif; ?>

    <!-- Edit Profile Section -->
    <div>
        <h2 class="text-xl font-semibold mb-4">Edit Profile</h2>
        <form action="profile.php" method="POST" class="space-y-4 bg-gray-700 p-4 rounded-lg">
            <input type="hidden" name="update_profile" value="1">
            <div><label for="username" class="block text-sm">Username</label><input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required class="mt-1 block w-full bg-gray-800 rounded-md p-2"></div>
            <div><label for="email" class="block text-sm">Email</label><input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="mt-1 block w-full bg-gray-800 rounded-md p-2"></div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg">Update Profile</button>
        </form>
    </div>

    <!-- Payment Details (UPI ID) Section -->
    <div>
        <h2 class="text-xl font-semibold mb-4">Payment Details</h2>
        <form action="profile.php" method="POST" class="space-y-4 bg-gray-700 p-4 rounded-lg">
            <input type="hidden" name="update_upi" value="1">
            <div><label for="upi_id" class="block text-sm">Your UPI ID</label><input type="text" name="upi_id" id="upi_id" value="<?php echo htmlspecialchars($user['upi_id'] ?? ''); ?>" placeholder="yourname@bank" required class="mt-1 block w-full bg-gray-800 rounded-md p-2"></div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg">Save UPI ID</button>
        </form>
    </div>
    
    <!-- Contact & About Sections -->
    <div>
        <h2 class="text-xl font-semibold mb-4">Support & Info</h2>
        <div class="bg-gray-700 p-4 rounded-lg space-y-4">
            <div>
                <a href="contact.php" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg"><i class="fas fa-headset mr-2"></i> Contact Support</a>
            </div>
            <div class="text-sm text-gray-300 space-y-2 pt-4 border-t border-gray-600">
                <p><strong>Developer:</strong> Munim Yeasin</p>
                <div class="flex items-center"><i class="fab fa-instagram mr-2"></i><span><a href="https://www.instagram.com/__yeasin_0" target="_blank" class="text-indigo-400 hover:underline">__yeasin_0</a>, <a href="https://www.instagram.com/levi_exe_0" target="_blank" class="text-indigo-400 hover:underline">levi_exe_0</a></span></div>
            </div>
        </div>
    </div>
    
    <!-- Change Password Section -->
    <div>
        <h2 class="text-xl font-semibold mb-4">Change Password</h2>
        <form action="profile.php" method="POST" class="space-y-4 bg-gray-700 p-4 rounded-lg">
            <input type="hidden" name="change_password" value="1">
            <div><label for="current_password" class="block text-sm">Current Password</label><input type="password" name="current_password" id="current_password" required class="mt-1 block w-full bg-gray-800 rounded-md p-2"></div>
            <div><label for="new_password" class="block text-sm">New Password</label><input type="password" name="new_password" id="new_password" required class="mt-1 block w-full bg-gray-800 rounded-md p-2"></div>
            <div><label for="confirm_password" class="block text-sm">Confirm New Password</label><input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full bg-gray-800 rounded-md p-2"></div>
            <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-4 rounded-lg">Change Password</button>
        </form>
    </div>

    <!-- === THIS IS THE LOGOUT BUTTON === -->
    <div class="pt-4">
        <a href="profile.php?action=logout" class="block w-full text-center bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>