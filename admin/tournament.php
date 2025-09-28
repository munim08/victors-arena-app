<?php
require_once 'common/header.php';

$error = '';
$success = '';

// Handle Create Tournament
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_tournament'])) {
    $title = trim($_POST['title']);
    $game_name = trim($_POST['game_name']);
    $entry_fee = trim($_POST['entry_fee']);
    $prize_pool = trim($_POST['prize_pool']);
    $max_slots = intval($_POST['max_slots']);
    $total_matches = intval($_POST['total_matches']);
    $match_time = trim($_POST['match_time']);
    
    if (empty($title) || empty($game_name) || !is_numeric($entry_fee) || !is_numeric($prize_pool) || $max_slots <= 0 || $total_matches <= 0 || empty($match_time)) {
        $error = "All fields are required and must be in the correct format.";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO tournaments (title, game_name, entry_fee, prize_pool, max_slots, total_matches, match_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddiis", $title, $game_name, $entry_fee, $prize_pool, $max_slots, $total_matches, $match_time);
            $stmt->execute();
            $tournament_id = $stmt->insert_id;
            for ($i = 1; $i <= $total_matches; $i++) {
                $match_stmt = $conn->prepare("INSERT INTO matches (tournament_id, match_number) VALUES (?, ?)");
                $match_stmt->bind_param("ii", $tournament_id, $i);
                $match_stmt->execute();
            }
            $conn->commit();
            $success = "Tournament and its $total_matches matches created successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to create tournament: " . $e->getMessage();
        }
    }
}

// --- SIMPLIFIED DELETE LOGIC ---
// Handle Delete Tournament Request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $tournament_id_to_delete = intval($_GET['id']);
    
    // Thanks to ON DELETE CASCADE, we only need to delete the tournament itself.
    // The database will automatically delete all linked matches and participants.
    $stmt = $conn->prepare("DELETE FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $tournament_id_to_delete);
    
    if ($stmt->execute()) {
        $success = "Tournament and all its related data have been deleted successfully.";
    } else {
        $error = "Failed to delete tournament. Error: " . $conn->error;
    }
}

$tournaments_result = $conn->query("SELECT * FROM tournaments ORDER BY created_at DESC");
?>

<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold mb-4">Create New Tournament</h2>
        <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded mb-4 text-sm"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded mb-4 text-sm"><?php echo $success; ?></div><?php endif; ?>

        <form action="tournament.php" method="POST" class="bg-gray-700 p-4 rounded-lg space-y-4">
            <input type="hidden" name="create_tournament" value="1">
            <div><label class="block text-sm">Tournament Title</label><input type="text" name="title" required class="mt-1 w-full bg-gray-800 p-2 rounded"></div>
            <div><label class="block text-sm">Game Name</label><input type="text" name="game_name" required class="mt-1 w-full bg-gray-800 p-2 rounded"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm">Entry Fee (₹)</label><input type="number" name="entry_fee" step="1" required class="mt-1 w-full bg-gray-800 p-2 rounded"></div>
                <div><label class="block text-sm">Prize Pool (₹)</label><input type="number" name="prize_pool" step="1" required class="mt-1 w-full bg-gray-800 p-2 rounded"></div>
            </div>
             <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm">Max Slots</label><input type="number" name="max_slots" value="25" required class="mt-1 w-full bg-gray-800 p-2 rounded"></div>
                <div><label class="block text-sm">Total Matches</label><input type="number" name="total_matches" value="1" required class="mt-1 w-full bg-gray-800 p-2 rounded"></div>
            </div>
            <div><label class="block text-sm">Start Time</label><input type="datetime-local" name="match_time" required class="mt-1 w-full bg-gray-800 p-2" style="color-scheme: dark;"></div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 font-bold py-3 px-4 rounded-md"><i class="fas fa-plus-circle"></i> Create Tournament</button>
        </form>
    </div>

    <div>
        <h2 class="text-2xl font-bold mb-4">All Tournaments</h2>
        <div class="space-y-4">
            <?php if ($tournaments_result && $tournaments_result->num_rows > 0): while ($t = $tournaments_result->fetch_assoc()): ?>
                <div class="bg-gray-700 p-4 rounded-lg">
                    <div class="flex justify-between items-start">
                        <div><h3 class="text-lg font-bold"><?php echo htmlspecialchars($t['title']); ?></h3><p class="text-sm text-gray-400"><?php echo htmlspecialchars($t['game_name']); ?></p></div>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo ($t['status'] == 'Completed' ? 'bg-green-500' : ($t['status'] == 'Live' ? 'bg-red-500' : 'bg-blue-500')); ?>"><?php echo $t['status']; ?></span>
                    </div>
                    <div class="mt-3 text-sm text-gray-300"><p><strong>Start Time:</strong> <?php echo date('d M Y, h:i A', strtotime($t['match_time'])); ?></p></div>
                    <div class="mt-4 flex gap-2">
                         <a href="manage_tournament.php?id=<?php echo $t['id']; ?>" class="flex-1 text-center bg-blue-600 hover:bg-blue-700 font-bold py-2 px-4 rounded-md text-sm">Manage</a>
                        <a href="tournament.php?action=delete&id=<?php echo $t['id']; ?>" onclick="return confirm('Are you sure? This will delete the tournament and ALL its data.');" class="flex-1 text-center bg-red-600 hover:bg-red-700 font-bold py-2 px-4 rounded-md text-sm">Delete</a>
                    </div>
                </div>
            <?php endwhile; endif; ?>
        </div>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>