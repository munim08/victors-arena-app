<?php
require_once 'common/header.php';
$message_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$message_id) { header("Location: messages.php"); exit(); }

// Mark the message as 'Read' as soon as the admin opens it
$conn->query("UPDATE contact_messages SET status = 'Read' WHERE id = $message_id");

// Fetch the message details
$msg_result = $conn->query("
    SELECT cm.*, u.username, u.email 
    FROM contact_messages cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.id = $message_id
");
$message = $msg_result->fetch_assoc();
?>
<div class="space-y-4">
    <a href="messages.php" class="text-indigo-400 hover:text-indigo-300 text-sm">&larr; Back to All Messages</a>
    <div>
        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($message['subject']); ?></h2>
        <div class="text-sm text-gray-400 mt-1">
            <p><strong>From:</strong> <?php echo htmlspecialchars($message['username']); ?> (<?php echo htmlspecialchars($message['email']); ?>)</p>
            <p><strong>Received:</strong> <?php echo date('d M Y, h:i A', strtotime($message['created_at'])); ?></p>
        </div>
    </div>
    <div class="bg-gray-700 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-2 pb-2 border-b border-gray-600">Message</h3>
        <p class="text-gray-300 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
    </div>
</div>
<?php require_once 'common/bottom.php'; ?>