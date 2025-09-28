<?php
require_once 'common/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the tournament ID from the URL, ensure it's a valid number
$tournament_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($tournament_id === 0) {
    // Redirect if the ID is missing or invalid
    header("Location: index.php");
    exit();
}

// Fetch basic tournament details to display as a title
$tournament_stmt = $conn->prepare("SELECT title, game_name FROM tournaments WHERE id = ?");
$tournament_stmt->bind_param("i", $tournament_id);
$tournament_stmt->execute();
$tournament = $tournament_stmt->get_result()->fetch_assoc();

// If tournament doesn't exist, redirect
if (!$tournament) {
    header("Location: index.php");
    exit();
}

// Fetch all participants for this specific tournament
$participants_stmt = $conn->prepare("
    SELECT u.username 
    FROM participants p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.tournament_id = ?
    ORDER BY u.username ASC
");
$participants_stmt->bind_param("i", $tournament_id);
$participants_stmt->execute();
$participants_result = $participants_stmt->get_result();

?>

<div class="space-y-4">
    <a href="index.php" class="text-indigo-400 hover:text-indigo-300 text-sm">&larr; Back to Tournaments</a>
    
    <div>
        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($tournament['title']); ?></h2>
        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($tournament['game_name']); ?></p>
    </div>

    <!-- Participants List Card -->
    <div class="bg-gray-700 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-4 border-b border-gray-600 pb-2">
            Joined Teams (<?php echo $participants_result->num_rows; ?>)
        </h3>
        <div class="space-y-3">
            <?php if ($participants_result && $participants_result->num_rows > 0): ?>
                <?php while($participant = $participants_result->fetch_assoc()): ?>
                    <div class="bg-gray-800 p-3 rounded-md flex items-center">
                        <i class="fas fa-user-shield text-gray-400 mr-3"></i>
                        <span class="text-white font-medium"><?php echo htmlspecialchars($participant['username']); ?></span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-400 text-center py-6">No teams have joined this tournament yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>