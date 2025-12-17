<?php
// app/admin/views/partials/sidebar.php
// Ambil page saat ini untuk highlight menu aktif
$currentPage = $_GET['page'] ?? '';
?>
<aside
    class="fixed top-0 left-0 h-full w-20 bg-white border-r border-gray-200 hidden lg:flex flex-col items-center py-6 space-y-6 z-40">

    <!-- Logo -->
    <a href="/sinergi/index.php?page=admin-dashboard" class="block p-1 hover:opacity-90 transition-opacity"
        title="Dashboard">
        <img src="/sinergi/public/assets/icons/logo.svg" alt="Logo" class="w-8 h-8">
    </a>

    <!-- Menu 1: Report Dashboard -->
    <a href="/sinergi/index.php?page=admin-dashboard"
        class="block p-2 rounded transition-all duration-200 <?php echo ($currentPage == 'admin-dashboard' || $currentPage == 'admin-detail-report') ? 'bg-gray-200 shadow-sm' : 'hover:bg-gray-50'; ?>"
        title="Laporan">
        <!-- Icon Report -->
        <img src="/sinergi/public/assets/icons/report.svg" alt="Laporan"
            class="w-6 h-6 <?php echo ($currentPage == 'admin-dashboard') ? 'opacity-100' : 'opacity-70'; ?>">
    </a>

    <!-- Menu 2: Analytics (FIXED LINK) -->
    <a href="/sinergi/index.php?page=admin-analytics"
        class="block p-2 rounded transition-all duration-200 <?php echo ($currentPage == 'admin-analytics') ? 'bg-gray-200 shadow-sm' : 'hover:bg-gray-50'; ?>"
        title="Analitik">
        <!-- Icon Chart/Analytics -->
        <svg xmlns="http://www.w3.org/2000/svg"
            class="w-6 h-6 <?php echo ($currentPage == 'admin-analytics') ? 'text-gray-600' : 'text-gray-500'; ?>"
            fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
        </svg>
    </a>
    <!-- Menu 3: Blacklist User -->
    <a href="/sinergi/index.php?page=admin-blacklist"
        class="block p-2 rounded transition-all duration-200 <?php echo ($currentPage == 'admin-blacklist') ? 'bg-gray-200 shadow-sm' : 'hover:bg-gray-50'; ?>"
        title="Blacklist User">
        <svg xmlns="http://www.w3.org/2000/svg"
            class="w-6 h-6 <?php echo ($currentPage == 'admin-blacklist') ? 'text-gray-900' : 'text-gray-500'; ?>"
            fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
            </path>
        </svg>
    </a>

    <div class="flex-1"></div>

    <!-- Profile -->
    <div class="mt-auto"> <a href="/sinergi/index.php?page=admin-profile"
            class="block p-1 hover:bg-gray-50 rounded transition-colors" title="Profil Admin">
            <img src="<?= $_SESSION['avatar_url'] ?? '/sinergi/public/assets/icons/profile.svg' ?>" alt="Profil"
                class="w-10 h-10 rounded-full object-cover border border-gray-200">
        </a>
    </div>

</aside>