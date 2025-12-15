<style>
/* ====== SEMBUNYIKAN SCROLLBAR ====== */
.sidebar-kanan-container {
    overflow-y: scroll;
    height: 100vh;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.sidebar-kanan-container::-webkit-scrollbar {
    width: 0;
    height: 0;
}
</style>

<aside class="col-span-4 p-6 pt-3 space-y-6 bg-transparent sticky top-20">

    <div class="border border-gray-200 bg-white w-64 p-5 rounded-2xl shadow-sm">
        <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z">
                </path>
            </svg>
            Paling Aktif
        </h3>
        <div class="space-y-4">
            <?php if (isset($recommendedUsers) && !empty($recommendedUsers)): ?>
            <?php foreach ($recommendedUsers as $user): ?>
            <a href="index.php?page=profile&id=<?= htmlspecialchars($user['user_id']); ?>"
                class="flex items-center justify-between group hover:bg-gray-50 p-2 -mx-2 rounded-xl transition">
                <div class="flex items-center min-w-0">
                    <img src="<?= htmlspecialchars($user['avatar_url']); ?>" alt="Avatar"
                        class="w-10 h-10 rounded-full mr-3 object-cover border border-gray-100 bg-gray-100">
                    <div class="min-w-0">
                        <p class="font-bold text-sm text-gray-800 truncate group-hover:text-gray-600 transition">
                            <?= htmlspecialchars($user['nama_lengkap']); ?>
                        </p>
                        <p class="text-xs text-gray-500 truncate">@<?= htmlspecialchars($user['username']); ?></p>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php else: ?>
            <p class="text-sm text-gray-400 italic">Belum ada user aktif.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="border border-gray-200 bg-white w-64 p-5 rounded-2xl shadow-sm">
        <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                </path>
            </svg>
            Grup Populer
        </h3>
        <div class="space-y-4">
            <?php if (isset($recommendedGroups) && !empty($recommendedGroups)): ?>
            <?php foreach ($recommendedGroups as $group): ?>

            <?php 
                    // Logika Visual Private/Public
                    $isPrivate = ($group['is_private'] == 1);
                    $iconColor = $isPrivate ? 'text-gray-400' : 'text-gray-500';
                    $privacyText = $isPrivate ? 'Privat' : 'Publik';
                ?>

            <a href="index.php?page=group-details&group_id=<?= htmlspecialchars($group['group_id']); ?>"
                class="flex items-center justify-between group hover:bg-gray-50 p-2 -mx-2 rounded-xl transition">
                <div class="flex items-center min-w-0">
                    <img src="<?= htmlspecialchars($group['group_image']); ?>" alt="Group"
                        class="w-10 h-10 rounded-xl mr-3 object-cover border border-gray-100 bg-white">

                    <div class="min-w-0">
                        <p class="font-bold text-sm text-gray-800 truncate group-hover:text-gray-600 transition">
                            <?= htmlspecialchars($group['nama_group']); ?>
                        </p>
                        <div class="flex items-center gap-1 text-xs text-gray-500">
                            <?php if($isPrivate): ?>
                            <svg class="w-3 h-3 <?= $iconColor ?>" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                </path>
                            </svg>
                            <?php else: ?>
                            <svg class="w-3 h-3 <?= $iconColor ?>" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                            <?php endif; ?>
                            <span><?= $privacyText ?> • <?= $group['member_count'] ?> member</span>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php else: ?>
            <p class="text-sm text-gray-400 italic">Belum ada grup.</p>
            <?php endif; ?>
        </div>
    </div>
</aside>