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

    <div class="flex border-b border-gray-200">
        <a href="index.php?page=search&tab=forum&q=<?= htmlspecialchars($query) ?>"
            class="flex-1 text-center py-3 font-semibold hover:bg-gray-100 <?php if ($currentTab === 'forum') echo 'border-b-2 border-black text-black font-bold'; else echo 'text-gray-500'; ?>">
            Forum
        </a>
        <a href="index.php?page=search&tab=orang&q=<?= htmlspecialchars($query) ?>"
            class="flex-1 text-center py-3 font-semibold hover:bg-gray-100 <?php if ($currentTab === 'orang') echo 'border-b-2 border-black text-black font-bold'; else echo 'text-gray-500'; ?>">
            Orang
        </a>
    </div>

    <div>
        <?php if (empty($query)): ?>
        <div class="p-4 text-center text-gray-500">
            <p>Mulai pencarian Anda dengan mengetik di atas.</p>
        </div>

        <?php elseif ($currentTab === 'forum'): ?>
        <?php if (!empty($forum_results)): ?>
        <?php foreach ($forum_results as $forum): ?>
        <?php
                    // Ambil data dan proses CLOB untuk deskripsi
                    $deskripsi_raw = $forum['deskripsi'] ?? null;
                    $deskripsi_string = ($deskripsi_raw instanceof OCILob) ? $deskripsi_raw->read($deskripsi_raw->size()) : (is_string($deskripsi_raw) ? $deskripsi_raw : '');
                    $deskripsi = !empty($deskripsi_string) ? htmlspecialchars($deskripsi_string) : 'Tidak ada deskripsi.';
                    
                    // Siapkan gambar (jika ada)
                    $image_path = '/Sinergi/public/assets/images/user.png'; // Default
                    if (!empty($forum['forum_image'])) {
                        $image_path = '/Sinergi/public/uploads/forum_profiles/' . htmlspecialchars($forum['forum_image']);
                    }
                    ?>
        <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="<?= $image_path ?>" alt="Avatar Forum" class="w-12 h-12 rounded-full mr-4 object-cover">
                    <div>
                        <p class="font-bold text-gray-800"><?= htmlspecialchars($forum['nama_forum']) ?></p>
                        <p class="text-sm text-gray-600 mt-1 truncate"><?= $deskripsi ?></p>
                    </div>
                </div>

                <a href="index.php?page=join-forum&forum_id=<?= $forum['forum_id'] ?>"
                    class="border border-black bg-black text-white font-bold py-1 px-4 rounded-full hover:bg-gray-700 text-sm whitespace-nowrap">
                    Join
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="p-4 text-center text-gray-500">
            <p>Tidak ada forum yang cocok dengan "<?= htmlspecialchars($query) ?>".</p>
        </div>
        <?php endif; ?>

        <?php elseif ($currentTab === 'orang'): ?>
        <?php if (!empty($user_results)): ?>
        <?php foreach ($user_results as $user): ?>
        <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-12 h-12 rounded-full mr-4">
                    <div>
                        <p class="font-bold text-gray-800"><?= htmlspecialchars($user['nama_lengkap']) ?></p>
                        <p class="text-sm text-gray-500">@<?= htmlspecialchars($user['username']) ?></p>
                    </div>
                </div>

                <a href="index.php?page=follow-user&user_id=<?= $user['user_id'] ?>"
                    class="border border-black font-bold py-1 px-4 rounded-full hover:bg-gray-100 text-sm whitespace-nowrap">
                    Follow
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="p-4 text-center text-gray-500">
            <p>Tidak ada orang yang cocok dengan "<?= htmlspecialchars($query) ?>".</p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php 
// Kita asumsikan sidebar kanan dipanggil oleh index.php (jika Anda sudah Rombak Besar)
// Jika belum, biarkan baris ini:
require 'partials/sidebar_kanan.php'; 
?>