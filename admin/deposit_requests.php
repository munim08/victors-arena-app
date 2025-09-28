<?php
require_once 'common/header.php';

$success_msg = '';
$error_msg = '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle Admin Actions (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    // ... (The existing PHP logic for approving/rejecting remains exactly the same) ...
    $request_id = intval($_POST['request_id']);
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    if (isset($_POST['approve'])) {
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt1->bind_param("di", $amount, $user_id);
            $stmt1->execute();
            $desc = "Deposit approved (Request ID: #$request_id)";
            $stmt2 = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', ?)");
            $stmt2->bind_param("ids", $user_id, $amount, $desc);
            $stmt2->execute();
            $stmt3 = $conn->prepare("UPDATE deposits SET status = 'Completed' WHERE id = ?");
            $stmt3->bind_param("i", $request_id);
            $stmt3->execute();
            $conn->commit();
            $success_msg = "Request approved and balance updated!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Transaction failed: " . $e->getMessage();
        }
    } elseif (isset($_POST['reject'])) {
        $stmt = $conn->prepare("UPDATE deposits SET status = 'Rejected' WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $success_msg = "Request has been rejected.";
    }
}

// --- UPDATED QUERY 1: Fetch PENDING requests with search functionality ---
$sql_pending = "
    SELECT d.*, u.username 
    FROM deposits d JOIN users u ON d.user_id = u.id 
    WHERE d.status = 'Pending'
";
if (!empty($search_term)) {
    $sql_pending .= " AND d.transaction_id LIKE ?";
    $like_search_term = "%" . $search_term . "%";
}
$sql_pending .= " ORDER BY d.created_at ASC";

$stmt_pending = $conn->prepare($sql_pending);
if (!empty($search_term)) {
    $stmt_pending->bind_param("s", $like_search_term);
}
$stmt_pending->execute();
$pending_requests = $stmt_pending->get_result();


// Query 2: Fetch RECENT HISTORY (no changes needed here)
$history_requests = $conn->query("
    SELECT d.*, u.username FROM deposits d JOIN users u ON d.user_id = u.id 
    WHERE d.status != 'Pending' AND d.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY d.created_at DESC
");
?>

<div class="space-y-6">
    <!-- Section for Pending Requests -->
    <div>
        <h2 class="text-2xl font-bold">Pending Deposit Requests</h2>
        <?php if ($error_msg): ?><div class="bg-red-500 text-white p-3 rounded-lg text-sm my-4"><?php echo $error_msg; ?></div><?php endif; ?>
        <?php if ($success_msg): ?><div class="bg-green-500 text-white p-3 rounded-lg text-sm my-4"><?php echo $success_msg; ?></div><?php endif; ?>
        
        <!-- === NEW SEARCH FORM === -->
        <form action="deposit_requests.php" method="GET" class="mt-4">
            <div class="flex">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search by Transaction ID..." class="w-full bg-gray-700 text-white p-2 rounded-l-lg focus:outline-none">
                <button type="submit" class="bg-indigo-600 text-white p-2 rounded-r-lg"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <div class="space-y-4 mt-4">
            <?php if ($pending_requests && $pending_requests->num_rows > 0): ?>
                <?php while($req = $pending_requests->fetch_assoc()): ?>
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <span class="font-semibold text-gray-400">User:</span><span><?php echo htmlspecialchars($req['username']); ?></span>
                            <span class="font-semibold text-gray-400">Amount:</span><span class="font-bold text-green-400">₹<?php echo number_format($req['amount']); ?></span>
                            <span class="font-semibold text-gray-400">Transaction ID:</span><span class="break-words"><?php echo htmlspecialchars($req['transaction_id']); ?></span>
                            <span class="font-semibold text-gray-400">Time:</span><span><?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?></span>
                        </div>
                        <form method="POST" action="deposit_requests.php" class="flex gap-4 mt-4">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>"><input type="hidden" name="user_id" value="<?php echo $req['user_id']; ?>"><input type="hidden" name="amount" value="<?php echo $req['amount']; ?>">
                            <button type="submit" name="approve" class="w-full bg-green-600 hover:bg-green-700 p-2 rounded font-bold text-sm">Approve</button>
                            <button type="submit" name="reject" class="w-full bg-red-600 hover:bg-red-700 p-2 rounded font-bold text-sm">Reject</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-400 text-center py-8">No pending deposit requests found<?php echo !empty($search_term) ? ' for your search "' . htmlspecialchars($search_term) . '"' : '.'; ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section for Recent History -->
    <div class="mt-10">
        <!-- ... (The code for the history section remains exactly the same) ... -->
        <h2 class="text-2xl font-bold">Recent History (Last 7 Days)</h2>
        <div class="space-y-4 mt-4">
            <?php if ($history_requests && $history_requests->num_rows > 0): ?>
                <?php while($req = $history_requests->fetch_assoc()): ?>
                    <div class="bg-gray-700 p-4 rounded-lg opacity-70">
                        <div class="flex justify-between items-start">
                             <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                <span class="font-semibold text-gray-400">User:</span><span><?php echo htmlspecialchars($req['username']); ?></span>
                                <span class="font-semibold text-gray-400">Amount:</span><span>₹<?php echo number_format($req['amount']); ?></span>
                                <span class="font-semibold text-gray-400">Transaction ID:</span><span class="break-words"><?php echo htmlspecialchars($req['transaction_id']); ?></span>
                                <span class="font-semibold text-gray-400">Time:</span><span><?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?></span>
                            </div>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo ($req['status'] == 'Completed' ? 'bg-green-500' : 'bg-red-500'); ?>"><?php echo $req['status']; ?></span>
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