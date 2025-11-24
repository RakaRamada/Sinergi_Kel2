<nav class="col-span-2 border-r border-gray-200 px-4">
    <div class="flex flex-col items-end h-screen py-6 sticky top-0">
        <?php
        $currentPage = $_GET['page'] ?? 'dashboard';
        ?>

        <div class="flex flex-col items-center space-y-4">

            <a href="index.php?page=dashboard" title="Sinergi"
                class="w-12 h-12 flex items-center justify-center mb-2 hover:scale-105 transition-transform">
                <img src="/Sinergi/public/assets/icons/logo.svg" alt="Logo" class="w-9 h-9">
            </a>

            <a href="index.php?page=dashboard" title="Beranda" class="group w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-200
                      <?php echo ($currentPage == 'dashboard') ? 'bg-gray-200' : 'hover:bg-gray-100'; ?>">
                <img src="/Sinergi/public/assets/icons/home.svg" alt="Beranda"
                    class="w-7 h-7 transition-opacity duration-200 
                            <?php echo ($currentPage == 'dashboard') ? 'opacity-100' : 'opacity-50 group-hover:opacity-100'; ?>">
            </a>

            <a href="index.php?page=search" title="Cari" class="group w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-200
                      <?php echo ($currentPage == 'search') ? 'bg-gray-200' : 'hover:bg-gray-100'; ?>">
                <img src="/Sinergi/public/assets/icons/search.svg" alt="Cari"
                    class="w-7 h-7 transition-opacity duration-200
                            <?php echo ($currentPage == 'search') ? 'opacity-100' : 'opacity-50 group-hover:opacity-100'; ?>">
            </a>

            <?php
            require_once __DIR__ . '/../../models/NotificationModel.php';
            $badge_count = 0;
            if (isset($_SESSION['user_id'])) {
                $badge_count = getUnreadCount($_SESSION['user_id']);
            }
            $badge_display = ($badge_count > 9) ? '9+' : $badge_count;
            ?>

            <a href="index.php?page=notification" title="Notifikasi"
                class="group w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-200 relative
                      <?php echo ($currentPage == 'notification') ? 'bg-gray-200 text-black' : 'text-gray-500 hover:bg-gray-100 hover:text-black'; ?>">

                <div class="relative">
                    <svg class="w-7 h-7 transition-colors duration-200" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                        </path>
                    </svg>

                    <?php if ($badge_count > 0): ?>
                    <div
                        class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-gray-700 rounded-full border-2 border-white transform translate-x-1 -translate-y-1">
                        <?= $badge_display ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>

            <a href="index.php?page=messages" title="Pesan" class="group w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-200
                      <?php echo ($currentPage == 'messages') ? 'bg-gray-200' : 'hover:bg-gray-100'; ?>">
                <img src="/Sinergi/public/assets/icons/chat.svg" alt="Pesan"
                    class="w-7 h-7 transition-opacity duration-200
                            <?php echo ($currentPage == 'messages') ? 'opacity-100' : 'opacity-50 group-hover:opacity-100'; ?>">
            </a>

            <a href="index.php?page=settings" title="Pengaturan" class="group w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-200
                      <?php echo ($currentPage == 'settings') ? 'bg-gray-200' : 'hover:bg-gray-100'; ?>">
                <img src="/Sinergi/public/assets/icons/settings.svg" alt="Pengaturan"
                    class="w-7 h-7 transition-opacity duration-200
                            <?php echo ($currentPage == 'settings') ? 'opacity-100' : 'opacity-50 group-hover:opacity-100'; ?>">
            </a>
        </div>

        <div class="mt-auto">
            <a href="index.php?page=profile" title="Profil Anda" class="block hover:opacity-80 transition-opacity">
                <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Anda"
                    class="w-10 h-10 rounded-full border border-gray-200">
            </a>
        </div>
    </div>
</nav>