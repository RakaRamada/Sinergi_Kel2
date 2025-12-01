<?php
// app/admin/views/profile.php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

<main class="flex-1 p-6 ml-0 lg:ml-20">
    <div class="max-w-xl mx-auto">
        <div class="bg-white border rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-6">Profil Admin</h2>

            <div class="flex items-center gap-4 mb-6">
                <img src="<?= $_SESSION['avatar_url'] ?? '/Sinergi/public/assets/images/profile.svg' ?>"
                    class="w-16 h-16 rounded-full object-cover border-2" alt="admin avatar">
                <div>
                    <div class="font-semibold text-lg"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin') ?>
                    </div>
                    <div class="text-xs text-blue-600 font-medium mt-1">
                        <?= htmlspecialchars($_SESSION['role_name'] ?? 'Admin') ?></div>
                </div>
            </div>

            <a href="/Sinergi/index.php?page=logout"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition"
                onclick="return confirm('Yakin ingin logout?')">
                🚪 Logout
            </a>
        </div>
    </div>
    </div>
</main>