<div
    class="col-span-1 lg:col-span-10 h-[calc(100vh-3px)] bg-[#f0f2f5] overflow-y-auto custom-scrollbar relative pb-20 lg:pb-0">

    <div
        class="sticky top-0 z-30 bg-white/90 backdrop-blur-sm border-b border-gray-200 px-4 py-3 flex items-center gap-4 shadow-sm">
        <button onclick="history.back()"
            class="p-2 rounded-full hover:bg-gray-100 transition text-gray-600 group cursor-pointer" title="Kembali">
            <svg class="w-6 h-6 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
        </button>
        <h2 class="text-lg font-bold text-gray-800">Info Grup</h2>
    </div>

    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-6 w-full">

            <div class="relative pt-8 pb-6 md:flex items-end gap-8">
                <?php
                    // Setup Data Tampilan
                    $g_img = !empty($group_info['group_image']) 
                        ? '/Sinergi/public/uploads/group_profiles/' . htmlspecialchars($group_info['group_image']) 
                        : '/Sinergi/public/assets/images/user.png';
                    
                    // Pastikan variable role aman (dikirim dari Controller)
                    $is_owner = (isset($myRole) && $myRole === 'owner');
                    $is_admin = (isset($myRole) && $myRole === 'admin');
                ?>

                <div class="shrink-0 relative">
                    <img src="<?= $g_img ?>"
                        class="w-32 h-32 md:w-44 md:h-44 rounded-2xl object-cover border-4 border-white shadow-lg bg-gray-200">
                </div>

                <div class="flex-1 mb-2 mt-4 md:mt-0">
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight mb-2">
                        <?= htmlspecialchars($group_info['nama_group']) ?>
                    </h1>

                    <div class="flex flex-wrap items-center text-sm text-gray-500 gap-4">
                        <?php if($group_info['is_private']): ?>
                        <div class="flex items-center gap-1.5 bg-gray-100 px-2 py-1 rounded text-gray-600 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                </path>
                            </svg>
                            <span>Grup Privat</span>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center gap-1.5 bg-gray-50 px-2 py-1 rounded text-gray-600 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9">
                                </path>
                            </svg>
                            <span>Grup Publik</span>
                        </div>
                        <?php endif; ?>

                        <?php if(!$is_locked): ?>
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                            <span><?= isset($group_members) ? count($group_members) : 0 ?> Anggota</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex gap-3 mb-2 shrink-0">
                    <?php if ($is_member): ?>
                    <?php if ($can_manage): ?>
                    <a href="index.php?page=edit-group&group_id=<?= $group_info['group_id'] ?>"
                        class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold rounded-xl text-sm transition">
                        Edit Grup
                    </a>
                    <?php endif; ?>

                    <?php if (!$is_owner): ?>
                    <a href="index.php?page=exit-group&group_id=<?= $group_info['group_id'] ?>"
                        onclick="return confirm('Yakin ingin keluar dari grup?')"
                        class="px-5 py-2.5 bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-800 font-bold rounded-xl text-sm transition">
                        Keluar
                    </a>
                    <?php endif; ?>

                    <a href="index.php?page=messages&group_id=<?= $group_info['group_id'] ?>"
                        class="px-6 py-2.5 bg-gray-900 hover:bg-gray-700 text-white font-bold rounded-xl text-sm transition flex items-center gap-2 shadow-md shadow-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                        Chat
                    </a>

                    <?php else: ?>
                    <?php if ($group_info['is_private']): ?>
                    <a href="index.php?page=join-group&group_id=<?= $group_info['group_id'] ?>"
                        class="px-6 py-2.5 bg-black hover:bg-gray-800 text-white font-bold rounded-xl text-sm transition flex items-center gap-2 shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                            </path>
                        </svg>
                        Request Join
                    </a>
                    <?php else: ?>
                    <a href="index.php?page=join-group&group_id=<?= $group_info['group_id'] ?>"
                        class="px-6 py-2.5 bg-gray-900 hover:bg-gray-700 text-white font-bold rounded-xl text-sm transition shadow-lg shadow-gray-200">
                        Gabung Grup
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$is_locked): ?>
            <div class="flex items-center gap-6 mt-6 overflow-x-auto">
                <?php 
                    $view = $_GET['view'] ?? 'diskusi';
                    $tab_base = "pb-4 text-sm font-bold border-b-[3px] transition whitespace-nowrap px-1";
                    $tab_active = "border-gray-600 text-gray-600";
                    $tab_inactive = "border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-200";
                ?>
                <a href="index.php?page=group-details&group_id=<?= $group_id ?>&view=diskusi"
                    class="<?= $tab_base ?> <?= $view=='diskusi' ? $tab_active : $tab_inactive ?>">Diskusi</a>
                <a href="index.php?page=group-details&group_id=<?= $group_id ?>&view=members"
                    class="<?= $tab_base ?> <?= $view=='members' ? $tab_active : $tab_inactive ?>">Anggota</a>
                <a href="index.php?page=group-details&group_id=<?= $group_id ?>&view=media"
                    class="<?= $tab_base ?> <?= $view=='media' ? $tab_active : $tab_inactive ?>">Media</a>
                <a href="index.php?page=group-details&group_id=<?= $group_id ?>&view=files"
                    class="<?= $tab_base ?> <?= $view=='files' ? $tab_active : $tab_inactive ?>">File</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-6 w-full py-8 grid grid-cols-1 lg:grid-cols-3 gap-8 pb-24">

        <div class="lg:col-span-2 space-y-6">

            <?php if ($is_locked): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="w-24 h-24 mx-auto mb-6 bg-gray-50 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Grup Privat</h3>
                <p class="text-gray-500 max-w-md mx-auto leading-relaxed">
                    Grup ini bersifat pribadi. Hanya anggota yang disetujui yang dapat melihat diskusi, daftar anggota,
                    dan file yang dibagikan.
                </p>
                <div class="mt-8">
                    <a href="index.php?page=join-group&group_id=<?= $group_info['group_id'] ?>"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-black text-white font-bold rounded-xl hover:bg-gray-800 transition shadow-lg">
                        Ajukan Bergabung
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <?php else: ?>
            <?php if($view == 'diskusi'): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 text-center">
                <div class="w-20 h-20 mx-auto mb-4 bg-gray-50 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Mulai Diskusi</h3>
                <p class="text-gray-500 mb-6 max-w-md mx-auto">Bagikan ide, pertanyaan, atau file dengan anggota grup
                    ini di forum diskusi.</p>
                <a href="index.php?page=messages&group_id=<?= $group_id ?>&tab=forum"
                    class="px-6 py-3 bg-gray-900 hover:bg-gray-700 text-white rounded-xl font-bold transition shadow-lg shadow-gray-200">Buka
                    Forum Diskusi</a>
            </div>
            <?php endif; ?>

            <?php if($view == 'members'): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 text-lg">Anggota Grup</h3>
                    <?php if ($can_manage): ?>
                    <button onclick="openAddMemberModal()"
                        class="text-xs bg-gray-900 text-white px-3 py-1.5 rounded-full font-bold hover:bg-gray-700 transition shadow flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                            </path>
                        </svg> Undang
                    </button>
                    <?php endif; ?>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php foreach($group_members as $gm): ?>
                    <div class="p-5 flex items-center justify-between hover:bg-gray-50 transition group">
                        <div class="flex items-center gap-4">
                            <?php $gm_img = !empty($gm['avatar_url']) ? '/Sinergi/public/uploads/avatars/' . $gm['avatar_url'] : '/Sinergi/public/assets/images/user.png'; ?>
                            <img src="<?= $gm_img ?>"
                                class="w-12 h-12 rounded-full object-cover border border-gray-100">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-bold text-gray-900"><?= htmlspecialchars($gm['nama_lengkap']) ?></p>
                                    <?php if($gm['group_role'] === 'owner'): ?>
                                    <span
                                        class="text-[10px] bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded border border-yellow-200 font-bold uppercase">OWNER</span>
                                    <?php elseif($gm['group_role'] === 'admin'): ?>
                                    <span
                                        class="text-[10px] bg-gray-100 text-gray-700 px-2 py-0.5 rounded border border-gray-200 font-bold uppercase">ADMIN</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-500">@<?= htmlspecialchars($gm['username']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                            <?php if ($is_owner && $gm['user_id'] != $_SESSION['user_id']): ?>
                            <form action="index.php?page=change-role" method="POST" class="inline">
                                <input type="hidden" name="group_id" value="<?= $group_id ?>">
                                <input type="hidden" name="target_user_id" value="<?= $gm['user_id'] ?>">
                                <?php if (($gm['group_role'] ?? 'member') === 'member'): ?>
                                <input type="hidden" name="action" value="promote">
                                <button type="submit"
                                    class="text-xs bg-gray-50 text-gray-600 px-2 py-1 rounded border border-gray-200 font-medium cursor-pointer">▲
                                    Admin</button>
                                <?php elseif (($gm['group_role'] ?? 'member') === 'admin'): ?>
                                <input type="hidden" name="action" value="demote">
                                <button type="submit"
                                    class="text-xs bg-orange-50 text-orange-600 px-2 py-1 rounded border border-orange-200 font-medium cursor-pointer">▼
                                    Member</button>
                                <?php endif; ?>
                            </form>
                            <?php endif; ?>

                            <?php 
                                            $canKick = ($is_owner && $gm['user_id'] != $_SESSION['user_id']) || ($is_admin && ($gm['group_role'] ?? 'member') === 'member');
                                            if($canKick): 
                                        ?>
                            <button
                                onclick="kickUser(<?= $gm['user_id'] ?>, '<?= htmlspecialchars($gm['nama_lengkap']) ?>')"
                                class="text-gray-400 hover:text-red-500 p-2 rounded-full hover:bg-red-50 transition cursor-pointer"><svg
                                    class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6">
                                    </path>
                                </svg></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if($view == 'media'): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-800 text-lg mb-6">Foto</h3>
                <?php if (!empty($group_media)): ?>
                <div class="grid grid-cols-3 md:grid-cols-4 gap-3">
                    <?php foreach ($group_media as $m): ?>
                    <?php 
                            // 1. Pecah String Path (karena bisa berisi banyak gambar)
                            $mediaPaths = explode(',', $m['file_path'] ?? ''); 
                        ?>
                    <?php foreach($mediaPaths as $path): ?>
                    <?php 
                                $fullPath = trim($path);
                                if(empty($fullPath)) continue;

                                // Handle Legacy Path
                                if (strpos($fullPath, '/Sinergi') === false) {
                                    $fullPath = '/Sinergi/public/uploads/group_files/' . $fullPath;
                                }
                            ?>
                    <a href="<?= $fullPath ?>" target="_blank"
                        class="aspect-square bg-gray-100 rounded-xl overflow-hidden block relative group shadow-sm hover:shadow-md transition border border-gray-200">
                        <img src="<?= $fullPath ?>"
                            class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    </a>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-12 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                    <p class="text-gray-400 font-medium">Belum ada media.</p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if($view == 'files'): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-800 text-lg mb-6">Dokumen</h3>
                <div class="space-y-3">
                    <?php foreach ($group_documents as $d): ?>
                    <?php 
                            // 1. Pecah Path dan Nama File
                            $docPaths = explode(',', $d['file_path'] ?? '');
                            $docNames = explode(',', $d['original_filename'] ?? '');
                        ?>
                    <?php foreach($docPaths as $idx => $path): ?>
                    <?php 
                                $fullPath = trim($path);
                                if(empty($fullPath)) continue;

                                // Handle Legacy
                                if (strpos($fullPath, '/Sinergi') === false) {
                                    $fullPath = '/Sinergi/public/uploads/group_files/' . $fullPath;
                                }

                                // Ambil nama yang sesuai urutan
                                $showName = isset($docNames[$idx]) && !empty(trim($docNames[$idx])) 
                                            ? trim($docNames[$idx]) 
                                            : basename($fullPath);
                            ?>
                    <a href="<?= $fullPath ?>" target="_blank"
                        class="flex items-center p-4 border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-200 transition group bg-gray-50">
                        <div class="bg-gray-100 text-gray-600 p-3 rounded-lg mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 truncate group-hover:text-gray-700 text-base">
                                <?= htmlspecialchars($showName) ?></p>
                            <p class="text-sm text-gray-500 mt-0.5">
                                <?= htmlspecialchars($d['sender_nama'] ?? 'User') ?> •
                                <?= htmlspecialchars($d['created_at_formatted'] ?? 'Baru saja') ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php endforeach; ?>

                    <?php if (empty($group_documents)): ?>
                    <div class="text-center py-12 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                        <p class="text-gray-400 font-medium">Belum ada dokumen.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; // END IF IS_LOCKED ?>
        </div>

        <div class="lg:col-span-1 space-y-6">

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sticky top-20">
                <h3 class="font-bold text-gray-900 text-lg mb-4">Tentang Grup</h3>
                <?php 
                    $deskripsi_clean = 'Tidak ada deskripsi.';
                    if(!empty($group_info['deskripsi'])) {
                        $raw = $group_info['deskripsi'];
                        $deskripsi_clean = ($raw instanceof OCILob) ? $raw->read($raw->size()) : $raw;
                    }
                ?>
                <div class="text-sm text-gray-600 leading-relaxed mb-6 whitespace-normal break-words">
                    <?= nl2br(htmlspecialchars($deskripsi_clean)) ?>
                </div>

                <div class="space-y-4 pt-6 border-t border-gray-100">
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Dibuat pada
                            <strong><?= date('d M Y', strtotime($group_info['created_at'])) ?></strong></span>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                        <span>Tipe: <strong><?= $group_info['is_private'] ? 'Privat' : 'Publik' ?></strong></span>
                    </div>
                </div>
            </div>

            <?php if ($can_manage && !empty($pending_members) && !$is_locked): ?>
            <div class="bg-gray-50 rounded-2xl border border-gray-100 p-5 shadow-sm">
                <h4 class="font-bold text-gray-800 mb-1">Butuh Persetujuan</h4>
                <p class="text-sm text-gray-600 mb-4 opacity-80">Ada <?= count($pending_members) ?> orang ingin
                    bergabung.</p>
                <div class="divide-y divide-gray-200">
                    <?php foreach($pending_members as $pm): ?>
                    <div class="py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2 min-w-0">
                            <?php $pm_img = !empty($pm['avatar_url']) ? '/Sinergi/public/uploads/avatars/' . $pm['avatar_url'] : '/Sinergi/public/assets/images/user.png'; ?>
                            <img src="<?= $pm_img ?>" class="w-8 h-8 rounded-full object-cover">
                            <p class="text-sm font-bold text-gray-900 truncate">
                                <?= htmlspecialchars($pm['nama_lengkap']) ?></p>
                        </div>
                        <div class="flex gap-1">
                            <a href="index.php?page=process-request&action=approve&group_id=<?= $group_id ?>&user_id=<?= $pm['user_id'] ?>"
                                class="p-1.5 bg-gray-600 text-white rounded hover:bg-gray-700" title="Terima"><svg
                                    class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg></a>
                            <a href="index.php?page=process-request&action=reject&group_id=<?= $group_id ?>&user_id=<?= $pm['user_id'] ?>"
                                class="p-1.5 bg-gray-200 text-gray-600 rounded hover:bg-gray-300" title="Tolak"><svg
                                    class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div id="addMemberModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeAddMemberModal()"></div>
    <div
        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col h-[600px]">
        <div class="bg-gray-900 text-white px-4 py-3 flex items-center space-x-3">
            <button onclick="closeAddMemberModal()"
                class="hover:bg-white/20 p-1 rounded-full transition cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </button>
            <h3 class="font-bold text-lg">Undang Teman</h3>
        </div>
        <div class="p-3 border-b border-gray-100">
            <div class="relative">
                <input type="text" id="searchCandidateInput" placeholder="Cari nama teman..." autocomplete="off"
                    class="w-full pl-10 pr-4 py-2 bg-gray-100 border-none rounded-lg text-sm focus:ring-2 focus:ring-gray-600 focus:bg-white transition placeholder-gray-500">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-2" id="candidatesList">
            <div id="loadingSpinner" class="hidden flex justify-center py-8">
                <svg class="animate-spin h-8 w-8 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>
            <div id="resultsContainer" class="space-y-1"></div>
            <div id="emptyState" class="hidden text-center py-10 text-gray-500">
                <p>Tidak ditemukan.</p>
            </div>
        </div>
    </div>
</div>

<div id="confirmAddModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>
    <div
        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm bg-white rounded-xl shadow-2xl p-6 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-4">
            <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </div>
        <h3 class="text-lg leading-6 font-bold text-gray-900">Kirim Undangan?</h3>
        <div class="mt-2">
            <p class="text-sm text-gray-500" id="confirmMessage">Yakin ingin mengundang user ini?</p>
        </div>
        <div class="mt-6 flex justify-center gap-3">
            <button type="button" onclick="closeConfirmModal()"
                class="px-4 py-2 bg-white text-gray-700 text-base font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition cursor-pointer">Batal</button>
            <button type="button" id="btnConfirmYes"
                class="px-4 py-2 bg-gray-900 text-white text-base font-medium rounded-lg hover:bg-gray-700 shadow-lg shadow-gray-500/30 transition cursor-pointer">Ya,
                Undang</button>
        </div>
    </div>
</div>

<form id="addMemberForm" action="index.php?page=process-invite-member" method="POST" class="hidden">
    <input type="hidden" name="group_id" value="<?= $group_info['group_id'] ?>">
    <input type="hidden" name="target_user_id" id="form_target_id">
    <input type="hidden" name="target_user_name" id="form_target_name">
</form>

<div id="kickMemberModal" class="fixed inset-0 z-[70] hidden transition-opacity duration-300"
    aria-labelledby="modal-title" role="dialog" aria-modal="true">

    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeKickModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">

        <div
            class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md scale-100 opacity-100">

            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">

                    <div
                        class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>

                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                        <h3 class="text-lg font-bold leading-6 text-gray-900" id="modal-title">Keluarkan Anggota?</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Apakah kamu yakin ingin mengeluarkan <span id="kickTargetName"
                                    class="font-bold text-gray-800">User</span> dari grup ini?
                            </p>
                            <p class="text-xs text-red-500 mt-2 bg-red-50 p-2 rounded border border-red-100">
                                Tindakan ini akan menghapus akses mereka ke semua chat dan file grup.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                <button type="button" id="btnConfirmKick"
                    class="inline-flex w-full justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-700 sm:w-auto transition shadow-red-200 cursor-pointer">
                    Ya, Keluarkan
                </button>
                <button type="button" onclick="closeKickModal()"
                    class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto transition cursor-pointer">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// --- LOGIKA MODAL UNDANG ---
const modal = document.getElementById('addMemberModal');
const searchInput = document.getElementById('searchCandidateInput');
const resultsContainer = document.getElementById('resultsContainer');
const loadingSpinner = document.getElementById('loadingSpinner');
const emptyState = document.getElementById('emptyState');
const groupId = <?= (int)$group_info['group_id'] ?>;

function openAddMemberModal() {
    modal.classList.remove('hidden');
    searchInput.value = '';
    searchInput.focus();
    fetchCandidates('');
}

function closeAddMemberModal() {
    modal.classList.add('hidden');
}

let timeout = null;
searchInput.addEventListener('input', function() {
    clearTimeout(timeout);
    const query = this.value;
    resultsContainer.innerHTML = '';
    loadingSpinner.classList.remove('hidden');
    emptyState.classList.add('hidden');
    timeout = setTimeout(() => {
        fetchCandidates(query);
    }, 300);
});

function fetchCandidates(query) {
    fetch(`index.php?page=api-search-candidates&group_id=${groupId}&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            loadingSpinner.classList.add('hidden');
            resultsContainer.innerHTML = '';
            if (data.length === 0) {
                emptyState.classList.remove('hidden');
                return;
            } else {
                emptyState.classList.add('hidden');
            }

            data.forEach(user => {
                let avatarPath = '/Sinergi/public/assets/images/user.png';
                if (user.avatar_url && user.avatar_url !== '') {
                    avatarPath = '/Sinergi/public/uploads/avatars/' + user.avatar_url;
                }

                const item = document.createElement('div');
                item.className =
                    'flex items-center p-3 hover:bg-gray-50 rounded-lg cursor-pointer transition border-b border-gray-50 last:border-0';
                item.onclick = () => submitAddMember(user.user_id, user.nama_lengkap);
                item.innerHTML =
                    `<img src="${avatarPath}" class="w-10 h-10 rounded-full bg-gray-200 mr-3 object-cover border border-gray-200"><div class="flex-1"><p class="font-bold text-gray-800 text-sm">${user.nama_lengkap}</p><p class="text-xs text-gray-500">@${user.username}</p></div><div class="text-gray-600 bg-gray-100 p-1.5 rounded-full"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></div>`;
                resultsContainer.appendChild(item);
            });
        })
        .catch(err => {
            console.error('Error:', err);
            loadingSpinner.classList.add('hidden');
        });
}

// Konfirmasi & Submit
let selectedUserId = null;
let selectedUserName = null;
const confirmModal = document.getElementById('confirmAddModal');
const confirmMessage = document.getElementById('confirmMessage');
const btnConfirmYes = document.getElementById('btnConfirmYes');

function submitAddMember(userId, userName) {
    selectedUserId = userId;
    selectedUserName = userName;
    confirmMessage.innerHTML = `Kirim undangan bergabung kepada <strong>${userName}</strong>?`;
    confirmModal.classList.remove('hidden');
}

function closeConfirmModal() {
    confirmModal.classList.add('hidden');
    selectedUserId = null;
}

btnConfirmYes.addEventListener('click', function() {
    if (selectedUserId) {
        document.getElementById('form_target_id').value = selectedUserId;
        document.getElementById('form_target_name').value = selectedUserName;
        document.getElementById('addMemberForm').submit();
    }
});

// --- LOGIKA MODERN KICK MODAL ---
let kickUrl = ''; // Variabel untuk menyimpan link tujuan

function kickUser(userId, name) {
    // 1. Simpan Link Kick ke variabel global
    // Pastikan PHP echo $group_id aman di sini
    const groupId = <?= (int)$group_info['group_id'] ?>;
    kickUrl = `index.php?page=kick-member&group_id=${groupId}&user_id=${userId}&name=${encodeURIComponent(name)}`;

    // 2. Update Nama User di Modal
    document.getElementById('kickTargetName').innerText = name;

    // 3. Tampilkan Modal (Hapus class hidden)
    const modal = document.getElementById('kickMemberModal');
    modal.classList.remove('hidden');

    // Opsional: Tambahkan animasi masuk simpel
    modal.querySelector('div[class*="transform"]').classList.remove('scale-95', 'opacity-0');
    modal.querySelector('div[class*="transform"]').classList.add('scale-100', 'opacity-100');
}

function closeKickModal() {
    const modal = document.getElementById('kickMemberModal');
    modal.classList.add('hidden');
}

// Event Listener Tombol "Ya, Keluarkan"
document.getElementById('btnConfirmKick').addEventListener('click', function() {
    if (kickUrl) {
        // Redirect ke link PHP untuk proses kick
        window.location.href = kickUrl;
    }
});
</script>