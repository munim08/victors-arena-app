<?php
require_once 'common/header.php';

// Fetch all messages, joining with users table to get the username
$messages_result = $conn->query("
    SELECT cm.*, u.username 
    FROM contact_messages cm
    JOIN users u ON cm.user_id = u.id
    ORDER BY cm.created_at DESC
");
?>
<div class="space-y-6">
    <h2 class="text-2xl font-bold">User Messages</h2>
    <div class="space-y-4">
        <?php if ($messages_result && $messages_result->num_rows > 0): ?>
            <?php while($msg = $messages_result->fetch_assoc()): ?>
                <a href="view_message.php?id=<?php echo $msg['id']; ?>" class="block bg-gray-700 p-4 rounded-lg hover:bg-gray-600">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold <?php if($msg['status'] == 'Pending') echo 'text-yellow-400'; ?>">
                                <?php echo htmlspecialchars($msg['subject']); ?>
                            </p>
                            <p class="text-sm text-gray-300">From: <?php echo htmlspecialchars($msg['username']); ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo date('d M Y, h:i A', strtotime($msg['created_at'])); ?></p>
                        </div>
                        <?php if($msg['status'] == 'Pending'): ?>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full bg-yellow-500 text-yellow-900">New</span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-gray-400 text-center py-8">No messages found.</p>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'common/bottom.php'; ?>