<nav class="col-span-2 border-r border-gray-200 px-4">
    <div class="flex flex-col items-end h-screen py-6 sticky top-0">
        <?php
        // Ambil nama halaman saat ini dari URL.
        $currentPage = $_GET['page'] ?? 'dashboard';
        ?>

        <div class="flex flex-col items-center space-y-4">

            <a href="index.php?page=dashboard" title="Sinergi" class="w-12 h-12 flex items-center justify-center">
                <img src="/Sinergi/public/assets/icons/logo.svg" alt="Logo" class="w-8 h-8">
            </a>

            <a href="index.php?page=dashboard" title="Beranda"
                class="w-12 h-12 flex items-center justify-center rounded-lg 
                      <?php echo ($currentPage == 'dashboard') ? 'bg-gray-200 text-black' : 'text-gray-500 hover:bg-gray-100'; ?>">
                <img src="/Sinergi/public/assets/icons/home.svg" alt="Beranda" class="w-7 h-7">
            </a>

            <a href="index.php?page=search" title="Cari"
                class="w-12 h-12 flex items-center justify-center rounded-lg 
                      <?php echo ($currentPage == 'search') ? 'bg-gray-200 text-black' : 'text-gray-500 hover:bg-gray-100'; ?>">
                <img src="/Sinergi/public/assets/icons/search.svg" alt="Cari" class="w-7 h-7">
            </a>

            <a href="index.php?page=notification" title="Notifikasi"
                class="w-12 h-12 flex items-center justify-center rounded-lg 
                      <?php echo ($currentPage == 'notification') ? 'bg-gray-200 text-black' : 'text-gray-500 hover:bg-gray-100'; ?>">
                <img src="/Sinergi/public/assets/icons/notification.svg" alt="Notifikasi" class="w-8 h-8">
            </a>

            <a href="index.php?page=messages" title="Pesan"
                class="w-12 h-12 flex items-center justify-center rounded-lg 
                       <?php echo ($currentPage == 'messages') ? 'bg-gray-200 text-black' : 'text-gray-500 hover:bg-gray-100'; ?>">
                <img src="/Sinergi/public/assets/icons/chat.svg" alt="Pesan" class="w-7 h-7">
            </a>

            <a href="index.php?page=settings" title="Pengaturan"
                class="w-12 h-12 flex items-center justify-center rounded-lg 
                      <?php echo ($currentPage == 'settings') ? 'bg-gray-200 text-black' : 'text-gray-500 hover:bg-gray-100'; ?>">
                <img src="/Sinergi/public/assets/icons/settings.svg" alt="Pengaturan" class="w-7 h-7">
            </a>
        </div>

        <div class="mt-auto">
            <a href="index.php?page=profile" title="Profil Anda">
                <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Anda" class="w-10 h-10 rounded-full">
            </a>
        </div>
    </div>
</nav>