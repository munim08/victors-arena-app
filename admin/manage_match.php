<?php
require_once 'common/header.php';

$error = '';
$success = '';
$match_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$match_id) { 
    header("Location: index.php"); 
    exit(); 
}

// Handle All Form Submissions on This Page
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // --- 1. Handle Updating Room ID, Password, and Match Status ---
    if (isset($_POST['update_room'])) {
        $room_id = trim($_POST['room_id']);
        $room_pass = trim($_POST['room_password']);
        $status = $_POST['status']; // 'Pending', 'Live', or 'Completed'
        
        $stmt = $conn->prepare("UPDATE matches SET room_id = ?, room_password = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssi", $room_id, $room_pass, $status, $match_id);
        if($stmt->execute()) {
            $success = "Match details updated successfully!";
        } else {
            $error = "Failed to update match details.";
        }
    }
    
    // --- 2. Handle Updating Player Performance (Ranks and Kills) ---
    if (isset($_POST['update_performance'])) {
        $ranks = $_POST['ranks'];
        $kills = $_POST['kills'];
        
        $conn->begin_transaction();
        try {
            foreach ($ranks as $participant_id => $rank) {
                $kill_count = isset($kills[$participant_id]) ? intval($kills[$participant_id]) : 0;
                $rank_value = !empty($rank) ? intval($rank) : null;
                
                // This "UPSERT" query will create a result row if it's the first time, or update it if it already exists.
                $upsert_sql = "
                    INSERT INTO match_results (match_id, participant_id, rank, kills) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE rank = VALUES(rank), kills = VALUES(kills)
                ";
                $stmt = $conn->prepare($upsert_sql);
                $stmt->bind_param("iiii", $match_id, $participant_id, $rank_value, $kill_count);
                $stmt->execute();
            }
            $conn->commit();
            $success = "Player performance for this match has been saved! You can now set the match status to 'Completed'.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "An error occurred while saving performance data.";
        }
    }
}

// Fetch data for the page
$match = $conn->query("SELECT m.*, t.title, t.id as tournament_id FROM matches m JOIN tournaments t ON m.tournament_id = t.id WHERE m.id = $match_id")->fetch_assoc();
$tournament_id = $match['tournament_id'];

// Fetch all participants of the parent tournament, along with any results they have for THIS specific match
$participants_sql = "
    SELECT p.id, u.username, mr.rank, mr.kills 
    FROM participants p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN match_results mr ON p.id = mr.participant_id AND mr.match_id = ?
    WHERE p.tournament_id = ?
";
$stmt = $conn->prepare($participants_sql);
$stmt->bind_param("ii", $match_id, $tournament_id);
$stmt->execute();
$participants_result = $stmt->get_result();
?>

<div class="space-y-6">
    <a href="manage_tournament.php?id=<?php echo $tournament_id; ?>" class="text-indigo-400 hover:text-indigo-300">&larr; Back to Tournament Hub</a>
    <h2 class="text-2xl font-bold">Manage Match #<?php echo $match['match_number']; ?></h2>
    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($match['title']); ?></p>

    <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded-lg my-4"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded-lg my-4"><?php echo $success; ?></div><?php endif; ?>

    <!-- Form for Match Details (Room, Pass, Status) -->
    <form action="manage_match.php?id=<?php echo $match_id; ?>" method="POST" class="bg-gray-700 p-4 rounded-lg space-y-4">
        <input type="hidden" name="update_room" value="1">
        <h3 class="font-semibold text-lg">Match Details</h3>
        <div><label class="block text-sm">Room ID</label><input type="text" name="room_id" value="<?php echo htmlspecialchars($match['room_id'] ?? ''); ?>" class="w-full bg-gray-800 p-2 rounded mt-1"></div>
        <div><label class="block text-sm">Room Password</label><input type="text" name="room_password" value="<?php echo htmlspecialchars($match['room_password'] ?? ''); ?>" class="w-full bg-gray-800 p-2 rounded mt-1"></div>
        <div><label class="block text-sm">Status</label><select name="status" class="w-full bg-gray-800 p-2 rounded mt-1"><option value="Pending" <?php if($match['status'] == 'Pending') echo 'selected'; ?>>Pending</option><option value="Live" <?php if($match['status'] == 'Live') echo 'selected'; ?>>Live</option><option value="Completed" <?php if($match['status'] == 'Completed') echo 'selected'; ?>>Completed</option></select></div>
        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 font-bold py-2 rounded">Save Details</button>
    </form>

    <!-- Form for Player Performance -->
    <div class="bg-gray-700 p-4 rounded-lg">
        <h3 class="font-semibold text-lg">Player Performance for this Match</h3>
        <form action="manage_match.php?id=<?php echo $match_id; ?>" method="POST">
            <input type="hidden" name="update_performance" value="1">
            <div class="space-y-3 mt-4">
                <?php if ($participants_result && $participants_result->num_rows > 0): ?>
                    <?php while($p = $participants_result->fetch_assoc()): ?>
                    <div class="grid grid-cols-12 gap-2 items-center">
                        <span class="col-span-6 text-sm truncate" title="<?php echo htmlspecialchars($p['username']); ?>"><?php echo htmlspecialchars($p['username']); ?></span>
                        <div class="col-span-3">
                            <input type="number" name="ranks[<?php echo $p['id']; ?>]" value="<?php echo $p['rank']; ?>" placeholder="Rank" class="w-full bg-gray-800 text-white p-1 rounded text-center text-sm">
                        </div>
                        <div class="col-span-3">
                            <input type="number" name="kills[<?php echo $p['id']; ?>]" value="<?php echo $p['kills']; ?>" placeholder="Kills" class="w-full bg-gray-800 text-white p-1 rounded text-center text-sm">
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 font-bold py-2 px-4 rounded-md mt-4">Save Performance</button>
                <?php else: ?>
                    <p class="text-gray-400 text-center py-4">No participants have joined this tournament.</p>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php require_once 'common/bottom.php'; ?>