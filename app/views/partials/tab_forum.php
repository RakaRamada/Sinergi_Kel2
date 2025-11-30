<div class="flex flex-col h-[calc(100vh-64px)] bg-[#f0f2f5] relative overflow-hidden">

    <div class="flex-1 overflow-y-auto p-4 space-y-6 custom-scrollbar" id="forumFeedContainer">

        <?php if (!empty($forum_posts)): ?>
        <?php foreach ($forum_posts as $post): ?>

        <?php 
            // --- LOGIKA POSISI ---
            $is_me = ($post['user_id'] == $_SESSION['user_id']);

            // STYLE DASAR (WHITE CARD UNTUK SEMUA)
            $card_base = "bg-white text-gray-900 border shadow-sm rounded-2xl p-4 relative group";
            
            if ($is_me) {
                // GAYA SAYA (Kanan)
                $align_wrapper = "justify-end pl-12"; 
                // Sedikit pembeda di border/sudut
                $card_style    = $card_base . " rounded-tr-none border-blue-100"; 
                $img_border    = "border-gray-100";
            } else {
                // GAYA ORANG LAIN (Kiri)
                $align_wrapper = "justify-start pr-12";
                $card_style    = $card_base . " rounded-tl-none border-gray-200";
                $img_border    = "border-gray-100";
            }

            // Warna tombol Like/Komen
            $btn_active    = "text-red-500";
            $btn_inactive  = "text-gray-400 hover:text-red-500";
            $btn_comment   = "text-gray-400 hover:text-blue-600";
        ?>

        <div class="flex w-full <?= $align_wrapper ?>">

            <div class="<?= $card_style ?> w-full max-w-[85%] md:max-w-[70%]">

                <div class="flex items-center gap-3 mb-3">
                    <img src="<?= $post['avatar_url_fixed'] ?>"
                        class="w-9 h-9 rounded-full object-cover border border-gray-100">
                    <div>
                        <span
                            class="font-bold text-sm text-gray-900 block leading-tight">@<?= htmlspecialchars($post['username']) ?></span>
                        <span class="text-[10px] text-gray-400"><?= htmlspecialchars($post['waktu_lalu']) ?></span>
                    </div>

                    <?php if ($is_me): ?>
                    <button onclick="deleteForumPost(<?= $post['post_id'] ?>)"
                        class="absolute top-3 right-3 text-gray-300 hover:text-red-500 transition p-1 rounded-full hover:bg-gray-50 opacity-0 group-hover:opacity-100"
                        title="Hapus Diskusi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($post['konten'])): ?>
                <div class="text-sm text-gray-800 mb-3 leading-relaxed whitespace-normal break-words">
                    <?= nl2br(htmlspecialchars(trim($post['konten']))) ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($post['image_path'])): ?>
                <div class="mb-3 rounded-lg overflow-hidden border <?= $img_border ?>">
                    <img src="/Sinergi/public/uploads/forum_posts/<?= htmlspecialchars($post['image_path']) ?>"
                        class="w-full h-auto max-h-[450px] object-cover hover:scale-[1.01] transition duration-500 cursor-pointer"
                        onclick="openImageModal(this.src)">
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-6 pt-3 border-t border-gray-100">

                    <button onclick="toggleLike(this, <?= $post['post_id'] ?>)"
                        class="flex items-center gap-1.5 text-xs font-medium transition group <?= ($post['user_has_liked'] > 0) ? $btn_active : $btn_inactive ?>">
                        <svg class="w-5 h-5 <?= ($post['user_has_liked'] > 0) ? 'fill-current' : 'fill-none stroke-current' ?>"
                            viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                        <span class="like-count"><?= $post['like_count'] ?></span>
                    </button>

                    <a href="index.php?page=forum-post-detail&post_id=<?= $post['post_id'] ?>"
                        class="flex items-center gap-1.5 text-xs font-medium transition <?= $btn_comment ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                        <span><?= $post['comment_count'] ?></span>
                    </a>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center h-full text-gray-400">
            <p>Belum ada diskusi.</p>
            <p class="text-sm">Jadilah yang pertama memposting!</p>
        </div>
        <?php endif; ?>
    </div>


    <div
        class="w-full bg-white border-t border-gray-200 px-3 py-3 z-20 shadow-[0_-2px_10px_rgba(0,0,0,0.05)] shrink-0 relative">

        <div id="imagePreviewArea"
            class="hidden absolute bottom-full left-4 mb-2 bg-white p-1.5 rounded-lg shadow-lg border border-gray-200">
            <img id="previewImg" src="#" class="h-20 w-auto rounded border border-gray-100 object-cover">
            <button type="button" onclick="clearImage()"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-0.5 shadow-md hover:bg-red-600 transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>

        <form action="index.php?page=store-forum-post" method="POST" enctype="multipart/form-data"
            class="flex items-end gap-2">
            <input type="hidden" name="group_id" value="<?= $current_group_id ?>">

            <div class="relative">
                <input type="file" name="post_image" id="hiddenFileInput" accept="image/*" class="hidden"
                    onchange="previewImage(this)">
                <button type="button" onclick="document.getElementById('hiddenFileInput').click()"
                    class="p-2.5 text-gray-500 hover:bg-gray-100 rounded-full transition shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </button>
            </div>

            <div
                class="flex-1 bg-gray-100 rounded-2xl px-4 py-1.5 border border-transparent focus-within:border-gray-300 focus-within:bg-white transition-colors">
                <textarea name="konten" placeholder="Mulai diskusi..."
                    class="w-full bg-transparent border-none focus:ring-0 text-sm resize-none overflow-hidden py-2 text-gray-800 placeholder-gray-500"
                    style="min-height: 24px; max-height: 100px;" rows="1"
                    oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
            </div>

            <button type="submit"
                class="p-2.5 bg-black text-white rounded-full hover:bg-gray-800 transition shrink-0 shadow-sm flex items-center justify-center">
                <svg class="w-5 h-5 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                        transform="rotate(90 12 12)" />
                </svg>
            </button>
        </form>
    </div>
</div>

<script>
// === LOGIKA JAVASCRIPT ===

// 1. Preview Gambar
function previewImage(input) {
    const previewArea = document.getElementById('imagePreviewArea');
    const previewImg = document.getElementById('previewImg');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewArea.classList.remove('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// 2. Hapus Gambar
function clearImage() {
    const input = document.getElementById('hiddenFileInput');
    const previewArea = document.getElementById('imagePreviewArea');
    input.value = '';
    previewArea.classList.add('hidden');
}

// 3. Like Postingan
function toggleLike(btn, postId) {
    const icon = btn.querySelector('svg');
    const countSpan = btn.querySelector('.like-count');
    let count = parseInt(countSpan.innerText);

    const isLiked = btn.classList.contains('text-red-500');

    if (isLiked) {
        btn.classList.remove('text-red-500');
        btn.classList.add('text-gray-400');
        icon.classList.remove('fill-current');
        icon.classList.add('fill-none', 'stroke-current');
        countSpan.innerText = Math.max(0, count - 1);
    } else {
        btn.classList.add('text-red-500');
        btn.classList.remove('text-gray-400');
        icon.classList.remove('fill-none', 'stroke-current');
        icon.classList.add('fill-current');
        countSpan.innerText = count + 1;
    }

    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('index.php?page=api-like-forum-post', {
        method: 'POST',
        body: formData
    });
}

// 4. Hapus Postingan
function deleteForumPost(postId) {
    if (!confirm('Yakin ingin menghapus diskusi ini?')) return;

    const fd = new FormData();
    fd.append('post_id', postId);

    fetch('index.php?page=api-delete-forum-post', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert(data.message || 'Gagal menghapus postingan');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan koneksi');
        });
}

// 5. Modal Gambar Besar
function openImageModal(src) {
    const m = document.createElement('div');
    m.style.cssText =
        'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.95);z-index:9999;display:flex;justify-content:center;align-items:center;cursor:pointer;';
    m.onclick = () => m.remove();
    const i = document.createElement('img');
    i.src = src;
    i.style.cssText = 'max-width:95%;max-height:95%;border-radius:8px;box-shadow:0 0 20px rgba(0,0,0,0.5);';
    m.appendChild(i);
    document.body.appendChild(m);
}

// 6. Auto Scroll ke Bawah
document.addEventListener("DOMContentLoaded", function() {
    const feed = document.getElementById('forumFeedContainer');
    if (feed) {
        feed.scrollTop = feed.scrollHeight;
    }
});
</script>