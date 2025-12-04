<main class="col-span-6 border-r border-gray-200 min-h-screen bg-white">

    <div
        class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-20">
        <a href="index.php?page=dashboard" title="Kembali" class="p-2 rounded-full hover:bg-gray-200 transition">
            <img src="/Sinergi/public/assets/icons/arrow-left.svg" alt="Kembali" class="w-6 h-6">
        </a>
        <div>
            <h2 class="text-xl font-bold leading-none"><?= htmlspecialchars($profile_data['NAMA_LENGKAP']); ?></h2>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($profile_data['TOTAL_POSTINGAN'] ?? 0); ?> postingan
            </p>
        </div>
    </div>

    <div>
        <div class="relative">
            <img src="<?= htmlspecialchars($profile_data['HEADER_URL_FIXED']); ?>" alt="Header Profil"
                class="w-full h-48 object-cover bg-gray-200">
            <div class="absolute -bottom-16 left-4">
                <img src="<?= htmlspecialchars($profile_data['AVATAR_URL_FIXED']); ?>" alt="Avatar"
                    class="w-32 h-32 rounded-full border-4 border-white object-cover bg-white shadow-sm">
            </div>
        </div>
        <div class="text-right p-4 pb-0 h-16">
            <?php if ($is_my_profile): ?>
            <a href="index.php?page=edit_profile"
                class="inline-block border border-gray-300 font-bold py-2 px-4 rounded-full hover:bg-gray-100 text-sm transition">
                Edit profile
            </a>
            <?php endif; ?>
        </div>
        <div class="px-4 mt-4 pb-4">
            <h2 class="text-xl font-bold text-black leading-tight">
                <?= htmlspecialchars($profile_data['NAMA_LENGKAP']); ?></h2>
            <p class="text-gray-500 mb-3">@<?= htmlspecialchars($profile_data['USERNAME']); ?></p>
            <div class="text-gray-700 mb-3 text-sm flex items-center">
                <span class="font-medium bg-gray-100 px-2 py-0.5 rounded text-xs border border-gray-200">
                    <?= htmlspecialchars($profile_data['ROLE_NAME'] ?? 'User'); ?>
                </span>
                <?php if(($profile_data['ROLE_ID'] ?? 0) == 3 && !empty($profile_data['TAHUN_MASUK'])): ?>
                <span class="ml-2 text-xs text-gray-400">• Angkatan <?= $profile_data['TAHUN_MASUK'] ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($profile_data['BIO'])): ?>
            <div class="text-gray-800 mb-3 text-sm leading-relaxed whitespace-normal break-words">
                <?= nl2br(htmlspecialchars(trim($profile_data['BIO']))) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="sticky top-[72px] z-10 bg-white/80 backdrop-blur-sm border-b border-gray-200">
        <div class="grid grid-cols-1 w-full">
            <div
                class="w-full py-3 text-sm font-bold text-center border-b-2 border-black text-black cursor-pointer transition select-none hover:bg-gray-50">
                Postingan
            </div>
        </div>
    </div>

    <div class="bg-white min-h-screen pb-20">
        <?php if (empty($user_posts)): ?>
        <div class="py-10 text-center">
            <p class="text-xl font-bold text-gray-800 mb-2">Belum ada postingan</p>
        </div>
        <?php else: ?>
        <?php foreach ($user_posts as $post): ?>
        <?php 
                // 1. LOGIKA AVATAR FIX (Agar tidak pecah)
                $rawAvatar = $post['AVATAR_URL'] ?? '';
                if (empty($rawAvatar)) {
                    $pAvatar = '/Sinergi/public/assets/images/user.png';
                } elseif (strpos($rawAvatar, '/') === false) {
                    $pAvatar = '/Sinergi/public/uploads/avatars/' . $rawAvatar;
                } else {
                    $pAvatar = $rawAvatar;
                }

                // 2. LOGIKA LIKE BUTTON (SVG STYLE)
                $is_liked = ($post['USER_SUDAH_LIKE'] > 0);
                $likeBtnClass = $is_liked ? 'text-red-500' : 'text-gray-500';
                $svgClass = $is_liked ? 'fill-current' : 'fill-none stroke-current';
            ?>
        <div class="bg-white border-b border-gray-200 hover:bg-gray-50/30 transition-colors cursor-pointer"
            onclick="window.location.href='index.php?page=post-detail&id=<?= $post['POST_ID'] ?>'">
            <div class="p-4">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 cursor-pointer"
                        onclick="event.stopPropagation(); window.location.href='index.php?page=profile&id=<?= $post['USER_ID'] ?>'">
                        <img src="<?= htmlspecialchars($pAvatar) ?>" alt="Avatar"
                            class="w-10 h-10 rounded-full object-cover bg-gray-200 border border-gray-100">
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-1 mb-1">
                            <span class="font-bold text-gray-900"><?= htmlspecialchars($post['NAMA_LENGKAP']) ?></span>
                            <span class="text-gray-500 text-sm">@<?= htmlspecialchars($post['USERNAME']) ?></span>
                            <span class="text-gray-400 text-sm">·</span>
                            <span
                                class="text-gray-500 text-sm"><?= htmlspecialchars($post['WAKTU_POSTING'] ?? '') ?></span>
                        </div>

                        <?php if (!empty($post['KONTEN'])): ?>
                        <div
                            class="cursor-pointer mb-2 text-gray-800 text-[15px] leading-normal break-words whitespace-normal">
                            <?= nl2br(htmlspecialchars(trim($post['KONTEN']))) ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($post['POST_IMAGE'])): ?>
                        <div class="mt-2 mb-2">
                            <img src="<?= htmlspecialchars($post['POST_IMAGE']) ?>"
                                onclick="event.stopPropagation(); openImageModal(this.src)"
                                class="rounded-xl w-full border border-gray-200 max-h-[500px] object-cover hover:opacity-95 transition">
                        </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between mt-3 max-w-md text-gray-500">

                            <button onclick="event.stopPropagation(); handleLikeProfile(this, <?= $post['POST_ID'] ?>)"
                                class="group flex items-center space-x-2 hover:text-red-500 transition-colors <?= $likeBtnClass ?> w-fit p-1 -ml-2 rounded-full hover:bg-red-50/50">
                                <div class="p-1.5 relative">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="w-5 h-5 transition-transform duration-200 <?= $svgClass ?>"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                    </svg>
                                </div>
                                <span
                                    class="like-count text-sm font-medium"><?= ($post['LIKE_COUNT'] > 0) ? $post['LIKE_COUNT'] : '' ?></span>
                            </button>

                            <div
                                class="group flex items-center space-x-2 hover:text-blue-500 transition-colors w-fit p-1 rounded-full hover:bg-blue-50/50">
                                <div class="p-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm font-medium"><?= ($post['COMMENT_COUNT'] > 0) ? $post['COMMENT_COUNT'] : '' ?></span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
</main>

<script>
// FUNGSI LIKE BARU (SVG VERSION)
function handleLikeProfile(btn, postId) {
    btn.disabled = true;
    const countSpan = btn.querySelector('.like-count');
    const svgIcon = btn.querySelector('svg');

    let currentCount = parseInt(countSpan.innerText) || 0;
    const isLiked = btn.classList.contains('text-red-500');

    // UI Optimistic Update (Langsung berubah sebelum server respon)
    if (isLiked) {
        // UNLIKE
        btn.classList.remove('text-red-500');
        btn.classList.add('text-gray-500');
        svgIcon.classList.remove('fill-current');
        svgIcon.classList.add('fill-none', 'stroke-current');
        countSpan.innerText = currentCount > 1 ? currentCount - 1 : '';
    } else {
        // LIKE
        btn.classList.remove('text-gray-500');
        btn.classList.add('text-red-500');
        svgIcon.classList.remove('fill-none', 'stroke-current');
        svgIcon.classList.add('fill-current');
        countSpan.innerText = currentCount + 1;

        // Animasi Pop
        svgIcon.style.transform = "scale(1.2)";
        setTimeout(() => svgIcon.style.transform = "scale(1)", 200);
    }

    const fd = new FormData();
    fd.append('post_id', postId);

    fetch('index.php?page=post-api&method=toggleLike', {
            method: 'POST',
            body: fd
        })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                // Revert jika gagal
                if (isLiked) {
                    btn.classList.add('text-red-500');
                    svgIcon.classList.add('fill-current');
                } else {
                    btn.classList.remove('text-red-500');
                    svgIcon.classList.remove('fill-current');
                }
            } else {
                if (data.new_count !== undefined) countSpan.innerText = data.new_count > 0 ? data.new_count : '';
            }
        })
        .catch(err => console.error(err))
        .finally(() => {
            btn.disabled = false;
        });
}

// FUNGSI MODAL GAMBAR
function openImageModal(src) {
    const m = document.createElement('div');
    m.style.cssText =
        'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;display:flex;justify-content:center;align-items:center;cursor:pointer;';
    m.onclick = () => m.remove();
    m.innerHTML =
        `<img src="${src}" style="max-width:95%;max-height:95%;border-radius:8px;object-fit:contain;box-shadow:0 0 20px rgba(0,0,0,0.5);">`;
    document.body.appendChild(m);
}
</script>

<?php require 'app/views/partials/sidebar_kanan.php'; ?>