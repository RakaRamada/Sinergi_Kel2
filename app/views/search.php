<style>
/* Class untuk menyembunyikan scrollbar di semua browser */
.hide-scrollbar {
    -ms-overflow-style: none;
    /* Internet Explorer 10+ */
    scrollbar-width: none;
    /* Firefox */
}

.hide-scrollbar::-webkit-scrollbar {
    display: none;
    /* Safari and Chrome */
}
</style>

<main class="col-span-6 border-r border-gray-200 bg-white h-[calc(100vh-3px)] overflow-y-auto hide-scrollbar relative">

    <div class="sticky top-0 z-20 bg-white/95 backdrop-blur-sm border-b border-gray-200">
        <div class="p-4">
            <form action="index.php" method="GET" class="relative">
                <input type="hidden" name="page" value="search">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($currentTab) ?>">

                <input type="text" name="q" placeholder="Cari group diskusi atau teman..."
                    value="<?= htmlspecialchars($query) ?>" autocomplete="off"
                    class="w-full py-2.5 pl-11 pr-4 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-black focus:bg-white transition text-gray-800">

                <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </form>
        </div>

        <div class="grid grid-cols-2 w-full">
            <a href="index.php?page=search&tab=group&q=<?= htmlspecialchars($query) ?>" class="w-full py-3 text-sm font-bold text-center border-b-2 transition-colors focus:outline-none
                <?= $currentTab === 'group' 
                    ? 'border-black text-black bg-gray-50' 
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' ?>">
                Grup Diskusi
            </a>

            <a href="index.php?page=search&tab=orang&q=<?= htmlspecialchars($query) ?>" class="w-full py-3 text-sm font-bold text-center border-b-2 transition-colors focus:outline-none
                <?= $currentTab === 'orang' 
                    ? 'border-black text-black bg-gray-50' 
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' ?>">
                Orang
            </a>
        </div>
    </div>

    <div class="pb-20">
        <?php if ($currentTab === 'group'): ?>
        <?php if (!empty($group_results)): ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($group_results as $group): ?>
            <?php
                        // Logika Display
                        $is_external_user = in_array($role_id, [3, 4]);
                        $deskripsi_raw = $group['deskripsi'] ?? null;
                        $deskripsi_string = ($deskripsi_raw instanceof OCILob) ? $deskripsi_raw->read($deskripsi_raw->size()) : (is_string($deskripsi_raw) ? $deskripsi_raw : '');
                        $deskripsi = !empty($deskripsi_string) ? htmlspecialchars($deskripsi_string) : 'Tidak ada deskripsi.';
                        
                        $image_path = !empty($group['group_image']) 
                            ? '/Sinergi/public/uploads/group_profiles/' . htmlspecialchars($group['group_image'])
                            : '/Sinergi/public/assets/images/user.png';

                        $status = $group['membership_status'] ?? null;
                        $real_is_private = ($group['is_private'] == 1);
                        $display_is_private = $is_external_user ? true : $real_is_private;
                    ?>

            <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between gap-3">
                <a href="index.php?page=group-details&group_id=<?= $group['group_id'] ?>&from=search"
                    class="flex items-center flex-1 min-w-0 cursor-pointer group">

                    <img src="<?= $image_path ?>"
                        class="w-12 h-12 rounded-xl object-cover border border-gray-100 mr-3 bg-white shrink-0 group-hover:opacity-90 transition">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <h3 class="font-bold text-gray-900 truncate text-base group-hover:text-gray-600 transition">
                                <?= htmlspecialchars($group['nama_group']) ?>
                            </h3>
                            <?php if($display_is_private): ?>
                            <span
                                class="bg-gray-100 text-gray-500 text-[10px] px-1.5 py-0.5 rounded border border-gray-200 flex items-center gap-1 shrink-0">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                    </path>
                                </svg>
                                Privat
                            </span>
                            <?php else: ?>
                            <span
                                class="bg-gray-50 text-gray-500 text-[10px] px-1.5 py-0.5 rounded border border-gray-100 flex items-center gap-1 shrink-0">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                    </path>
                                </svg>
                                Publik
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-500 truncate group-hover:text-gray-600 transition">
                            <?= $deskripsi ?></p>
                    </div>
                </a>

                <div class="shrink-0 z-10">
                    <?php if ($status === 'active'): ?>
                    <a href="index.php?page=messages&group_id=<?= $group['group_id'] ?>"
                        class="px-4 py-1.5 border border-gray-300 shadow-sm text-sm font-bold rounded-full text-gray-700 bg-white hover:bg-gray-50 transition">
                        Buka
                    </a>
                    <?php elseif ($status === 'pending' || $status === 'invited'): ?>
                    <button disabled
                        class="px-4 py-1.5 border border-transparent text-sm font-bold rounded-full text-gray-400 bg-gray-100 cursor-not-allowed">
                        Menunggu
                    </button>
                    <?php else: ?>
                    <?php if ($display_is_private): ?>
                    <a href="index.php?page=join-group&group_id=<?= $group['group_id'] ?>&q=<?= htmlspecialchars($query) ?>"
                        class="px-4 py-1.5 border border-transparent text-sm font-bold rounded-full shadow-sm text-white bg-black hover:bg-gray-800 transition">
                        Request Join
                    </a>
                    <?php else: ?>
                    <a href="index.php?page=join-group&group_id=<?= $group['group_id'] ?>&q=<?= htmlspecialchars($query) ?>"
                        class="px-4 py-1.5 border border-transparent text-sm font-bold rounded-full shadow-sm text-white bg-black hover:bg-gray-800 transition">
                        Join Group
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center h-64 text-gray-400">
            <p>Tidak ada group ditemukan.</p>
        </div>
        <?php endif; ?>

        <?php elseif ($currentTab === 'orang'): ?>
        <?php if (!empty($user_results)): ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($user_results as $user): ?>
            <?php 
                    $u_avatar = !empty($user['avatar_url']) ? '/Sinergi/public/uploads/avatars/' . $user['avatar_url'] : '/Sinergi/public/assets/images/user.png';
                ?>
            <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between">
                <div class="flex items-center">
                    <img src="<?= $u_avatar ?>"
                        class="w-10 h-10 rounded-full mr-3 bg-gray-200 object-cover border border-gray-100">
                    <div>
                        <p class="font-bold text-gray-900"><?= htmlspecialchars($user['nama_lengkap']) ?></p>
                        <p class="text-sm text-gray-500">@<?= htmlspecialchars($user['username']) ?></p>
                    </div>
                </div>

                <a href="index.php?page=profile&id=<?= $user['user_id'] ?>"
                    class="px-4 py-1.5 border border-black text-black font-bold text-xs rounded-full hover:bg-black hover:text-white transition">
                    Lihat Profil
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center h-64 text-gray-400">
            <p>Tidak ada user ditemukan.</p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php require 'app/views/partials/sidebar_kanan.php'; ?>