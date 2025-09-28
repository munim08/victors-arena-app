<?php
// Step 1: Include config for session and DB connection
require_once 'common/config.php';

// Step 2: Perform all security checks and potential redirects BEFORE any HTML output
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Step 3: Now it's safe to include the header, which starts the HTML
require_once 'common/header.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($subject) || empty($message)) {
        $error = "Subject and message cannot be empty.";
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (user_id, subject, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $subject, $message);
        if ($stmt->execute()) {
            $success = "Your message has been sent successfully! An admin will review it shortly.";
        } else {
            $error = "There was an error sending your message. Please try again.";
        }
    }
}
?>

<div class="space-y-4">
    <a href="profile.php" class="text-indigo-400 hover:text-indigo-300 text-sm">&larr; Back to Profile</a>
    <h2 class="text-2xl font-bold">Contact Support</h2>

    <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded-lg text-sm my-4"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded-lg text-sm my-4"><?php echo $success; ?></div><?php endif; ?>

    <?php if (!$success): // Hide form after successful submission ?>
    <form action="contact.php" method="POST" class="bg-gray-700 p-4 rounded-lg space-y-4">
        <div>
            <label for="subject" class="block text-sm font-medium text-gray-300">Subject</label>
            <input type="text" name="subject" id="subject" required placeholder="e.g., Deposit Issue ID #123" class="mt-1 block w-full bg-gray-800 text-white rounded-md p-2">
        </div>
        <div>
            <label for="message" class="block text-sm font-medium text-gray-300">Message</label>
            <textarea name="message" id="message" required rows="6" placeholder="Please describe your issue in detail..." class="mt-1 block w-full bg-gray-800 text-white rounded-md p-2"></textarea>
        </div>
        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-md">
            Send Message
        </button>
    </form>
    <?php endif; ?>
</div>

<?php require_once 'common/bottom.php'; ?>