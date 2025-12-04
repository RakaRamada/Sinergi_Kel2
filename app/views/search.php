<div class="col-span-10 flex h-[calc(100vh-3px)] bg-gray-100 overflow-hidden relative">

    <main class="flex-1 flex flex-col h-full bg-white border-r border-gray-200 min-w-0">

        <div class="p-4 border-b border-gray-200 bg-white/95 backdrop-blur-sm z-10 shrink-0">
            <form action="index.php" method="GET" class="relative mb-4">
                <input type="hidden" name="page" value="search">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($currentTab) ?>">

                <input type="text" name="q" placeholder="Cari group diskusi atau teman..."
                    value="<?= htmlspecialchars($query) ?>" autocomplete="off"
                    class="w-full py-2.5 pl-11 pr-4 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-gray-800">

                <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </form>

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

        <div class="flex-1 overflow-y-auto custom-scrollbar">

            <?php if ($currentTab === 'group'): ?>
            <?php if (!empty($group_results)): ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($group_results as $group): ?>
                <?php
                        // --- LOGIKA ROLE EKSTERNAL ---
                        // Role ID: 3 = Alumni, 4 = Mitra Industri
                        // Jika user adalah eksternal, PAKSA semua grup terlihat PRIVATE
                        $is_external_user = in_array($role_id, [3, 4]);

                        $deskripsi_raw = $group['deskripsi'] ?? null;
                        $deskripsi_string = ($deskripsi_raw instanceof OCILob) ? $deskripsi_raw->read($deskripsi_raw->size()) : (is_string($deskripsi_raw) ? $deskripsi_raw : '');
                        $deskripsi = !empty($deskripsi_string) ? htmlspecialchars($deskripsi_string) : 'Tidak ada deskripsi.';
                        
                        $image_path = '/Sinergi/public/assets/images/user.png'; 
                        if (!empty($group['group_image'])) {
                            $image_path = '/Sinergi/public/uploads/group_profiles/' . htmlspecialchars($group['group_image']);
                        }

                        // Status Member Asli
                        $status = $group['membership_status'] ?? null;
                        
                        // Status Private Asli dari Database
                        $real_is_private = ($group['is_private'] == 1);

                        // Status Tampilan (Visual Only)
                        // Jika eksternal user, paksa jadi TRUE (Private). Jika bukan, ikuti database.
                        $display_is_private = $is_external_user ? true : $real_is_private;
                    ?>

                <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between gap-4">

                    <a href="index.php?page=group-details&group_id=<?= $group['group_id'] ?>&from=search"
                        class="flex items-center flex-1 min-w-0 cursor-pointer group">

                        <img src="<?= $image_path ?>"
                            class="w-12 h-12 rounded-xl object-cover border border-gray-100 mr-4 bg-white shrink-0 group-hover:opacity-90 transition">

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <h3
                                    class="font-bold text-gray-900 truncate text-base group-hover:text-blue-600 transition">
                                    <?= htmlspecialchars($group['nama_group']) ?>
                                </h3>

                                <?php if($display_is_private): ?>
                                <span
                                    class="bg-gray-100 text-gray-600 text-[10px] px-1.5 py-0.5 rounded border border-gray-200 flex items-center gap-1 shrink-0"
                                    title="Grup Tertutup">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                        </path>
                                    </svg>
                                    <?= $is_external_user ? 'Tertutup (Eksternal)' : 'Privat' ?>
                                </span>
                                <?php else: ?>
                                <span
                                    class="bg-blue-50 text-blue-600 text-[10px] px-1.5 py-0.5 rounded border border-blue-100 flex items-center gap-1 shrink-0"
                                    title="Grup Terbuka">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9">
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
                            class="inline-flex items-center justify-center px-5 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50 transition">
                            Buka
                        </a>

                        <?php elseif ($status === 'pending' || $status === 'invited'): ?>
                        <button disabled
                            class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-full text-gray-400 bg-gray-100 cursor-not-allowed">
                            Menunggu
                        </button>

                        <?php else: ?>
                        <?php if ($display_is_private): ?>
                        <a href="index.php?page=join-group&group_id=<?= $group['group_id'] ?>&q=<?= htmlspecialchars($query) ?>"
                            class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-black hover:bg-gray-800 transition"
                            title="Ajukan Permintaan Bergabung">
                            Request Join
                        </a>
                        <?php else: ?>
                        <a href="index.php?page=join-group&group_id=<?= $group['group_id'] ?>&q=<?= htmlspecialchars($query) ?>"
                            class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-black hover:bg-gray-800 transition">
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
                        class="px-4 py-1.5 border border-black text-black font-bold text-xs rounded-full hover:bg-gray-900 hover:text-white transition">
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

    <aside class="w-80 hidden lg:block border-l border-gray-200 bg-white h-full overflow-y-auto">
        <?php require 'app/views/partials/sidebar_kanan.php'; ?>
    </aside>

</div>