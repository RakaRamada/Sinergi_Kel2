<?php
// File: app/views/forum_details.php
// Variabel $forum_info, $forum_members, dan $is_creator dikirim dari ForumController.php
?>

<main class="col-span-6 border-r border-gray-200">

    <div
        class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <a href="javascript:history.back()" title="Kembali" class="p-2 rounded-full hover:bg-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <h2 class="text-xl font-bold">Info Forum</h2>
    </div>

    <div class="p-6 space-y-6">

        <div class="flex flex-col items-center">
            <?php
                // Logika Gambar Profil
                $image_path = '/Sinergi/public/assets/images/user.png'; // Default
                if (!empty($forum_info['forum_image'])) {
                    $image_path = '/Sinergi/public/uploads/forum_profiles/' . htmlspecialchars($forum_info['forum_image']);
                }
            ?>
            <img src="<?= $image_path ?>" alt="Profil Forum" class="w-32 h-32 rounded-full mb-4 object-cover">

            <div class="flex items-center space-x-2">
                <h2 class="text-2xl font-bold"><?= htmlspecialchars($forum_info['nama_forum']) ?></h2>

                <?php if ($is_creator): // Tampilkan tombol 'Edit' hanya jika user adalah pembuatnya ?>
                <a href="index.php?page=edit-forum&forum_id=<?= $forum_info['forum_id'] ?>" title="Edit Forum"
                    class="text-gray-400 hover:text-blue-500">
                    <img src="/Sinergi/public/assets/icons/edit.svg" alt="edit" class="w-6 h-7">
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="border border-gray-200 rounded-lg p-4">
            <h3 class="font-semibold text-gray-800 mb-1">Deskripsi</h3>
            <?php 
                $deskripsi_raw = $forum_info['deskripsi'] ?? null;
                $deskripsi_string = ($deskripsi_raw instanceof OCILob) ? $deskripsi_raw->read($deskripsi_raw->size()) : (is_string($deskripsi_raw) ? $deskripsi_raw : 'Tidak ada deskripsi.');
            ?>
            <p class="text-gray-600 whitespace-pre-wrap"><?= htmlspecialchars($deskripsi_string) ?></p>
        </div>

        <div class="border border-gray-200 rounded-lg">
            <h3 class="font-semibold text-gray-800 p-4 border-b border-gray-200">Anggota (<?= count($forum_members) ?>)
            </h3>
            <div class="max-h-60 overflow-y-auto">
                <?php if (!empty($forum_members)): ?>
                <?php foreach ($forum_members as $member): ?>
                <div class="flex items-center space-x-3 p-4 border-b border-gray-100">
                    <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-10 h-10 rounded-full">
                    <div>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($member['nama_lengkap']) ?></p>
                        <p class="text-sm text-gray-500">@<?= htmlspecialchars($member['username']) ?> -
                            <?= htmlspecialchars($member['role_name']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="p-4 text-gray-500">Tidak ada anggota.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="border border-gray-200 rounded-lg">
            <div class="p-4 text-gray-500">Media (Placeholder)</div>
            <div class="p-4 border-t border-gray-200 text-gray-500">Dokumen (Placeholder)</div>
        </div>

        <div class="border border-red-200 rounded-lg">
            <a href="index.php?page=exit-forum&forum_id=<?= $forum_info['forum_id'] ?>"
                onclick="return confirm('Apakah Anda yakin ingin keluar dari forum ini?');"
                class="block p-4 text-red-600 font-semibold text-center hover:bg-red-50 rounded-lg transition-colors duration-150">
                Keluar Forum
            </a>
        </div>

    </div>
</main>

<aside class="col-span-4">
    <?php 
        // Panggil sidebar kanan
        require 'app/views/partials/sidebar_kanan.php'; 
    ?>
</aside>