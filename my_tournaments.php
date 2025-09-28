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

$stmt = $conn->prepare("
    SELECT t.*, (SELECT COUNT(id) FROM matches WHERE tournament_id = t.id AND status = 'Completed') as matches_played,
           u1.username AS winner_1st_name, u2.username AS winner_2nd_name, u3.username AS winner_3rd_name
    FROM tournaments AS t
    JOIN participants AS p ON t.id = p.tournament_id
    LEFT JOIN users AS u1 ON t.winner_1st_id = u1.id
    LEFT JOIN users AS u2 ON t.winner_2nd_id = u2.id
    LEFT JOIN users AS u3 ON t.winner_3rd_id = u3.id
    WHERE p.user_id = ? ORDER BY t.match_time DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$upcoming_live = []; $completed = [];
while ($row = $result->fetch_assoc()) {
    if ($row['status'] == 'Upcoming' || $row['status'] == 'Live') { $upcoming_live[] = $row; } 
    else { $completed[] = $row; }
}

$live_matches_stmt = $conn->prepare("SELECT t.id as tournament_id, m.match_number, m.room_id, m.room_password FROM tournaments t JOIN matches m ON t.id = m.tournament_id WHERE t.status IN ('Upcoming', 'Live') AND m.status = 'Live'");
$live_matches_stmt->execute();
$live_matches_result = $live_matches_stmt->get_result();
$live_match_details = [];
while($row = $live_matches_result->fetch_assoc()) { $live_match_details[$row['tournament_id']] = $row; }
?>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<div x-data="{ tab: 'upcoming' }" class="space-y-4">
    <div class="flex bg-gray-700 rounded-lg p-1">
        <button @click="tab = 'upcoming'" :class="{'bg-indigo-600 text-white': tab === 'upcoming'}" class="flex-1 py-2 px-4 rounded-md text-sm font-medium">Upcoming/Live</button>
        <button @click="tab = 'completed'" :class="{'bg-indigo-600 text-white': tab === 'completed'}" class="flex-1 py-2 px-4 rounded-md text-sm font-medium">Completed</button>
    </div>

    <div x-show="tab === 'upcoming'" class="space-y-4">
        <?php if (!empty($upcoming_live)): foreach ($upcoming_live as $t): ?>
            <div class="bg-gray-700 rounded-lg shadow-lg">
                <div class="p-4">
                    <div class="flex justify-between items-start">
                        <div><h3 class="text-lg font-bold"><?php echo htmlspecialchars($t['title']); ?></h3><p class="text-sm text-gray-400"><?php echo htmlspecialchars($t['game_name']); ?></p></div>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo ($t['status'] == 'Live' ? 'bg-green-500' : 'bg-blue-500'); ?>"><?php echo $t['status']; ?></span>
                    </div>
                    <p class="text-sm text-gray-300 mt-2"><i class="far fa-clock mr-1"></i> Starts: <?php echo date('d M Y, h:i A', strtotime($t['match_time'])); ?></p>
                    <div class="mt-2 text-sm bg-gray-800 p-2 rounded-lg flex justify-center items-center"><span class="font-semibold text-white">Matches Played: <?php echo $t['matches_played']; ?> / <?php echo $t['total_matches']; ?></span></div>
                </div>
                <?php if (isset($live_match_details[$t['id']])): $live_match = $live_match_details[$t['id']]; ?>
                    <div class="bg-gray-800 px-4 py-3 border-y border-gray-600"><h4 class="text-sm font-semibold mb-2 text-red-400 animate-pulse">MATCH #<?php echo $live_match['match_number']; ?> IS LIVE</h4><div class="grid grid-cols-2 gap-4 text-sm"><div><p class="text-gray-400">Room ID</p><p class="font-bold text-white"><?php echo htmlspecialchars($live_match['room_id']); ?></p></div><div><p class="text-gray-400">Password</p><p class="font-bold text-white"><?php echo htmlspecialchars($live_match['room_password']); ?></p></div></div></div>
                <?php endif; ?>
                <div class="p-4 bg-gray-700 rounded-b-lg"><a href="results.php?id=<?php echo $t['id']; ?>" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg text-sm">View Standings</a></div>
            </div>
        <?php endforeach; else: ?>
            <div class="text-center py-10"><p class="text-gray-400">You have not joined any upcoming tournaments.</p></div>
        <?php endif; ?>
    </div>
    
    <div x-show="tab === 'completed'" style="display: none;" class="space-y-4">
        <?php if (!empty($completed)): foreach ($completed as $t): ?>
            <div class="bg-gray-700 rounded-lg p-4">
                <div class="pb-3 mb-3 border-b border-gray-600"><h3 class="text-lg font-bold"><?php echo htmlspecialchars($t['title']); ?></h3><?php $user_rank = 'Participated'; $user_prize=0; if($t['winner_1st_id']==$user_id){$user_rank='🥇 1st Place';$user_prize=$t['prize_1st'];}elseif($t['winner_2nd_id']==$user_id){$user_rank='🥈 2nd Place';$user_prize=$t['prize_2nd'];}elseif($t['winner_3rd_id']==$user_id){$user_rank='🥉 3rd Place';$user_prize=$t['prize_3rd'];} ?><div class="flex justify-between items-center mt-1"><span class="font-semibold <?php if($user_prize>0) echo 'text-green-400';?>"><?php echo $user_rank;?></span><?php if($user_prize>0):?><span class="font-bold text-green-400">+₹<?php echo number_format($user_prize);?></span><?php endif;?></div></div>
                <div class="text-sm space-y-1"><h4 class="font-semibold mb-2">Final Results:</h4><?php if($t['winner_1st_name']):?><p>🥇 <strong>1st:</strong> <?php echo htmlspecialchars($t['winner_1st_name']);?> (₹<?php echo number_format($t['prize_1st']);?>)</p><?php endif;?><?php if($t['winner_2nd_name']):?><p>🥈 <strong>2nd:</strong> <?php echo htmlspecialchars($t['winner_2nd_name']);?> (₹<?php echo number_format($t['prize_2nd']);?>)</p><?php endif;?><?php if($t['winner_3rd_name']):?><p>🥉 <strong>3rd:</strong> <?php echo htmlspecialchars($t['winner_3rd_name']);?> (₹<?php echo number_format($t['prize_3rd']);?>)</p><?php endif;?></div>
                <div class="mt-4"><a href="results.php?id=<?php echo $t['id'];?>" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg text-sm">View Full Standings</a></div>
            </div>
        <?php endforeach; else: ?>
            <div class="text-center py-10"><p class="text-gray-400">No completed tournaments to show.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>