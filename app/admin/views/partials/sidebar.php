<?php
// app/admin/views/partials/sidebar.php
// Ambil page saat ini untuk highlight menu aktif
$currentPage = $_GET['page'] ?? '';
?>
<aside
    class="fixed top-0 left-0 h-full w-20 bg-white border-r border-gray-200 flex flex-col items-center py-6 space-y-6 z-40">

    <!-- Logo -->
    <a href="/sinergi/index.php?page=admin-dashboard" class="block p-1 hover:opacity-90 transition-opacity"
        title="Dashboard">
        <img src="/sinergi/public/assets/icons/logo.svg" alt="Logo" class="w-8 h-8">
    </a>

    <!-- Menu 1: Report Dashboard -->
    <a href="/Sinergi/index.php?page=admin-dashboard"
        class="block p-2 rounded transition-all duration-200 <?php echo ($currentPage == 'admin-dashboard' || $currentPage == 'admin-detail-report') ? 'bg-indigo-100 shadow-sm' : 'hover:bg-gray-50'; ?>"
        title="Laporan">
        <!-- Icon Report -->
        <img src="/Sinergi/public/assets/icons/report.svg" alt="Laporan"
            class="w-6 h-6 <?php echo ($currentPage == 'admin-dashboard') ? 'opacity-100' : 'opacity-70'; ?>">
    </a>

    <!-- Menu 2: Analytics (FIXED LINK) -->
    <a href="/Sinergi/index.php?page=admin-analytics"
        class="block p-2 rounded transition-all duration-200 <?php echo ($currentPage == 'admin-analytics') ? 'bg-indigo-100 shadow-sm' : 'hover:bg-gray-50'; ?>"
        title="Analitik">
        <!-- Icon Chart/Analytics -->
        <svg xmlns="http://www.w3.org/2000/svg"
            class="w-6 h-6 <?php echo ($currentPage == 'admin-analytics') ? 'text-indigo-600' : 'text-gray-500'; ?>"
            fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
        </svg>
    </a>

    <div class="flex-1"></div>

    <!-- Profile -->
    <div class="mt-auto"> <a href="/Sinergi/index.php?page=admin-profile"
            class="block p-1 hover:bg-gray-50 rounded transition-colors" title="Profil Admin">
            <img src="<?= $_SESSION['avatar_url'] ?? '/Sinergi/public/assets/icons/profile.svg' ?>" alt="Profil"
                class="w-10 h-10 rounded-full object-cover border border-gray-200">
        </a>
    </div>

</aside>