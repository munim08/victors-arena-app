<?php
require_once '../common/config.php';
$error = '';

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Arena Victor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>body { -webkit-tap-highlight-color: transparent; }</style>
</head>
<body class="bg-gray-900 text-white select-none">
    <div class="container mx-auto max-w-lg min-h-screen bg-gray-800 flex items-center justify-center p-4">
        <div class="w-full">
            <h1 class="text-3xl font-bold text-center mb-6"><i class="fas fa-user-shield mr-2"></i>Admin Login</h1>
            <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded mb-4 text-center"><?php echo $error; ?></div><?php endif; ?>
            <form action="login.php" method="POST" class="bg-gray-700 p-6 rounded-lg shadow-lg">
                <div class="mb-4">
                    <label for="username" class="block mb-2">Username</label>
                    <input type="text" name="username" class="w-full p-3 bg-gray-800 rounded border border-gray-600 focus:outline-none focus:border-indigo-500">
                </div>
                <div class="mb-6">
                    <label for="password" class="block mb-2">Password</label>
                    <input type="password" name="password" class="w-full p-3 bg-gray-800 rounded border border-gray-600 focus:outline-none focus:border-indigo-500">
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 p-3 rounded-lg font-bold">Login</button>
            </form>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>