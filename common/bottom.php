        </main>
    </div>
    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="fixed bottom-0 left-0 right-0 bg-gray-900 max-w-lg mx-auto border-t border-gray-700">
        <div class="flex justify-around">
            <a href="index.php" class="flex-1 text-center py-2 text-gray-400 hover:text-white">
                <i class="fas fa-home text-xl"></i>
                <span class="block text-xs mt-1">Home</span>
            </a>
            <a href="my_tournaments.php" class="flex-1 text-center py-2 text-gray-400 hover:text-white">
                <i class="fas fa-trophy text-xl"></i>
                <span class="block text-xs mt-1">My Tournaments</span>
            </a>
            <a href="wallet.php" class="flex-1 text-center py-2 text-gray-400 hover:text-white">
                <i class="fas fa-wallet text-xl"></i>
                <span class="block text-xs mt-1">Wallet</span>
            </a>
            <a href="profile.php" class="flex-1 text-center py-2 text-gray-400 hover:text-white">
                <i class="fas fa-user text-xl"></i>
                <span class="block text-xs mt-1">Profile</span>
            </a>
        </div>
    </nav>
    <?php endif; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>