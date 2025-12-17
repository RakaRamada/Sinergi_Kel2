<?php
$currentPage = $_GET['page'] ?? 'dashboard';

// Logic Fix Avatar Path di Navbar
$raw_avatar_nav = $_SESSION['avatar_url'] ?? '';
if (empty($raw_avatar_nav)) {
    $nav_avatar_fixed = '/Sinergi/public/assets/images/user.png';
} elseif (strpos($raw_avatar_nav, '/') === false) {
    $nav_avatar_fixed = '/Sinergi/public/uploads/avatars/' . $raw_avatar_nav;
} else {
    $nav_avatar_fixed = $raw_avatar_nav;
}
?>

<div class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 z-50 lg:hidden safe-area-bottom">
    <div class="grid grid-cols-5 h-16">
        <!-- Home -->
        <a href="index.php?page=dashboard"
            class="flex flex-col items-center justify-center space-y-1 <?php echo ($currentPage == 'dashboard') ? 'text-black' : 'text-gray-400 hover:text-gray-600'; ?>">
            <svg class="w-6 h-6" fill="<?php echo ($currentPage == 'dashboard') ? 'currentColor' : 'none'; ?>"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            <span class="text-[10px] font-medium">Beranda</span>
        </a>

        <!-- Search -->
        <a href="index.php?page=search"
            class="flex flex-col items-center justify-center space-y-1 <?php echo ($currentPage == 'search') ? 'text-black' : 'text-gray-400 hover:text-gray-600'; ?>">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="<?php echo ($currentPage == 'search') ? '2.5' : '2'; ?>">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">
                </path>
            </svg>
            <span class="text-[10px] font-medium">Cari</span>
        </a>

        <!-- Groups -->
        <a href="index.php?page=messages"
            class="flex flex-col items-center justify-center space-y-1 <?php echo ($currentPage == 'messages' || $currentPage == 'group-details') ? 'text-black' : 'text-gray-400 hover:text-gray-600'; ?>">
            <svg class="w-6 h-6"
                fill="<?php echo ($currentPage == 'messages' || $currentPage == 'group-details') ? 'currentColor' : 'none'; ?>"
                stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="<?php echo ($currentPage == 'messages' || $currentPage == 'group-details') ? '2.5' : '2'; ?>">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                </path>
            </svg>
            <span class="text-[10px] font-medium">Grup</span>
        </a>

        <!-- Notification -->
        <?php
        require_once __DIR__ . '/../../models/NotificationModel.php';
        if (isset($conn) && isset($_SESSION['user_id'])) {
            $notifModel = new NotificationModel($conn);
            $badge_count = $notifModel->getUnreadCount($_SESSION['user_id']);
        } else {
            $badge_count = 0;
        }
        $badge_display = ($badge_count > 9) ? '9+' : $badge_count;
        ?>
        <a href="index.php?page=notification"
            class="relative flex flex-col items-center justify-center space-y-1 <?php echo ($currentPage == 'notification') ? 'text-black' : 'text-gray-400 hover:text-gray-600'; ?>">
            <div class="relative">
                <svg class="w-6 h-6" fill="<?php echo ($currentPage == 'notification') ? 'currentColor' : 'none'; ?>"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                    </path>
                </svg>
                <?php if ($badge_count > 0): ?>
                <span
                    class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white border-2 border-white">
                    <?= $badge_display ?>
                </span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] font-medium">Notifikasi</span>
        </a>

        <!-- Profile (Trigger Modal) -->
        <button onclick="openMobileProfileMenu()"
            class="flex flex-col items-center justify-center space-y-1 <?php echo ($currentPage == 'profile') ? 'text-black' : 'text-gray-400 hover:text-gray-600'; ?>">
            <div class="<?php echo ($currentPage == 'profile') ? 'ring-2 ring-black rounded-full p-0.5' : ''; ?>">
                <img src="<?php echo $nav_avatar_fixed; ?>" alt="Profile"
                    class="w-6 h-6 rounded-full object-cover bg-gray-100">
            </div>
            <span class="text-[10px] font-medium">Profil</span>
        </button>
    </div>
</div>

<!-- Mobile Profile Modal -->
<div id="mobileProfileModal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity opacity-0" id="mobileProfileBackdrop"
        onclick="closeMobileProfileMenu()"></div>

    <div class="fixed bottom-0 left-0 right-0 z-[101] overflow-hidden rounded-t-2xl bg-white shadow-2xl transform translate-y-full transition-transform duration-300 ease-out"
        id="mobileProfilePanel">
        <div class="flex flex-col p-6 space-y-4">

            <div class="flex items-center gap-4 mb-2">
                <img src="<?php echo $nav_avatar_fixed; ?>"
                    class="w-12 h-12 rounded-full object-cover border border-gray-200">
                <div>
                    <h3 class="text-lg font-bold text-gray-900"><?php echo $_SESSION['nama'] ?? 'User'; ?></h3>
                    <p class="text-sm text-gray-500">Pilih tindakan</p>
                </div>
            </div>

            <a href="index.php?page=profile"
                class="flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 transition border border-gray-100">
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <span class="font-bold text-gray-800 block">Lihat Profil</span>
                    <span class="text-xs text-gray-500">Lihat dan edit profil Anda</span>
                </div>
            </a>

            <a href="index.php?page=logout"
                class="flex items-center gap-4 p-3 rounded-xl hover:bg-red-50 transition border border-red-100 group">
                <div
                    class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-600 group-hover:bg-red-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                        </path>
                    </svg>
                </div>
                <div class="flex-1">
                    <span class="font-bold text-red-600 block">Logout</span>
                    <span class="text-xs text-red-400">Keluar dari akun</span>
                </div>
            </a>

            <button onclick="closeMobileProfileMenu()"
                class="w-full py-3 bg-gray-100 text-gray-700 font-bold rounded-xl mt-2">
                Batal
            </button>
        </div>
    </div>
</div>

<script>
function openMobileProfileMenu() {
    const modal = document.getElementById('mobileProfileModal');
    const backdrop = document.getElementById('mobileProfileBackdrop');
    const panel = document.getElementById('mobileProfilePanel');

    modal.classList.remove('hidden');
    // Force reflow
    void modal.offsetWidth;

    backdrop.classList.remove('opacity-0');
    panel.classList.remove('translate-y-full');
}

function closeMobileProfileMenu() {
    const modal = document.getElementById('mobileProfileModal');
    const backdrop = document.getElementById('mobileProfileBackdrop');
    const panel = document.getElementById('mobileProfilePanel');

    backdrop.classList.add('opacity-0');
    panel.classList.add('translate-y-full');

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
</script>
</div>