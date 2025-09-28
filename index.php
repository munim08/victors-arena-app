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

// Handle Joining a Tournament
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['join_tournament'])) {
    $tournament_id = intval($_POST['tournament_id']);
    
    $conn->begin_transaction();
    try {
        $stmt_tourney = $conn->prepare("SELECT entry_fee, max_slots FROM tournaments WHERE id = ? AND status = 'Upcoming' FOR UPDATE");
        $stmt_tourney->bind_param("i", $tournament_id);
        $stmt_tourney->execute();
        $tournament_result = $stmt_tourney->get_result();
        
        if ($tournament_result->num_rows === 0) { throw new Exception("This tournament is no longer available to join."); }
        $tournament = $tournament_result->fetch_assoc();
        
        $stmt_count = $conn->prepare("SELECT COUNT(id) as p_count FROM participants WHERE tournament_id = ?");
        $stmt_count->bind_param("i", $tournament_id);
        $stmt_count->execute();
        $p_count = $stmt_count->get_result()->fetch_assoc()['p_count'];

        if ($p_count >= $tournament['max_slots']) { throw new Exception("Sorry, this tournament is full."); }
        
        $stmt_wallet = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
        $stmt_wallet->bind_param("i", $user_id);
        $stmt_wallet->execute();
        $user = $stmt_wallet->get_result()->fetch_assoc();
        
        $entry_fee = $tournament['entry_fee'];
        $user_balance = $user['wallet_balance'];

        $stmt_check = $conn->prepare("SELECT id FROM participants WHERE user_id = ? AND tournament_id = ?");
        $stmt_check->bind_param("ii", $user_id, $tournament_id);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) { throw new Exception("You have already joined this tournament."); }
        
        if ($user_balance < $entry_fee) { throw new Exception("Insufficient wallet balance."); }

        $new_balance = $user_balance - $entry_fee;
        $stmt_update = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $stmt_update->bind_param("di", $new_balance, $user_id);
        $stmt_update->execute();
        
        $stmt_insert = $conn->prepare("INSERT INTO participants (user_id, tournament_id) VALUES (?, ?)");
        $stmt_insert->bind_param("ii", $user_id, $tournament_id);
        $stmt_insert->execute();
        
        $description = "Entry fee for tournament #" . $tournament_id;
        $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
        $stmt_trans->bind_param("ids", $user_id, $entry_fee, $description);
        $stmt_trans->execute();
        
        $conn->commit();
        $success = "Successfully joined the tournament!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Query for upcoming tournaments
$tournaments_sql = "SELECT t.*, (SELECT COUNT(id) FROM participants WHERE tournament_id = t.id) as participant_count FROM tournaments t WHERE t.status = 'Upcoming' ORDER BY t.match_time ASC";
$tournaments_result = $conn->query($tournaments_sql);

// Query for recent winners
$winners_sql = "SELECT t.title, u1.username AS w1, u2.username AS w2, u3.username AS w3 FROM tournaments t LEFT JOIN users u1 ON t.winner_1st_id = u1.id LEFT JOIN users u2 ON t.winner_2nd_id = u2.id LEFT JOIN users u3 ON t.winner_3rd_id = u3.id WHERE t.status = 'Completed' AND t.winner_1st_id IS NOT NULL ORDER BY t.created_at DESC LIMIT 3";
$winners_result = $conn->query($winners_sql);
?>

<div class="space-y-6">
    <div>
        <h2 class="text-xl font-semibold">Upcoming Tournaments</h2>
        <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded-lg text-center text-sm my-4"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded-lg text-center text-sm my-4"><?php echo $success; ?></div><?php endif; ?>
        <div class="space-y-4 mt-4">
            <?php if ($tournaments_result && $tournaments_result->num_rows > 0): while($row = $tournaments_result->fetch_assoc()): ?>
                <div class="bg-gray-700 rounded-lg shadow-lg overflow-hidden">
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <div><h3 class="text-lg font-bold text-white"><?php echo htmlspecialchars($row['title']); ?></h3><p class="text-sm text-gray-400"><?php echo htmlspecialchars($row['game_name']); ?></p></div>
                            <div class="text-right"><p class="text-sm font-semibold text-green-400">Prize Pool</p><p class="text-lg font-bold text-green-400">₹<?php echo number_format($row['prize_pool']); ?></p></div>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-4 text-sm">
                            <div><p class="text-gray-400">Match Time</p><p class="font-semibold text-white"><?php echo date('d M, h:i A', strtotime($row['match_time'])); ?></p></div>
                            <div><p class="text-gray-400">Entry Fee</p><p class="font-semibold text-white">₹<?php echo number_format($row['entry_fee']); ?></p></div>
                            <div><p class="text-gray-400">Slots</p><p class="font-semibold text-white"><?php echo $row['participant_count']; ?> / <?php echo $row['max_slots']; ?></p></div>
                        </div>
                    </div>
                    <div class="bg-gray-800 p-4 flex gap-2">
                        <a href="participants.php?id=<?php echo $row['id']; ?>" class="flex-1 text-center bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg text-sm">View Teams</a>
                        <?php if ($row['participant_count'] >= $row['max_slots']): ?>
                            <button class="flex-1 bg-gray-500 text-white font-bold py-2 px-4 rounded-lg cursor-not-allowed" disabled>Slots Full</button>
                        <?php else: ?>
                            <form action="index.php" method="POST" class="flex-1"><input type="hidden" name="tournament_id" value="<?php echo $row['id']; ?>"><button type="submit" name="join_tournament" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">Join Now</button></form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <div class="text-center py-10"><p class="text-gray-400">No upcoming tournaments right now.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-10">
        <h2 class="text-xl font-semibold">Recent Winners</h2>
        <div class="space-y-4 mt-4">
            <?php if ($winners_result && $winners_result->num_rows > 0): while ($w = $winners_result->fetch_assoc()): ?>
                <div class="bg-gray-700 p-4 rounded-lg">
                    <h4 class="font-bold text-white mb-2 pb-2 border-b border-gray-600"><?php echo htmlspecialchars($w['title']); ?></h4>
                    <div class="text-sm space-y-1 text-gray-300">
                        <?php if ($w['w1']): ?><p>🥇 <strong>1st:</strong> <?php echo htmlspecialchars($w['w1']); ?></p><?php endif; ?>
                        <?php if ($w['w2']): ?><p>🥈 <strong>2nd:</strong> <?php echo htmlspecialchars($w['w2']); ?></p><?php endif; ?>
                        <?php if ($w['w3']): ?><p>🥉 <strong>3rd:</strong> <?php echo htmlspecialchars($w['w3']); ?></p><?php endif; ?>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <p class="text-gray-400 text-center py-4 text-sm">No winners to show yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>