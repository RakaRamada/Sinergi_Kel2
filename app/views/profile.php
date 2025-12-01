<main class="col-span-6 border-r border-gray-200 min-h-screen bg-white">
    
    <div class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-20">
        <a href="index.php?page=dashboard" title="Kembali" class="p-2 rounded-full hover:bg-gray-200 transition">
            <img src="/Sinergi/public/assets/icons/arrow-left.svg" alt="Kembali" class="w-6 h-6">
        </a>
        <div>
            <h2 class="text-xl font-bold leading-none"><?= htmlspecialchars($profile_data['NAMA_LENGKAP']); ?></h2>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($profile_data['TOTAL_POSTINGAN'] ?? 0); ?> postingan</p>
        </div>
    </div>

    <div>
        <div class="relative">
            <img src="<?= htmlspecialchars($profile_data['HEADER_URL_FIXED']); ?>" 
                 alt="Header Profil" 
                 class="w-full h-48 object-cover bg-gray-200">
            
            <div class="absolute -bottom-16 left-4">
                <img src="<?= htmlspecialchars($profile_data['AVATAR_URL_FIXED']); ?>" 
                     alt="Avatar"
                     class="w-32 h-32 rounded-full border-4 border-white object-cover bg-white shadow-sm">
            </div>
        </div>

        <div class="text-right p-4 pb-0 h-16"> <?php if ($is_my_profile): ?>
                <a href="index.php?page=edit_profile" class="inline-block border border-gray-300 font-bold py-2 px-4 rounded-full hover:bg-gray-100 text-sm transition">
                    Edit profile
                </a>
            <?php endif; ?>
        </div>

        <div class="px-4 mt-4 pb-4">
            <h2 class="text-xl font-bold text-black leading-tight"><?= htmlspecialchars($profile_data['NAMA_LENGKAP']); ?></h2>
            <p class="text-gray-500 mb-3">@<?= htmlspecialchars($profile_data['USERNAME']); ?></p>

            <div class="text-gray-700 mb-3 text-sm flex items-center">
                <span class="font-medium"><?= htmlspecialchars($profile_data['ROLE_NAME'] ?? 'User'); ?></span>
                <?php if (!empty($profile_data['NIM'])): ?>
                    <span class="mx-2 text-gray-300">|</span>
                    <span><?= htmlspecialchars($profile_data['NIM']); ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($profile_data['BIO'])): ?>
                <!-- Bio menggunakan whitespace-pre-line, jadi aman tanpa nl2br atau bisa disesuaikan -->
                <div class="text-gray-800 mb-3 text-sm whitespace-pre-line leading-relaxed">
                    <?= htmlspecialchars($profile_data['BIO']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="border-b border-gray-200 mt-2 sticky top-[73px] bg-white z-10">
        <div class="flex px-4">
            <div class="border-b-4 border-black py-3 px-4 font-bold text-black text-sm cursor-pointer hover:bg-gray-50 transition">
                Postingan
            </div>
            </div>
    </div>

    <div class="bg-white min-h-screen pb-20">
        
        <?php if (empty($user_posts)): ?>
            <div class="py-10 text-center">
                <p class="text-xl font-bold text-gray-800 mb-2">Belum ada postingan</p>
                <p class="text-gray-500 text-sm">Saat pengguna ini memposting sesuatu, akan muncul di sini.</p>
            </div>
        <?php else: ?>
            
            <?php foreach ($user_posts as $post): ?>
                
                <?php 
                    $is_liked = ($post['USER_SUDAH_LIKE'] > 0);
                    $likeColorClass = $is_liked ? 'text-red-500' : 'text-gray-500';
                    $iconFilterClass = $is_liked ? 'filter-red' : ''; 
                ?>

                <div class="bg-white border-b border-gray-200 hover:bg-gray-50/30 transition-colors cursor-pointer" 
                     onclick="window.location.href='index.php?page=post-detail&id=<?= $post['POST_ID'] ?>'">
                    
                    <div class="p-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 cursor-pointer" onclick="event.stopPropagation(); window.location.href='index.php?page=profile&id=<?= $post['USER_ID'] ?>'">
                                <img src="<?= htmlspecialchars($post['AVATAR_URL_FIXED']) ?>" 
                                     alt="Avatar" 
                                     class="w-10 h-10 rounded-full object-cover bg-gray-200 border border-gray-100">
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center flex-wrap space-x-1 mb-1">
                                    <a href="index.php?page=profile&id=<?= $post['USER_ID'] ?>" 
                                       onclick="event.stopPropagation();"
                                       class="font-bold text-gray-900 hover:underline text-[15px]">
                                        <?= htmlspecialchars($post['NAMA_LENGKAP']) ?>
                                    </a>
                                    <span class="text-gray-500 text-sm truncate">@<?= htmlspecialchars($post['USERNAME']) ?></span>
                                    <span class="text-gray-400 text-sm">·</span>
                                    <span class="text-gray-500 text-sm hover:underline cursor-pointer" 
                                          title="<?= htmlspecialchars($post['CREATED_AT_STR'] ?? '') ?>">
                                        <?= htmlspecialchars($post['WAKTU_POSTING']) ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($post['KONTEN'])): ?>
                                    <div class="cursor-pointer mb-2">
                                        <!-- PERBAIKAN DISINI: Menghapus nl2br() -->
                                        <p class="text-gray-800 text-[15px] leading-normal break-words whitespace-pre-wrap"><?= htmlspecialchars($post['KONTEN']) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($post['POST_IMAGE'])): ?>
                                    <div class="mt-2 mb-2">
                                        <img src="<?= htmlspecialchars($post['POST_IMAGE']) ?>" 
                                             onclick="event.stopPropagation(); openImageModal('<?= htmlspecialchars($post['POST_IMAGE']) ?>')"
                                             class="rounded-xl w-full border border-gray-200 max-h-[500px] object-cover hover:opacity-95 transition">
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between mt-3 max-w-md text-gray-500">
                                    
                                    <button onclick="event.stopPropagation(); handleLike(this, <?= $post['POST_ID'] ?>)" 
                                            class="group flex items-center space-x-2 hover:text-red-500 transition-colors <?= $likeColorClass ?> w-fit">
                                        <div class="p-2 rounded-full group-hover:bg-red-50 transition-colors relative">
                                            <img src="/Sinergi/public/assets/icons/heart.svg" 
                                                 alt="Like" 
                                                 class="w-5 h-5 icon-transition">
                                        </div>
                                        <span class="like-count text-sm font-medium"><?= ($post['LIKE_COUNT'] > 0) ? $post['LIKE_COUNT'] : '' ?></span>
                                    </button>

                                    <a href="index.php?page=post-detail&id=<?= $post['POST_ID'] ?>"
                                       class="group flex items-center space-x-2 hover:text-blue-500 transition-colors w-fit">
                                        <div class="p-2 rounded-full group-hover:bg-blue-50 transition-colors">
                                            <img src="/Sinergi/public/assets/icons/comment.svg" 
                                                 alt="Komentar" 
                                                 class="w-5 h-5">
                                        </div>
                                        <span class="text-sm font-medium"><?= ($post['COMMENT_COUNT'] > 0) ? $post['COMMENT_COUNT'] : '' ?></span>
                                    </a>

                                    <button class="group flex items-center space-x-2 hover:text-green-500 transition-colors w-fit"
                                            onclick="event.stopPropagation(); alert('Fitur laporan akan segera hadir!')">
                                        <div class="p-2 rounded-full group-hover:bg-green-50 transition-colors">
                                            <img src="/Sinergi/public/assets/icons/report.svg" 
                                                 alt="Laporkan" 
                                                 class="w-5 h-5">
                                        </div>
                                    </button>

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
// Fungsi Like Postingan
function handleLike(btn, postId) {
    btn.disabled = true; // Cegah spam klik
    const countSpan = btn.querySelector('.like-count');
    const iconImg = btn.querySelector('img');
    
    // Optimistic UI Update (Langsung berubah sebelum request selesai)
    let currentCount = parseInt(countSpan.innerText) || 0;
    const isLiked = btn.classList.contains('text-red-500');
    
    if (isLiked) {
        btn.classList.remove('text-red-500');
        btn.classList.add('text-gray-500');
        countSpan.innerText = currentCount > 1 ? currentCount - 1 : '';
    } else {
        btn.classList.remove('text-gray-500');
        btn.classList.add('text-red-500');
        countSpan.innerText = currentCount + 1;
    }

    const fd = new FormData();
    fd.append('post_id', postId);

    // Pastikan URL API ini sesuai dengan struktur routing Anda
    fetch('index.php?page=post-api&method=toggleLike', { 
        method: 'POST',
        body: fd
    })
    .then(response => response.json())
    .then(data => {
        if (data.status !== 'success') {
            // Revert jika gagal
            console.error('Gagal like:', data.message);
            if (isLiked) {
                btn.classList.add('text-red-500');
                btn.classList.remove('text-gray-500');
                countSpan.innerText = currentCount;
            } else {
                btn.classList.add('text-gray-500');
                btn.classList.remove('text-red-500');
                countSpan.innerText = currentCount;
            }
        }
    })
    .catch(error => console.error('Error:', error))
    .finally(() => {
        btn.disabled = false;
    });
}

// Fungsi Modal Gambar (Preview Besar)
function openImageModal(src) {
    const m = document.createElement('div');
    m.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;display:flex;justify-content:center;align-items:center;cursor:pointer;';
    m.onclick = () => m.remove();
    
    const i = document.createElement('img');
    i.src = src;
    i.style.cssText = 'max-width:95%;max-height:95%;border-radius:8px;object-fit:contain;';
    
    m.appendChild(i); 
    document.body.appendChild(m);
}
</script>
<?php 
require 'app/views/partials/sidebar_kanan.php'; 
?>