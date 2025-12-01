<div class="col-span-10 flex h-[calc(100vh-64px)] bg-gray-100 overflow-hidden relative">

    <main class="flex-1 flex flex-col h-full bg-white border-r border-gray-200 min-w-0">

        <div class="p-4 border-b border-gray-200 bg-white/95 backdrop-blur-sm z-10 shrink-0">
            <form action="index.php" method="GET" class="relative">
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

            <div class="flex mt-4 space-x-6">
                <a href="index.php?page=search&tab=group&q=<?= htmlspecialchars($query) ?>"
                    class="pb-2 text-sm font-semibold transition border-b-2 <?= $currentTab === 'group' ? 'border-black text-black' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
                    Grup Diskusi
                </a>
                <a href="index.php?page=search&tab=orang&q=<?= htmlspecialchars($query) ?>"
                    class="pb-2 text-sm font-semibold transition border-b-2 <?= $currentTab === 'orang' ? 'border-black text-black' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
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
                            $deskripsi_raw = $group['deskripsi'] ?? null;
                            $deskripsi_string = ($deskripsi_raw instanceof OCILob) ? $deskripsi_raw->read($deskripsi_raw->size()) : (is_string($deskripsi_raw) ? $deskripsi_raw : '');
                            $deskripsi = !empty($deskripsi_string) ? htmlspecialchars($deskripsi_string) : 'Tidak ada deskripsi.';
                            
                            $image_path = '/Sinergi/public/assets/images/user.png'; 
                            if (!empty($group['group_image'])) {
                                $image_path = '/Sinergi/public/uploads/group_profiles/' . htmlspecialchars($group['group_image']);
                            }

                            // Status Member
                            $status = $group['membership_status'] ?? null;
                            $is_private = ($group['is_private'] == 1);
                        ?>

                <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between gap-4">
                    <div class="flex items-center flex-1 min-w-0">
                        <img src="<?= $image_path ?>"
                            class="w-12 h-12 rounded-xl object-cover border border-gray-100 mr-4 bg-white shrink-0">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <h3 class="font-bold text-gray-900 truncate text-base">
                                    <?= htmlspecialchars($group['nama_group']) ?></h3>
                                <?php if($is_private): ?>
                                <span
                                    class="bg-gray-100 text-gray-600 text-[10px] px-1.5 py-0.5 rounded border border-gray-200 flex items-center gap-1"
                                    title="Private Group">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                        </path>
                                    </svg>
                                    Privat
                                </span>
                                <?php else: ?>
                                <span
                                    class="bg-blue-50 text-blue-600 text-[10px] px-1.5 py-0.5 rounded border border-blue-100 flex items-center gap-1"
                                    title="Public Group">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9">
                                        </path>
                                    </svg>
                                    Publik
                                </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-500 truncate"><?= $deskripsi ?></p>
                        </div>
                    </div>

                    <div class="shrink-0">
                        <?php if ($status === 'active'): ?>
                        <a href="index.php?page=messages&group_id=<?= $group['group_id'] ?>"
                            class="inline-flex items-center justify-center px-5 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition">
                            Buka
                        </a>
                        <?php elseif ($status === 'pending'): ?>
                        <button disabled
                            class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-full text-gray-400 bg-gray-100 cursor-not-allowed">
                            Menunggu
                        </button>
                        <?php else: ?>
                        <a href="index.php?page=join-group&group_id=<?= $group['group_id'] ?>&q=<?= htmlspecialchars($query) ?>"
                            class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition">
                            <?= $is_private ? 'Request Join' : 'Join Group' ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="flex flex-col items-center justify-center h-64 text-gray-400">
                <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z">
                    </path>
                </svg>
                <p>Tidak ada group ditemukan.</p>
            </div>
            <?php endif; ?>

            <?php elseif ($currentTab === 'orang'): ?>
            <?php if (!empty($user_results)): ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($user_results as $user): ?>
                <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between">
                    <div class="flex items-center">
                        <img src="/Sinergi/public/assets/images/user.png"
                            class="w-10 h-10 rounded-full mr-3 bg-gray-200 object-cover">
                        <div>
                            <p class="font-bold text-gray-900"><?= htmlspecialchars($user['nama_lengkap']) ?></p>
                            <p class="text-sm text-gray-500">@<?= htmlspecialchars($user['username']) ?></p>
                        </div>
                    </div>
                    <a href="#"
                        class="px-4 py-1.5 border border-black text-black font-bold text-xs rounded-full hover:bg-gray-100 transition">Lihat
                        Profil</a>
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