<?php
require_once 'common/header.php';

$success_msg = '';
$error_msg = '';

// Handle Admin Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    if (isset($_POST['complete'])) {
        $conn->begin_transaction();
        try {
            $description = "Withdrawal completed (Request ID: #$request_id)";
            $stmt1 = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
            $stmt1->bind_param("ids", $user_id, $amount, $description);
            $stmt1->execute();
            $stmt2 = $conn->prepare("UPDATE withdrawals SET status = 'Completed' WHERE id = ?");
            $stmt2->bind_param("i", $request_id);
            $stmt2->execute();
            $conn->commit();
            $success_msg = "Withdrawal marked as completed!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Transaction failed: " . $e->getMessage();
        }
    }
}

// Query 1: Fetch PENDING requests
$pending_requests = $conn->query("
    SELECT w.*, u.username 
    FROM withdrawals w JOIN users u ON w.user_id = u.id 
    WHERE w.status = 'Pending' 
    ORDER BY w.created_at ASC
");

// Query 2: Fetch RECENT HISTORY (Completed in the last 7 days)
$history_requests = $conn->query("
    SELECT w.*, u.username 
    FROM withdrawals w JOIN users u ON w.user_id = u.id 
    WHERE w.status != 'Pending' AND w.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY w.created_at DESC
");
?>

<div class="space-y-6">
    <!-- Section for Pending Requests -->
    <div>
        <h2 class="text-2xl font-bold">Pending Withdrawal Requests</h2>
        <?php if ($error_msg): ?><div class="bg-red-500 text-white p-3 rounded-lg text-sm my-4"><?php echo $error_msg; ?></div><?php endif; ?>
        <?php if ($success_msg): ?><div class="bg-green-500 text-white p-3 rounded-lg text-sm my-4"><?php echo $success_msg; ?></div><?php endif; ?>
        <div class="space-y-4 mt-4">
            <?php if ($pending_requests && $pending_requests->num_rows > 0): ?>
                <?php while($req = $pending_requests->fetch_assoc()): ?>
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <span class="font-semibold text-gray-400">User:</span><span><?php echo htmlspecialchars($req['username']); ?></span>
                            <span class="font-semibold text-gray-400">Amount:</span><span class="font-bold text-yellow-400">₹<?php echo number_format($req['amount']); ?></span>
                            <span class="font-semibold text-gray-400">User UPI ID:</span><span class="break-words"><?php echo htmlspecialchars($req['upi_id']); ?></span>
                            <span class="font-semibold text-gray-400">Time:</span><span><?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?></span>
                        </div>
                        <form method="POST" action="withdrawal_requests.php" class="mt-4">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>"><input type="hidden" name="user_id" value="<?php echo $req['user_id']; ?>"><input type="hidden" name="amount" value="<?php echo $req['amount']; ?>">
                            <button type="submit" name="complete" class="w-full bg-green-600 hover:bg-green-700 p-2 rounded font-bold text-sm">Mark as Completed</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-400 text-center py-8">No pending withdrawal requests found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section for Recent History -->
    <div class="mt-10">
        <h2 class="text-2xl font-bold">Recent History (Last 7 Days)</h2>
        <div class="space-y-4 mt-4">
            <?php if ($history_requests && $history_requests->num_rows > 0): ?>
                <?php while($req = $history_requests->fetch_assoc()): ?>
                     <div class="bg-gray-700 p-4 rounded-lg opacity-70">
                        <div class="flex justify-between items-start">
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                <span class="font-semibold text-gray-400">User:</span><span><?php echo htmlspecialchars($req['username']); ?></span>
                                <span class="font-semibold text-gray-400">Amount:</span><span>₹<?php echo number_format($req['amount']); ?></span>
                                <span class="font-semibold text-gray-400">User UPI ID:</span><span class="break-words"><?php echo htmlspecialchars($req['upi_id']); ?></span>
                                <span class="font-semibold text-gray-400">Time:</span><span><?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?></span>
                            </div>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full bg-green-500"><?php echo $req['status']; ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-400 text-center py-8">No history found for the last 7 days.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>