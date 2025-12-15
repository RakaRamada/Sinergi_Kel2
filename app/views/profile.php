<main class="col-span-6 border-r border-gray-200 min-h-screen bg-white">

    <div
        class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-20">
        <button onclick="history.back()" title="Kembali"
            class="p-2 rounded-full hover:bg-gray-200 transition cursor-pointer">
            <img src="/Sinergi/public/assets/icons/arrow-left.svg" alt="Kembali" class="w-6 h-6">
        </button>
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
            <p class="text-gray-500 mb-2">@<?= htmlspecialchars($profile_data['USERNAME']); ?></p>

            <div class="text-gray-700 mb-3 text-sm flex flex-wrap items-center gap-2">
                <span
                    class="font-medium bg-gray-100 px-2.5 py-0.5 rounded-full text-xs border border-gray-200 text-gray-600">
                    <?= htmlspecialchars($profile_data['ROLE_NAME'] ?? 'User'); ?>
                </span>

                <?php 
                $nomor_induk = $profile_data['NOMOR_INDUK'] ?? '';
                if (!empty($nomor_induk)) {
                    $label_induk = ($profile_data['ROLE_ID'] == 1) ? 'NIM' : (($profile_data['ROLE_ID'] == 2) ? 'NIP' : '');
                    if ($label_induk) {
                        echo '<span class="text-xs text-gray-500 font-mono bg-gray-50 px-2 py-0.5 rounded border border-gray-100 flex items-center gap-1">';
                        echo '<svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0c0 .667.333 1 1 1v1m0-2c0 .667-.333 1-1 1v1"></path></svg>';
                        echo $label_induk . ': ' . htmlspecialchars($nomor_induk);
                        echo '</span>';
                    }
                }
                ?>

                <?php if(!empty($profile_data['TAHUN_MASUK'])): ?>
                <span class="text-xs text-gray-400">• Angkatan <?= $profile_data['TAHUN_MASUK'] ?></span>
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
                // Setup Variable
                $rawAvatar = $post['AVATAR_URL'] ?? '';
                $pAvatar = (empty($rawAvatar)) ? '/Sinergi/public/assets/images/user.png' : 
                          ((strpos($rawAvatar, '/') === false) ? '/Sinergi/public/uploads/avatars/' . $rawAvatar : $rawAvatar);

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
                        <div class="flex flex-wrap items-center gap-x-2 mb-1">
                            <span
                                class="font-bold text-gray-900 text-[15px]">@<?= htmlspecialchars($post['USERNAME']) ?></span>
                            <span
                                class="px-2 py-0.5 bg-gray-100 text-gray-600 text-[10px] rounded-full font-medium border border-gray-100">
                                <?= htmlspecialchars($profile_data['ROLE_NAME'] ?? '') ?>
                            </span>
                            <span class="text-gray-400 text-sm">·</span>
                            <span
                                class="text-gray-500 text-sm hover:underline"><?= htmlspecialchars($post['WAKTU_POSTING'] ?? '') ?></span>
                        </div>

                        <?php if (!empty($post['KONTEN'])): ?>
                        <div
                            class="cursor-pointer mb-2 text-gray-800 text-[15px] leading-normal break-words whitespace-normal">
                            <?= nl2br(htmlspecialchars(trim($post['KONTEN']))) ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($post['POST_IMAGE'])): ?>
                        <?php 
                                    $images = array_filter(explode(',', $post['POST_IMAGE']), function($p){ return !empty(trim($p)); });
                                    $imgCount = count($images);
                                ?>

                        <?php if ($imgCount === 1): ?>
                        <div class="mt-2 mb-2 rounded-xl overflow-hidden border border-gray-200">
                            <?php $oneImg = reset($images); ?>
                            <img src="<?= htmlspecialchars(trim($oneImg)) ?>"
                                onclick="event.stopPropagation(); openImageModal(this.src)"
                                class="w-full max-h-[500px] object-cover hover:opacity-95 transition">
                        </div>

                        <?php elseif ($imgCount > 1): ?>
                        <div class="mt-2 mb-2 relative group rounded-xl overflow-hidden border border-gray-200 bg-gray-50"
                            id="carousel-<?= $post['POST_ID'] ?>">

                            <div class="carousel-track flex overflow-x-auto snap-x snap-mandatory scrollbar-hide bg-black"
                                style="scrollbar-width: none; -ms-overflow-style: none;"
                                onscroll="updateProfileCarousel('carousel-<?= $post['POST_ID'] ?>', 'btn-prev-<?= $post['POST_ID'] ?>', 'btn-next-<?= $post['POST_ID'] ?>')">

                                <?php foreach($images as $img): ?>
                                <div
                                    class="snap-center shrink-0 w-full flex justify-center items-center bg-gray-100/50 aspect-[4/3] sm:aspect-auto sm:h-[500px]">
                                    <img src="<?= htmlspecialchars(trim($img)) ?>"
                                        class="max-h-full max-w-full object-contain cursor-zoom-in"
                                        onclick="event.stopPropagation(); openImageModal(this.src)">
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div
                                class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5 z-10 pointer-events-none">
                                <?php for($i=0; $i<$imgCount; $i++): ?>
                                <div
                                    class="dot-indicator w-1.5 h-1.5 rounded-full bg-white/60 transition-all <?= $i===0?'bg-white w-2.5 scale-110':'' ?>">
                                </div>
                                <?php endfor; ?>
                            </div>

                            <button id="btn-prev-<?= $post['POST_ID'] ?>"
                                onclick="event.stopPropagation(); scrollProfileCarousel('carousel-<?= $post['POST_ID'] ?>', -1)"
                                class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-1.5 rounded-full opacity-0 group-hover:opacity-100 transition hover:bg-black/70 hidden">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <button id="btn-next-<?= $post['POST_ID'] ?>"
                                onclick="event.stopPropagation(); scrollProfileCarousel('carousel-<?= $post['POST_ID'] ?>', 1)"
                                class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-1.5 rounded-full opacity-0 group-hover:opacity-100 transition hover:bg-black/70">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                        <?php endif; ?>
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
// ==========================================
// 1. FUNGSI CAROUSEL (KHUSUS PROFIL)
// ==========================================
function scrollProfileCarousel(id, direction) {
    const container = document.getElementById(id);
    if (!container) return;
    const track = container.querySelector('.carousel-track');
    if (!track) return;
    const scrollAmount = track.clientWidth;
    track.scrollBy({
        left: direction * scrollAmount,
        behavior: 'smooth'
    });
}

function updateProfileCarousel(id, prevBtnId, nextBtnId) {
    const container = document.getElementById(id);
    if (!container) return;

    const track = container.querySelector('.carousel-track');
    const dots = container.querySelectorAll('.dot-indicator');
    const prevBtn = document.getElementById(prevBtnId);
    const nextBtn = document.getElementById(nextBtnId);

    if (!track) return;

    // A. Update Active Dot
    const index = Math.round(track.scrollLeft / track.clientWidth);
    dots.forEach((dot, idx) => {
        if (idx === index) {
            dot.classList.add('bg-white', 'w-2.5', 'scale-110');
            dot.classList.remove('bg-white/60');
        } else {
            dot.classList.remove('bg-white', 'w-2.5', 'scale-110');
            dot.classList.add('bg-white/60');
        }
    });

    // B. Smart Nav Button Logic
    const atStart = track.scrollLeft <= 5;
    const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 5;

    if (prevBtn) {
        if (atStart) prevBtn.classList.add('hidden');
        else prevBtn.classList.remove('hidden');
    }

    if (nextBtn) {
        if (atEnd) nextBtn.classList.add('hidden');
        else nextBtn.classList.remove('hidden');
    }
}

// ==========================================
// 2. FUNGSI LIKE & MODAL (UTILITY)
// ==========================================
function handleLikeProfile(btn, postId) {
    btn.disabled = true;
    const countSpan = btn.querySelector('.like-count');
    const svgIcon = btn.querySelector('svg');

    let currentCount = parseInt(countSpan.innerText) || 0;
    const isLiked = btn.classList.contains('text-red-500');

    if (isLiked) {
        btn.classList.remove('text-red-500');
        btn.classList.add('text-gray-500');
        svgIcon.classList.remove('fill-current');
        svgIcon.classList.add('fill-none', 'stroke-current');
        countSpan.innerText = currentCount > 1 ? currentCount - 1 : '';
    } else {
        btn.classList.remove('text-gray-500');
        btn.classList.add('text-red-500');
        svgIcon.classList.remove('fill-none', 'stroke-current');
        svgIcon.classList.add('fill-current');
        countSpan.innerText = currentCount + 1;
        svgIcon.style.transform = "scale(1.2)";
        setTimeout(() => svgIcon.style.transform = "scale(1)", 200);
    }

    const fd = new FormData();
    fd.append('post_id', postId);
    fetch('index.php?page=post-api&method=toggleLike', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
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

function openImageModal(src) {
    const m = document.createElement('div');
    m.style.cssText =
        'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.95);z-index:9999;display:flex;justify-content:center;align-items:center;cursor:pointer;backdrop-filter:blur(5px);';
    m.onclick = () => m.remove();
    m.innerHTML =
        `<img src="${src}" style="max-width:95%;max-height:95%;border-radius:8px;object-fit:contain;box-shadow:0 0 20px rgba(0,0,0,0.5);">`;
    document.body.appendChild(m);
}
</script>

<style>
/* Utilities Tambahan */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<?php require 'app/views/partials/sidebar_kanan.php'; ?>