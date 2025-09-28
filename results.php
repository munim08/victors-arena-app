<?php
require_once 'common/config.php'; // Step 1: Config for session and DB

// Step 2: Security Check BEFORE any HTML
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Step 3: Now that security is checked, include the header which starts HTML output
require_once 'common/header.php';

$tournament_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($tournament_id === 0) {
    header("Location: my_tournaments.php");
    exit();
}

$tournament_stmt = $conn->prepare("SELECT title, game_name, total_matches, (SELECT COUNT(id) FROM matches WHERE tournament_id = tournaments.id AND status = 'Completed') as matches_played FROM tournaments WHERE id = ?");
$tournament_stmt->bind_param("i", $tournament_id);
$tournament_stmt->execute();
$tournament = $tournament_stmt->get_result()->fetch_assoc();

if (!$tournament) {
    header("Location: my_tournaments.php");
    exit();
}

$results_sql = "
    SELECT u.username,
        COALESCE(SUM(mr.kills), 0) as total_kills,
        COALESCE(SUM(CASE WHEN mr.rank = 1 THEN 10 WHEN mr.rank = 2 THEN 6 WHEN mr.rank = 3 THEN 5 WHEN mr.rank = 4 THEN 4 WHEN mr.rank = 5 THEN 3 WHEN mr.rank = 6 THEN 2 WHEN mr.rank BETWEEN 7 AND 8 THEN 1 ELSE 0 END), 0) as total_position_points,
        (COALESCE(SUM(mr.kills), 0) + COALESCE(SUM(CASE WHEN mr.rank=1 THEN 10 WHEN mr.rank=2 THEN 6 WHEN mr.rank=3 THEN 5 WHEN mr.rank=4 THEN 4 WHEN mr.rank=5 THEN 3 WHEN mr.rank=6 THEN 2 WHEN mr.rank BETWEEN 7 AND 8 THEN 1 ELSE 0 END), 0)) as total_points
    FROM participants p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN match_results mr ON p.id = mr.participant_id
    WHERE p.tournament_id = ?
    GROUP BY p.user_id, u.username
    ORDER BY total_points DESC, total_kills DESC
";
$stmt = $conn->prepare($results_sql);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$results_result = $stmt->get_result();
$overall_rank = 1;
?>

<div class="space-y-4">
    <a href="my_tournaments.php" class="text-indigo-400 hover:text-indigo-300 text-sm">&larr; Back to My Tournaments</a>
    <div>
        <h2 class="text-2xl font-bold">Overall Standings</h2>
        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($tournament['title']); ?></p>
        <div class="mt-2 text-sm bg-gray-800 p-2 rounded-lg text-center"><span class="font-semibold text-white">Matches Played: <?php echo $tournament['matches_played']; ?> / <?php echo $tournament['total_matches']; ?></span></div>
    </div>
    <div class="bg-gray-700 rounded-lg overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-800 text-xs text-gray-400 uppercase">
                <tr>
                    <th class="px-4 py-3 text-center">#</th>
                    <th class="px-4 py-3">Player</th>
                    <th class="px-4 py-3 text-center">Position Pts</th>
                    <th class="px-4 py-3 text-center">Kills</th>
                    <th class="px-4 py-3 text-center">Total Pts</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results_result && $results_result->num_rows > 0): ?>
                    <?php while($row = $results_result->fetch_assoc()): ?>
                    <tr class="border-b border-gray-600">
                        <td class="px-4 py-3 font-bold text-center"><?php echo $overall_rank++; ?></td>
                        <td class="px-4 py-3 font-medium text-white"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td class="px-4 py-3 text-center"><?php echo intval($row['total_position_points']); ?></td>
                        <td class="px-4 py-3 text-center"><?php echo intval($row['total_kills']); ?></td>
                        <td class="px-4 py-3 text-center font-bold text-green-400"><?php echo intval($row['total_points']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center p-4 text-gray-400">Performance data has not been updated.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>