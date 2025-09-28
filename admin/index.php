<?php
require_once 'common/header.php';

// Fetch stats
$total_users = $conn->query("SELECT COUNT(id) as count FROM users")->fetch_assoc()['count'];
$total_tournaments = $conn->query("SELECT COUNT(id) as count FROM tournaments")->fetch_assoc()['count'];
$prizes_distributed_query = $conn->query("SELECT SUM(prize_pool) as total FROM tournaments WHERE status = 'Completed' AND winner_id IS NOT NULL");
$prizes_distributed = $prizes_distributed_query ? $prizes_distributed_query->fetch_assoc()['total'] ?? 0 : 0;

$total_fees_query = $conn->query("
    SELECT SUM(t.entry_fee) as total 
    FROM tournaments t 
    JOIN participants p ON t.id = p.tournament_id
");
$total_entry_fees = $total_fees_query ? $total_fees_query->fetch_assoc()['total'] ?? 0 : 0;
$total_revenue = $total_entry_fees * 0.20; // 20% commission
?>

<div class="space-y-6">
    <h2 class="text-2xl font-bold">Dashboard</h2>

    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-700 p-4 rounded-lg text-center"><p class="text-3xl font-bold"><?php echo $total_users; ?></p><p class="text-sm text-gray-400">Total Users</p></div>
        <div class="bg-gray-700 p-4 rounded-lg text-center"><p class="text-3xl font-bold"><?php echo $total_tournaments; ?></p><p class="text-sm text-gray-400">Tournaments</p></div>
        <div class="bg-gray-700 p-4 rounded-lg text-center"><p class="text-2xl font-bold">₹<?php echo number_format($prizes_distributed); ?></p><p class="text-sm text-gray-400">Prizes Distributed</p></div>
        <div class="bg-gray-700 p-4 rounded-lg text-center"><p class="text-2xl font-bold">₹<?php echo number_format($total_revenue); ?></p><p class="text-sm text-gray-400">Est. Revenue</p></div>
    </div>

    <!-- THIS IS THE CORRECTED LINK -->
    <a href="tournament.php" class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-4 rounded-lg transition-colors">
        <i class="fas fa-plus-circle mr-2"></i> Create New Tournament
    </a>

    <div>
        <h3 class="text-xl font-semibold mb-3">Recent Tournaments</h3>
        <div class="space-y-3">
            <?php
            $recent_tournaments = $conn->query("SELECT * FROM tournaments ORDER BY created_at DESC LIMIT 5");
            if ($recent_tournaments->num_rows > 0) {
                while($t = $recent_tournaments->fetch_assoc()) {
                    echo '<a href="manage_tournament.php?id='.$t['id'].'" class="block bg-gray-700 p-3 rounded-lg flex justify-between items-center hover:bg-gray-600">';
                    echo '<div><p class="font-semibold">' . htmlspecialchars($t['title']) . '</p><p class="text-xs text-gray-400">' . date('d M, Y', strtotime($t['created_at'])) . '</p></div>';
                    echo '<span class="text-xs font-semibold px-2 py-1 rounded-full ' . ($t['status'] == 'Completed' ? 'bg-green-500' : ($t['status'] == 'Live' ? 'bg-red-500' : 'bg-blue-500')) . '">' . $t['status'] . '</span>';
                    echo '</a>';
                }
            } else {
                echo '<p class="text-center text-gray-400">No tournaments created yet.</p>';
            }
            ?>
        </div>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>