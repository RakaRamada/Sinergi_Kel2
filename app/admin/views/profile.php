<?php
// app/admin/views/profile.php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/bottom_nav.php';

// Safe Session Data
$avatar = $_SESSION['avatar_url'] ?? '/sinergi/public/assets/images/user.png';
if (empty($avatar)) $avatar = '/sinergi/public/assets/images/user.png';
$name = $_SESSION['nama_lengkap'] ?? 'Admin';
$role = $_SESSION['role_name'] ?? 'Administrator';
$username = $_SESSION['username'] ?? 'admin';
$email = $_SESSION['email'] ?? '-';
?>

<main class="flex-1 p-4 sm:p-6 ml-0 lg:ml-20 bg-gray-50 min-h-screen flex items-center justify-center pb-24 lg:pb-6 overflow-x-hidden">

    <div class="max-w-md w-full">

        <!-- Profile Card -->
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 relative group">

            <!-- Decor Header -->
            <div class="h-32 bg-gradient-to-r from-gray-900 to-black relative">
                <div class="absolute inset-0 bg-[url('/sinergi/public/assets/images/noise.png')] opacity-20"></div>
            </div>

            <!-- Avatar & Content -->
            <div class="px-8 pb-8 text-center relative">

                <!-- Avatar -->
                <div class="relative -mt-16 inline-block mb-4">
                    <img src="<?= htmlspecialchars($avatar) ?>"
                        class="w-32 h-32 rounded-full border-4 border-white shadow-2xl object-cover bg-gray-200">
                    <div class="absolute bottom-2 right-2 w-6 h-6 bg-green-500 border-4 border-white rounded-full">
                    </div>
                </div>

                <!-- Info -->
                <h1 class="text-2xl font-bold text-gray-900 mb-1"><?= htmlspecialchars($name) ?></h1>
                <p class="text-sm text-gray-500 font-medium mb-2">@<?= htmlspecialchars($username) ?></p>

                <div
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-xs font-bold uppercase tracking-wider mb-6">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <?= htmlspecialchars($role) ?>
                </div>

                <!-- Details -->
                <div class="space-y-3 mb-8 text-left">
                    <div
                        class="flex items-center gap-4 p-3 rounded-xl bg-gray-50 border border-gray-100 hover:bg-gray-100 transition-colors">
                        <div
                            class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="overflow-hidden">
                            <div class="text-xs text-gray-400 font-bold uppercase">Email Address</div>
                            <div class="text-sm font-medium text-gray-900 truncate">admin@sinergi.com</div>
                        </div>
                    </div>
                </div>

                <!-- Logout Button -->
                <button onclick="confirmLogout()"
                    class="w-full flex items-center justify-center gap-2 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-100 hover:border-red-600 py-3 rounded-xl font-bold transition-all shadow-sm hover:shadow-lg active:scale-95 cursor-pointer group">
                    <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Sign Out
                </button>

            </div>
        </div>

        <p class="text-center text-gray-400 text-xs mt-8">Sinergi Admin Panel &copy; <?= date('Y') ?></p>

    </div>
</main>

<!-- Load Modern Modal Script -->
<script src="/sinergi/public/assets/js/admin-modal.js"></script>

<script>
async function confirmLogout() {
    if (await showModernConfirm('Sign Out?', 'Anda akan keluar dari sesi admin.')) {
        window.location.href = '/sinergi/index.php?page=logout';
    }
}
</script>