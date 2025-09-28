<?php
require_once 'common/config.php'; // Step 1: Config for session and DB

// Step 2: Security Check BEFORE any HTML
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Step 3: Now that security is checked, include the header which starts HTML output
require_once 'common/header.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_deposit'])) {
    $amount = trim($_POST['amount']);
    $transaction_id = trim($_POST['transaction_id']);
    if (empty($amount) || !is_numeric($amount) || $amount <= 0 || empty($transaction_id)) {
        $error = "Please enter a valid amount and transaction ID.";
    } else {
        $stmt = $conn->prepare("INSERT INTO deposits (user_id, amount, transaction_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $user_id, $amount, $transaction_id);
        if ($stmt->execute()) { $success = "Deposit request submitted! It will be reviewed by an admin shortly."; } 
        else { $error = "Failed to submit request. Please try again."; }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_withdrawal'])) {
    $amount = trim($_POST['amount']);
    $stmt = $conn->prepare("SELECT wallet_balance, upi_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) { $error = "Please enter a valid amount to withdraw."; } 
    elseif (empty($user['upi_id'])) { $error = "Please save your UPI ID in your profile before making a withdrawal request."; } 
    elseif ($amount > $user['wallet_balance']) { $error = "Withdrawal amount cannot be greater than your current balance."; } 
    else {
        $conn->begin_transaction();
        try {
            $new_balance = $user['wallet_balance'] - $amount;
            $stmt1 = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt1->bind_param("di", $new_balance, $user_id);
            $stmt1->execute();
            $stmt2 = $conn->prepare("INSERT INTO withdrawals (user_id, amount, upi_id) VALUES (?, ?, ?)");
            $stmt2->bind_param("ids", $user_id, $amount, $user['upi_id']);
            $stmt2->execute();
            $conn->commit();
            $success = "Withdrawal request submitted! It will be processed by an admin shortly.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "An error occurred. Please try again.";
        }
    }
}

$stmt = $conn->prepare("SELECT wallet_balance, upi_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$wallet_balance = $user_data['wallet_balance'];
$user_upi_id = $user_data['upi_id'];
$stmt->close();

$stmt_trans = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt_trans->bind_param("i", $user_id);
$stmt_trans->execute();
$transactions_result = $stmt_trans->get_result();

$stmt_admin = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('admin_upi_id', 'admin_qr_code')");
$admin_settings = [];
if ($stmt_admin) { while($row = $stmt_admin->fetch_assoc()) { $admin_settings[$row['setting_key']] = $row['setting_value']; } }
?>

<div class="space-y-6">
    <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded-lg mb-4 text-sm"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded-lg mb-4 text-sm"><?php echo $success; ?></div><?php endif; ?>

    <div class="bg-gradient-to-r from-indigo-600 to-blue-500 rounded-xl shadow-lg p-6 text-white text-center"><h2 class="text-lg font-medium text-indigo-200">Current Balance</h2><p class="text-5xl font-bold mt-2">₹<?php echo number_format($wallet_balance, 2); ?></p></div>
    <div class="grid grid-cols-2 gap-4">
        <button onclick="toggleModal('depositModal')" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg"><i class="fas fa-plus-circle mr-2"></i> Add Money</button>
        <button onclick="toggleModal('withdrawModal')" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-4 rounded-lg"><i class="fas fa-arrow-circle-down mr-2"></i> Withdraw</button>
    </div>
    <div>
        <h2 class="text-xl font-semibold mb-4">Transaction History</h2>
        <div class="space-y-3">
            <?php if ($transactions_result->num_rows > 0): while ($transaction = $transactions_result->fetch_assoc()): ?>
                <div class="bg-gray-700 p-4 rounded-lg flex justify-between items-center"><div><p class="font-semibold"><?php echo htmlspecialchars($transaction['description']); ?></p><p class="text-xs text-gray-400"><?php echo date('d M Y, h:i A', strtotime($transaction['created_at'])); ?></p></div><div class="text-right"><p class="font-bold <?php echo $transaction['type'] == 'credit' ? 'text-green-400' : 'text-red-400'; ?>"><?php echo $transaction['type'] == 'credit' ? '+' : '-'; ?> ₹<?php echo number_format($transaction['amount'], 2); ?></p></div></div>
            <?php endwhile; else: ?>
                <div class="text-center py-10"><p class="text-gray-400">No transactions yet.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="depositModal" class="fixed inset-0 bg-black bg-opacity-70 z-50 hidden flex justify-center items-center p-4 overflow-y-auto"><div class="bg-gray-800 p-6 rounded-lg shadow-xl w-full max-w-md my-auto"><h2 class="text-2xl font-bold mb-4">Add Money</h2><?php if (empty($admin_settings['admin_upi_id']) && empty($admin_settings['admin_qr_code'])): ?><p class="text-center text-yellow-400">The admin has not set up payment details yet. Please try again later.</p><button type="button" onclick="toggleModal('depositModal')" class="mt-4 w-full bg-gray-600 hover:bg-gray-700 p-3 rounded font-bold">Close</button><?php else: ?><div class="text-center space-y-4"><?php if (!empty($admin_settings['admin_qr_code'])): ?><p>Scan the QR Code to Pay</p><img src="uploads/<?php echo htmlspecialchars($admin_settings['admin_qr_code']); ?>" alt="Admin QR Code" class="mx-auto rounded-lg border-2 border-gray-600"><?php endif; ?><?php if (!empty($admin_settings['admin_upi_id'])): ?><p>Or pay to UPI ID:</p><p class="font-mono bg-gray-700 p-2 rounded break-words"><?php echo htmlspecialchars($admin_settings['admin_upi_id']); ?></p><?php endif; ?></div><hr class="my-4 border-gray-600"><p class="text-sm text-yellow-400 mb-4">After paying, fill the form below to submit your request.</p><form action="wallet.php" method="POST" class="space-y-4"><input type="hidden" name="request_deposit" value="1"><div><label for="amount" class="block text-sm">Amount Paid (₹)</label><input type="number" step="0.01" name="amount" required class="w-full p-2 mt-1 bg-gray-700 rounded border border-gray-600"></div><div><label for="transaction_id" class="block text-sm">UPI Transaction ID</label><input type="text" name="transaction_id" required class="w-full p-2 mt-1 bg-gray-700 rounded border border-gray-600"></div><div class="flex gap-4 mt-4"><button type="button" onclick="toggleModal('depositModal')" class="w-full bg-gray-600 hover:bg-gray-700 p-3 rounded font-bold">Cancel</button><button type="submit" class="w-full bg-green-600 hover:bg-green-700 p-3 rounded font-bold">Submit Request</button></div></form><?php endif; ?></div></div>
<div id="withdrawModal" class="fixed inset-0 bg-black bg-opacity-70 z-50 hidden flex justify-center items-center p-4 overflow-y-auto"><div class="bg-gray-800 p-6 rounded-lg shadow-xl w-full max-w-md my-auto"><h2 class="text-2xl font-bold mb-4">Request Withdrawal</h2><?php if(empty($user_upi_id)): ?><div class="bg-red-500 p-4 rounded-lg text-white text-center"><p>Please add your UPI ID in the <a href="profile.php" class="font-bold underline">Profile</a> page.</p><button type="button" onclick="toggleModal('withdrawModal')" class="mt-4 w-full bg-gray-600 hover:bg-gray-700 p-3 rounded font-bold">Close</button></div><?php else: ?><p class="text-sm mb-2">Funds will be sent to your saved UPI ID:</p><p class="font-mono bg-gray-700 p-2 rounded mb-4 break-words"><?php echo htmlspecialchars($user_upi_id); ?></p><form action="wallet.php" method="POST" class="space-y-4"><input type="hidden" name="request_withdrawal" value="1"><div><label for="amount_withdraw" class="block text-sm">Amount to Withdraw (₹)</label><input type="number" step="0.01" name="amount" id="amount_withdraw" required class="w-full p-2 mt-1 bg-gray-700 rounded border border-gray-600"></div><div class="flex gap-4 mt-4"><button type="button" onclick="toggleModal('withdrawModal')" class="w-full bg-gray-600 hover:bg-gray-700 p-3 rounded font-bold">Cancel</button><button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 p-3 rounded font-bold">Submit Request</button></div></form><?php endif; ?></div></div>

<script>function toggleModal(modalID) { document.getElementById(modalID).classList.toggle('hidden'); }</script>

<?php require_once 'common/bottom.php'; ?>