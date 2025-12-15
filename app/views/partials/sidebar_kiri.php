<nav class="col-span-2 border-r border-gray-200 px-4 bg-white">
    <div class="flex flex-col items-end h-screen py-6 sticky top-0 z-50">
        <?php
        $currentPage = $_GET['page'] ?? 'dashboard';
        ?>

        <a href="index.php?page=dashboard" title="Sinergi"
            class="w-12 h-12 flex items-center justify-center mb-6 hover:scale-105 transition-transform">
            <img src="/Sinergi/public/assets/icons/logo.svg" alt="Logo" class="w-9 h-9">
        </a>

        <div class="flex flex-col items-end space-y-3 w-full">

            <a href="index.php?page=dashboard" title="Beranda"
                class="group w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-200
                      <?php echo ($currentPage == 'dashboard') ? 'bg-black text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-black'; ?>">
                <img src="/Sinergi/public/assets/icons/home.svg" alt="Beranda"
                    class="w-6 h-6 transition-all duration-200 
                    <?php echo ($currentPage == 'dashboard') ? 'invert brightness-0' : 'opacity-60 group-hover:opacity-100'; ?>">
            </a>

            <a href="index.php?page=search" title="Cari"
                class="group w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-200
                      <?php echo ($currentPage == 'search') ? 'bg-black text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-black'; ?>">
                <img src="/Sinergi/public/assets/icons/search.svg" alt="Cari"
                    class="w-6 h-6 transition-all duration-200
                    <?php echo ($currentPage == 'search') ? 'invert brightness-0' : 'opacity-60 group-hover:opacity-100'; ?>">
            </a>

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

            <a href="index.php?page=notification" title="Notifikasi"
                class="group w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-200 relative
                      <?php echo ($currentPage == 'notification') ? 'bg-black text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-black'; ?>">

                <div class="relative">
                    <svg class="w-7 h-7 transition-colors duration-200" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                        </path>
                    </svg>
                    <?php if ($badge_count > 0): ?>
                    <div
                        class="absolute -top-1.5 -right-1.5 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-600 rounded-full border-2 border-white">
                        <?= $badge_display ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>

            <a href="index.php?page=messages" title="Pesan Grup"
                class="group w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-200
                      <?php echo ($currentPage == 'messages') ? 'bg-black text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-black'; ?>">
                <img src="/Sinergi/public/assets/icons/group.svg" alt="Pesan"
                    class="w-6 h-6 transition-all duration-200
                    <?php echo ($currentPage == 'messages') ? 'invert brightness-0' : 'opacity-60 group-hover:opacity-100'; ?>">
            </a>

            <div class="relative">
                <button onclick="toggleSettingsMenu()" title="Pengaturan"
                    class="group w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-200 cursor-pointer hover:bg-gray-100 focus:outline-none text-gray-500 hover:text-black">
                    <img src="/Sinergi/public/assets/icons/settings.svg" alt="Pengaturan"
                        class="w-6 h-6 transition-opacity duration-200 opacity-60 group-hover:opacity-100">
                </button>

                <div id="settingsPopover"
                    class="hidden absolute left-14 top-0 w-40 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50 origin-top-left transition-all">
                    <div class="py-1">
                        <button onclick="openLogoutConfirmation()"
                            class="w-full text-left px-4 py-3 text-sm font-bold text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                </path>
                            </svg>
                            Logout
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <div class="mt-auto pb-4">
            <a href="index.php?page=profile" title="Profil Anda"
                class="block hover:opacity-80 transition-opacity hover:scale-105 transform duration-200">
                <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Anda"
                    class="w-10 h-10 rounded-full border border-gray-200 bg-white object-cover shadow-sm">
            </a>
        </div>
    </div>
</nav>

<div id="logoutModal" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity opacity-0" id="logoutBackdrop"
        onclick="closeLogoutConfirmation()"></div>

    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm p-6 bg-white rounded-2xl shadow-2xl scale-95 opacity-0 transition-all duration-300"
        id="logoutContent">

        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </div>

            <h3 class="text-xl font-bold text-gray-900 mb-2">Konfirmasi Logout</h3>
            <p class="text-sm text-gray-500 mb-8">
                Apakah Anda yakin ingin mengakhiri sesi ini?
            </p>

            <div class="space-y-3">
                <a href="index.php?page=logout"
                    class="block w-full py-3 px-4 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition shadow-lg shadow-red-200">
                    Ya, Keluar
                </a>

                <button type="button" onclick="closeLogoutConfirmation()"
                    class="block w-full py-3 px-4 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition cursor-pointer">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// --- LOGIKA MENU KECIL (POPOVER) ---
const settingsPopover = document.getElementById('settingsPopover');

function toggleSettingsMenu() {
    if (settingsPopover.classList.contains('hidden')) {
        settingsPopover.classList.remove('hidden');
    } else {
        settingsPopover.classList.add('hidden');
    }
}

// Menutup menu jika klik di luar area
document.addEventListener('click', function(event) {
    const isButton = event.target.closest('button[onclick="toggleSettingsMenu()"]');
    const isMenu = event.target.closest('#settingsPopover');

    if (!isButton && !isMenu && settingsPopover && !settingsPopover.classList.contains('hidden')) {
        settingsPopover.classList.add('hidden');
    }
});

// --- LOGIKA MODAL KONFIRMASI (ALERT) ---
const logoutModal = document.getElementById('logoutModal');
const logoutBackdrop = document.getElementById('logoutBackdrop');
const logoutContent = document.getElementById('logoutContent');

function openLogoutConfirmation() {
    // Tutup menu kecil dulu
    settingsPopover.classList.add('hidden');

    // Buka modal besar
    logoutModal.classList.remove('hidden');
    setTimeout(() => {
        logoutBackdrop.classList.remove('opacity-0');
        logoutContent.classList.remove('scale-95', 'opacity-0');
        logoutContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeLogoutConfirmation() {
    // Animasi Tutup
    logoutBackdrop.classList.add('opacity-0');
    logoutContent.classList.remove('scale-100', 'opacity-100');
    logoutContent.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        logoutModal.classList.add('hidden');
    }, 300);
}
</script>