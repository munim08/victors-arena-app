<?php
// THIS LINE WAS MISSING
require_once 'common/header.php';

$error = '';
$success = '';
$tournament_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$tournament_id) { 
    header("Location: tournament.php"); 
    exit(); 
}

// Handle final winner declaration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['declare_winners'])) {
    // This logic should be moved here if you declare final winners on this page
    // For now, this page will focus on managing matches
}

// Fetch tournament, participants, and its matches
$tournament = $conn->query("SELECT * FROM tournaments WHERE id = $tournament_id")->fetch_assoc();
$participants_result = $conn->query("SELECT u.username FROM users u JOIN participants p ON u.id = p.user_id WHERE p.tournament_id = $tournament_id");
$matches_result = $conn->query("SELECT * FROM matches WHERE tournament_id = $tournament_id ORDER BY match_number ASC");

?>
<div class="space-y-6">
    <a href="tournament.php" class="text-indigo-400 hover:text-indigo-300">&larr; Back to All Tournaments</a>
    <h2 class="text-2xl font-bold">Manage Tournament: <?php echo htmlspecialchars($tournament['title']); ?></h2>
    
    <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded-lg"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded-lg"><?php echo $success; ?></div><?php endif; ?>

    <!-- Section: List of Matches -->
    <div class="bg-gray-700 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Matches (<?php echo $matches_result->num_rows; ?>)</h3>
        <div class="space-y-2">
            <?php if ($matches_result && $matches_result->num_rows > 0): ?>
                <?php while($match = $matches_result->fetch_assoc()): ?>
                    <a href="manage_match.php?id=<?php echo $match['id']; ?>" class="flex justify-between items-center bg-gray-800 p-3 rounded-lg hover:bg-gray-600 transition-colors">
                        <div>
                            <span class="font-bold">Match #<?php echo $match['match_number']; ?></span>
                            <span class="block text-xs text-gray-400">Click to manage room & results</span>
                        </div>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo ($match['status'] == 'Completed' ? 'bg-green-500' : ($match['status'] == 'Live' ? 'bg-red-500 animate-pulse' : 'bg-blue-500')); ?>">
                            <?php echo $match['status']; ?>
                        </span>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-400 text-center py-4">No matches found for this tournament.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Section: Participant List -->
    <div class="bg-gray-700 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Participants (<?php echo $participants_result->num_rows; ?>)</h3>
        <div class="space-y-2">
            <?php if ($participants_result && $participants_result->num_rows > 0): ?>
                <?php while($p = $participants_result->fetch_assoc()): ?>
                    <div class="bg-gray-800 p-2 rounded-md text-sm"><?php echo htmlspecialchars($p['username']); ?></div>
                <?php endwhile; ?>
            <?php else: ?>
                 <p class="text-gray-400 text-center py-4">No users have joined this tournament yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- You can add the final "Declare 1st, 2nd, 3rd" form here if needed, once all matches are complete -->

</div>
<?php require_once 'common/bottom.php'; ?>