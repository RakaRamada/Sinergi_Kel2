<style>
/* ====== SEMBUNYIKAN SCROLLBAR DI SEMUA BROWSER ====== */
.sidebar-kanan-container {
    /* Perhatikan: Jika menggunakan 100vh, pastikan tidak tumpang tindih dengan header */
    overflow-y: scroll;
    height: 100vh;
    scrollbar-width: none;
    /* Firefox */
    -ms-overflow-style: none;
    /* Edge lama & IE */
}

/* Chrome, Safari, Edge baru */
.sidebar-kanan-container::-webkit-scrollbar {
    width: 0;
    height: 0;
}
</style>

<aside class="col-span-4 p-6 pt-3 space-y-6 bg-transparent">

    <div class="sticky top-4 w-full">
        <div class="border border-black bg-white p-4 rounded-lg">
            <h3 class="font-bold text-lg mb-3">Mungkin Anda Kenal</h3>
            <div class="space-y-3">

                <?php 
                // Pastikan variabel $recommendedUsers tersedia dari Controller
                if (isset($recommendedUsers) && is_array($recommendedUsers) && !empty($recommendedUsers)): 
                ?>
                <?php foreach ($recommendedUsers as $user): ?>

                <!-- Item Rekomendasi User - Dibuat jadi link ke halaman profile -->
                <a href="index.php?page=profile&user_id=<?php echo htmlspecialchars($user['user_id']); ?>"
                    class="flex items-center justify-between p-1 rounded-lg hover:bg-gray-100 transition duration-150">

                    <div class="flex items-center">
                        <!-- Avatar User -->
                        <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar"
                            class="w-10 h-10 rounded-full mr-3 object-cover">

                        <!-- Nama Lengkap (Truncated di CSS atau dipotong di PHP jika terlalu panjang) -->
                        <span class="font-semibold text-sm truncate max-w-[120px]">
                            <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                        </span>
                    </div>

                    <!-- Tombol Follow/Lihat Profil bisa ditambahkan di sini, jika perlu -->
                    <!-- <button class="text-blue-500 hover:text-blue-700 text-xs font-bold px-3 py-1 rounded-full border border-blue-500">Lihat</button> -->
                </a>

                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-sm text-gray-500">Tidak ada rekomendasi pengguna saat ini.</p>
                <?php endif; ?>

            </div>
        </div>

        <div class="border border-black bg-white p-4 rounded-lg mt-6">
            <h3 class="font-bold text-lg mb-3">Rekomendasi Komunitas</h3>
            <div class="space-y-3">
                <!-- Konten Komunitas statis/placeholder tetap di sini -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <img src="/Sinergi/public/assets/images/user.png" alt="Avatar"
                            class="w-10 h-10 rounded-full mr-3">
                        <span class="font-semibold text-sm">Timpa Teks.</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <img src="/Sinergi/public/assets/images/user.png" alt="Avatar"
                            class="w-10 h-10 rounded-full mr-3">
                        <span class="font-semibold text-sm">Web Lanjut</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <img src="/Sinergi/public/assets/images/user.png" alt="Avatar"
                            class="w-10 h-10 rounded-full mr-3">
                        <span class="font-semibold text-sm">Grafika Komputer</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>