<?php
$currentPage = $_GET['page'] ?? 'admin-dashboard';
// Admin Bottom Nav
?>
<div class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 z-50 lg:hidden safe-area-bottom">
    <div class="grid grid-cols-4 h-16">
        <!-- Dashboard -->
        <a href="index.php?page=admin-dashboard"
            class="flex flex-col items-center justify-center space-y-1 <?php echo ($currentPage == 'admin-dashboard' || $currentPage == 'admin-detail-report') ? 'text-black' : 'text-gray-400 hover:text-gray-600'; ?>">
            <img src="/sinergi/public/assets/icons/report.svg" alt="Laporan"
                class="w-6 h-6 <?php echo ($currentPage == 'admin-dashboard') ? 'opacity-100' : 'opacity-50 grayscale'; ?>">
            <span class="text-[10px] font-medium">Laporan</span>
        </a>

        <!-- Analytics -->
        <a href="index.php?page=admin-analytics"
            class="flex flex-col items-center justify-center space-y-1 <?php echo ($currentPage == 'admin-analytics') ? 'text-black' : 'text-gray-400 hover:text-gray-600'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6 <?php echo ($currentPage == 'admin-analytics') ? 'text-black' : 'text-gray-400'; ?>"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
            </svg>
            <span class="text-[10px] font-medium">Analitik</span>
        </a>

        <!-- Blacklist -->
        <a href="index.php?page=admin-blacklist"
            class="flex flex-col items-center justify-center space-y-1 <?php echo ($currentPage == 'admin-blacklist') ? 'text-black' : 'text-gray-400 hover:text-gray-600'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6 <?php echo ($currentPage == 'admin-blacklist') ? 'text-black' : 'text-gray-400'; ?>"
                fill="none" viewBox="0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                </path>
            </svg>
            <span class="text-[10px] font-medium">Blacklist</span>
        </a>

        <!-- Profile -->
        <a href="index.php?page=admin-profile"
            class="flex flex-col items-center justify-center space-y-1 <?php echo ($currentPage == 'admin-profile') ? 'text-black' : 'text-gray-400 hover:text-gray-600'; ?>">
            <div class="<?php echo ($currentPage == 'admin-profile') ? 'ring-2 ring-black rounded-full p-0.5' : ''; ?>">
                <img src="<?php echo $_SESSION['avatar_url'] ?? '/sinergi/public/assets/icons/profile.svg'; ?>"
                    alt="Profile" class="w-6 h-6 rounded-full object-cover bg-gray-100">
            </div>
            <span class="text-[10px] font-medium">Profil</span>
        </a>
    </div>
</div>

<!-- Spacer -->
<div class="h-16 lg:hidden"></div>
