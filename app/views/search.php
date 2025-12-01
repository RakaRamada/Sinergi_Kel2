<?php
// File: app/views/search.php
// Variabel $currentTab, $query, $forum_results, dan $user_results
// sudah dikirim oleh SearchController.php

// (Kita tidak perlu memanggil header/footer, karena index.php sudah mengurusnya)
?>

<main class="col-span-6 border-r border-gray-200">

    <div class="p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <form action="index.php" method="GET" class="relative">
            <input type="hidden" name="page" value="search">
            <input type="text" name="q" placeholder="Cari forum atau orang..." value="<?= htmlspecialchars($query) ?>"
                class="w-full py-2 pl-10 pr-4 border border-gray-300 rounded-full bg-gray-100 focus:outline-none focus:border-blue-300">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        </form>
    </div>

    <div class="bg-white sticky top-0 z-10 border-b border-gray-200">
        <div class="flex">
            <!-- Tab Forum -->
            <a href="index.php?page=search&q=<?= urlencode($query) ?>&tab=forum" 
               class="flex-1 text-center py-3 font-semibold hover:bg-gray-50 transition-colors <?= $currentTab === 'forum' ? 'border-b-2 border-black text-black' : 'text-gray-500' ?>">
               Forum
            </a>
            
            <!-- Tab Orang -->
            <a href="index.php?page=search&q=<?= urlencode($query) ?>&tab=orang" 
               class="flex-1 text-center py-3 font-semibold hover:bg-gray-50 transition-colors <?= $currentTab === 'orang' ? 'border-b-2 border-black text-black' : 'text-gray-500' ?>">
               Orang
            </a>
        </div>
    </div>

    <!-- CONTENT AREA -->
    <div class="p-0"> 
        <?php if (empty($query)): ?>
            <div class="p-8 text-center text-gray-500">
                <p>Ketik nama atau topik untuk mulai mencari...</p>
            </div>
        <?php else: ?>
            
            <!-- --- LOGIKA TAB FORUM --- -->
            <?php if ($currentTab === 'forum'): ?>
                <div class="divide-y divide-gray-100">
                    <?php if (empty($forum_results)): ?>
                        <div class="p-8 text-center text-gray-500">
                            <p>Tidak ditemukan forum dengan kata kunci "<strong><?= htmlspecialchars($query) ?></strong>"</p>
                        </div>
                    <?php else: ?>
                        <!-- Loop hasil forum -->
                        <?php foreach ($forum_results as $forum): ?>
                            <div class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                                <!-- Info Forum (Kiri) -->
                                <div class="flex items-center space-x-3 overflow-hidden">
                                    <!-- Icon Forum (Placeholder/Avatar) -->
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-gray-900 truncate"><?= htmlspecialchars($forum['judul']) ?></h3>
                                        <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($forum['deskripsi']) ?></p>
                                    </div>
                                </div>

                                <!-- Tombol Aksi (Kanan) -->
                                <div class="flex-shrink-0 ml-4">
                                    <?php 
                                    // Logika status membership (jika ada data is_member dari controller)
                                    $is_member = isset($forum['is_member']) && $forum['is_member']; 
                                    ?>
                                    
                                    <?php if ($is_member): ?>
                                        <button class="px-4 py-1.5 rounded-full border border-gray-300 text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-colors">
                                            Member
                                        </button>
                                    <?php else: ?>
                                        <form action="index.php?page=join-forum" method="POST">
                                            <input type="hidden" name="forum_id" value="<?= $forum['forum_id'] ?? 0 ?>">
                                            <button type="submit" class="px-4 py-1.5 rounded-full bg-black text-white text-sm font-semibold hover:bg-gray-800 transition-colors">
                                                Join
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            <!-- --- LOGIKA TAB ORANG --- -->
            <?php elseif ($currentTab === 'orang'): ?>
                
                <?php if (empty($user_results)): ?>
                    <div class="p-8 text-center text-gray-500">
                         <p>Tidak ditemukan pengguna dengan nama "<strong><?= htmlspecialchars($query) ?></strong>"</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($user_results as $user): ?>
                            
                            <!-- Link ke Profil -->
                            <a href="index.php?page=profile&id=<?= $user['user_id'] ?>" class="block hover:bg-gray-50 transition-colors group">
                                <div class="flex items-center p-4 space-x-3">
                                    
                                    <!-- Avatar -->
                                    <img src="<?= !empty($user['avatar_url']) ? $user['avatar_url'] : '/Sinergi/public/assets/images/default_avatar.png' ?>" 
                                         alt="<?= htmlspecialchars($user['username']) ?>" 
                                         class="w-12 h-12 rounded-full object-cover border border-gray-200 group-hover:border-gray-300">
                                    
                                    <!-- Info User -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-bold text-gray-900 truncate">
                                                <?= htmlspecialchars($user['nama_lengkap']) ?>
                                            </h3>
                                            <!-- Badge Role -->
                                            <?php if(isset($user['role_id']) && $user['role_id'] == 2): ?>
                                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full ml-2">Dosen</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-gray-500 truncate">
                                            @<?= htmlspecialchars($user['username']) ?>
                                        </p>
                                        <?php if (!empty($user['bio'])): ?>
                                            <p class="text-xs text-gray-400 mt-1 truncate">
                                                <?= htmlspecialchars($user['bio']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            </a>

                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        <?php endif; ?>
    </div>
</main>

<?php 
// Kita asumsikan sidebar kanan dipanggil oleh index.php (jika Anda sudah Rombak Besar)
// Jika belum, biarkan baris ini:
require 'partials/sidebar_kanan.php'; 
?>