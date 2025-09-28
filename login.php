<?php
require_once 'common/config.php';
$error = '';
$success = '';

// If user is already logged in, redirect them away from the login page
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check for a block message that gets set when a user is forcibly logged out
if (isset($_SESSION['block_message'])) {
    $error = $_SESSION['block_message'];
    unset($_SESSION['block_message']);
}

// Handle form submissions for login and signup
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle Login
    if (isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        if (empty($username) || empty($password)) {
            $error = "All fields are required.";
        } else {
            $stmt = $conn->prepare("SELECT id, password, status FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if ($user['status'] == 'blocked') {
                    $error = "Your account has been blocked. Please contact support.";
                } elseif (password_verify($password, $user['password'])) {
                    session_regenerate_id(true); // Security measure
                    $_SESSION['user_id'] = $user['id'];
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        }
    } 
    // Handle Signup
    elseif (isset($_POST['signup'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        if (empty($username) || empty($email) || empty($password)) {
            $error = "All fields are required for signup.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = "Username or email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("sss", $username, $email, $hashed_password);
                if ($stmt_insert->execute()) {
                    $success = "Registration successful! Please login.";
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        }
    }
} // This was the missing closing brace
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Arena Victor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>body { -webkit-tap-highlight-color: transparent; }</style>
</head>
<body class="bg-gray-900 text-white select-none">
    <div class="container mx-auto max-w-lg min-h-screen bg-gray-800 p-4">
        <h1 class="text-3xl font-bold text-center mb-6">Arena Victor</h1>
        <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded mb-4 text-sm text-center"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded mb-4 text-sm text-center"><?php echo $success; ?></div><?php endif; ?>

        <div x-data="{ tab: 'login' }">
            <div class="flex border-b border-gray-700">
                <button @click="tab = 'login'" :class="{'bg-gray-700': tab === 'login'}" class="flex-1 py-2 px-4 focus:outline-none rounded-t-lg">Login</button>
                <button @click="tab = 'signup'" :class="{'bg-gray-700': tab === 'signup'}" class="flex-1 py-2 px-4 focus:outline-none rounded-t-lg">Sign Up</button>
            </div>

            <div x-show="tab === 'login'" class="pt-6">
                <form action="login.php" method="POST" novalidate>
                    <input type="hidden" name="login" value="1">
                    <div class="mb-4"><label for="username" class="block mb-2">Username</label><input type="text" name="username" class="w-full p-2 bg-gray-700 rounded border border-gray-600 focus:outline-none focus:border-indigo-500"></div>
                    <div class="mb-4"><label for="password" class="block mb-2">Password</label><input type="password" name="password" class="w-full p-2 bg-gray-700 rounded border border-gray-600 focus:outline-none focus:border-indigo-500"></div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 p-3 rounded font-bold">Login</button>
                </form>
            </div>

            <div x-show="tab === 'signup'" class="pt-6" style="display: none;">
                <form action="login.php" method="POST" novalidate>
                    <input type="hidden" name="signup" value="1">
                    <div class="mb-4"><label class="block mb-2">Username</label><input type="text" name="username" required class="w-full p-2 bg-gray-700 rounded border border-gray-600"></div>
                    <div class="mb-4"><label class="block mb-2">Email</label><input type="email" name="email" required class="w-full p-2 bg-gray-700 rounded border border-gray-600"></div>
                    <div class="mb-4"><label class="block mb-2">Password</label><input type="password" name="password" required class="w-full p-2 bg-gray-700 rounded border border-gray-600"></div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 p-3 rounded font-bold">Sign Up</button>
                </form>
            </div>
        </div>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>