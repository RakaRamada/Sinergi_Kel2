<div class="col-span-10 flex h-[calc(100vh-10px)] bg-gray-100 overflow-hidden relative">

    <main class="w-80 border-r border-gray-200 flex flex-col h-full bg-white hidden md:flex shrink-0 z-10">
        <div class="p-4 border-b border-gray-200 h-16 flex items-center shrink-0">
            <h2 class="text-lg font-bold">Grup Diskusi</h2>
        </div>
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <?php if (isset($groups) && !empty($groups)): ?>
            <?php foreach ($groups as $grp): ?>
            <?php 
                $grp_id = $grp['group_id'];
                $is_active = ($post['group_id'] == $grp_id);
                $grp_img = !empty($grp['group_image']) ? '/Sinergi/public/uploads/group_profiles/' . $grp['group_image'] : '/Sinergi/public/assets/images/user.png';
            ?>
            <a href="index.php?page=messages&group_id=<?= $grp_id ?>&tab=forum"
                class="flex items-center p-3 border-b border-gray-100 hover:bg-gray-50 transition <?= $is_active ? 'bg-blue-50/60 border-l-4 border-l-blue-500' : '' ?>">
                <img src="<?= $grp_img ?>" class="w-10 h-10 rounded-full mr-3 object-cover border border-gray-200">
                <div class="flex-1 overflow-hidden">
                    <p class="font-bold text-sm text-gray-800 truncate"><?= htmlspecialchars($grp['nama_group']) ?></p>
                    <p class="text-xs text-gray-500 truncate">Lihat diskusi</p>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <aside class="flex-1 flex flex-col h-full bg-[#f0f2f5] min-w-0 relative">

        <div class="flex items-center space-x-3 px-4 border-b border-gray-200 bg-white z-20 h-16 shrink-0 shadow-sm">
            <a href="index.php?page=messages&group_id=<?= $post['group_id'] ?>&tab=forum"
                class="p-2 rounded-full hover:bg-gray-100 transition-colors text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h2 class="text-base font-bold text-gray-900 leading-tight">Detail Diskusi</h2>
                <p class="text-xs text-gray-500">Forum Grup</p>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-0 md:p-4" id="scroll-container">
            <div class="max-w-3xl mx-auto w-full pb-4">

                <div class="bg-white p-5 md:rounded-xl border-b md:border border-gray-200 shadow-sm mb-4">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <img src="<?= htmlspecialchars($post['avatar_url_fixed']) ?>"
                                class="w-10 h-10 rounded-full object-cover border border-gray-100">
                            <div>
                                <p class="font-bold text-gray-900 text-sm">
                                    <?= htmlspecialchars($post['nama_lengkap']) ?></p>
                                <p class="text-xs text-gray-500">@<?= htmlspecialchars($post['username']) ?> ·
                                    <?= htmlspecialchars($post['waktu_lalu']) ?></p>
                            </div>
                        </div>
                        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                        <button onclick="deleteMainPost(<?= $post['post_id'] ?>, <?= $post['group_id'] ?>)"
                            class="text-gray-400 hover:text-red-500 p-2 rounded-full hover:bg-red-50 transition"
                            title="Hapus Diskusi">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="text-gray-800 text-base leading-relaxed mb-4 text-left whitespace-normal break-words">
                        <?= nl2br(htmlspecialchars(trim($post['konten'] ?? ''))) ?></div>

                    <?php if (!empty($post['image_path'])): ?>
                    <div class="mb-4 rounded-lg overflow-hidden border border-gray-100 shadow-sm bg-gray-50">
                        <img src="/Sinergi/public/uploads/forum_posts/<?= htmlspecialchars($post['image_path']) ?>"
                            class="w-full h-auto max-h-[500px] object-contain mx-auto cursor-pointer hover:opacity-95 transition"
                            onclick="openImageModal(this.src)">
                    </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-6 pt-3 border-t border-gray-50 text-sm text-gray-500 font-medium">
                        <button onclick="toggleLike(this, <?= $post['post_id'] ?>)"
                            class="flex items-center gap-1.5 transition group <?= ($post['user_has_liked'] > 0) ? 'text-red-500' : 'text-gray-500 hover:text-red-500' ?>">
                            <svg class="w-5 h-5 <?= ($post['user_has_liked'] > 0) ? 'fill-current' : 'fill-none stroke-current' ?>"
                                viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                            <span class="like-count"><?= $post['like_count'] ?></span> Suka
                        </button>
                        <span class="flex items-center gap-1.5 text-blue-600">
                            <svg class="w-5 h-5 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                </path>
                            </svg>
                            <span id="detail-count"><?= count($comments) ?></span> Balasan
                        </span>
                    </div>
                </div>

                <?php
                    // 1. Grouping Komentar
                    $parents = [];
                    $replies = [];
                    foreach ($comments as $c) {
                        if (empty($c['parent_comment_id'])) {
                            $parents[] = $c;
                        } else {
                            $replies[$c['parent_comment_id']][] = $c;
                        }
                    }
                ?>

                <div
                    class="bg-white md:rounded-xl border-y md:border border-gray-200 shadow-sm min-h-[200px] mb-2 overflow-visible">
                    <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/50 md:rounded-t-xl">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Komentar</h3>
                    </div>

                    <div id="detail-comment-list" class="divide-y divide-gray-100">
                        <?php if (empty($comments)): ?>
                        <div id="no-comment-placeholder" class="text-center py-12 text-gray-400">
                            <p class="text-sm">Belum ada balasan.</p>
                            <p class="text-xs mt-1">Jadilah yang pertama menanggapi!</p>
                        </div>
                        <?php else: ?>

                        <?php foreach ($parents as $p): ?>
                        <?php 
                                $has_reply = isset($replies[$p['comment_id']]);
                                $reply_count = $has_reply ? count($replies[$p['comment_id']]) : 0;
                            ?>

                        <div class="p-4 bg-white border-b border-gray-100 flex gap-4 group relative"
                            id="comment-row-<?= $p['comment_id'] ?>">
                            <img src="<?= htmlspecialchars($p['avatar_url_fixed']) ?>"
                                class="w-10 h-10 rounded-full object-cover border border-gray-100 shrink-0">

                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline gap-2 mb-1">
                                    <span
                                        class="font-bold text-sm text-gray-900"><?= htmlspecialchars($p['nama_lengkap']) ?></span>
                                    <span class="text-xs text-gray-400">@<?= htmlspecialchars($p['username']) ?> ·
                                        <?= htmlspecialchars($p['waktu_lalu']) ?></span>
                                </div>

                                <div class="text-sm text-gray-800 leading-relaxed whitespace-normal break-words mb-2">
                                    <?= nl2br(htmlspecialchars(trim($p['isi_komentar'] ?? ''))) ?>
                                </div>

                                <div class="flex items-center gap-4">

                                    <button
                                        onclick="replyToComment(<?= $p['comment_id'] ?>, '<?= htmlspecialchars($p['nama_lengkap'], ENT_QUOTES) ?>', '')"
                                        class="flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-blue-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                        </svg>
                                        Balas
                                    </button>

                                    <?php if($has_reply): ?>
                                    <button onclick="toggleReplies('replies-<?= $p['comment_id'] ?>')"
                                        class="flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-800 transition ml-2">
                                        <div class="h-[1px] w-6 bg-gray-300"></div>
                                        <span>Lihat <?= $reply_count ?> balasan</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($p['user_id'] == $_SESSION['user_id']): ?>
                            <div class="shrink-0 ml-2">
                                <button onclick="deleteComment(<?= $p['comment_id'] ?>)"
                                    class="p-2 text-gray-300 hover:text-red-500 hover:bg-gray-50 rounded-full transition border border-transparent hover:border-gray-200"
                                    title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($has_reply): ?>
                        <div id="replies-<?= $p['comment_id'] ?>" class="hidden bg-gray-50 border-b border-gray-100">
                            <?php foreach ($replies[$p['comment_id']] as $r): ?>
                            <div class="pl-20 pr-4 py-3 flex gap-3 group relative border-t border-gray-100 first:border-t-0"
                                id="comment-row-<?= $r['comment_id'] ?>">
                                <img src="<?= htmlspecialchars($r['avatar_url_fixed']) ?>"
                                    class="w-8 h-8 rounded-full object-cover border border-gray-200 shrink-0">

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline gap-2 mb-1">
                                        <span
                                            class="font-bold text-sm text-gray-900"><?= htmlspecialchars($r['nama_lengkap']) ?></span>
                                        <span class="text-xs text-gray-400">@<?= htmlspecialchars($r['username']) ?> ·
                                            <?= htmlspecialchars($r['waktu_lalu']) ?></span>
                                    </div>
                                    <div
                                        class="text-sm text-gray-800 leading-relaxed whitespace-normal break-words mb-1">
                                        <?= nl2br(htmlspecialchars(trim($r['isi_komentar'] ?? ''))) ?>
                                    </div>

                                </div>

                                <?php if ($r['user_id'] == $_SESSION['user_id']): ?>
                                <div class="shrink-0 ml-2">
                                    <button onclick="deleteComment(<?= $r['comment_id'] ?>)"
                                        class="p-1.5 text-gray-300 hover:text-red-500 hover:bg-white rounded-full transition"
                                        title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <div class="w-full bg-white border-t border-gray-200 px-4 p-2 z-40 shrink-0 mb-14 lg:mb-0">
            <div id="reply-preview"
                class="hidden flex items-center justify-between bg-blue-50 p-2 mb-2 rounded-lg border-l-4 border-blue-500 text-xs shadow-sm mx-auto max-w-3xl">
                <div class="overflow-hidden">
                    <span class="font-bold text-blue-600 block mb-0.5">Membalas <span
                            id="reply-target-name">...</span></span>
                    <span class="text-gray-500 truncate block max-w-xs" id="reply-target-text">...</span>
                </div>
                <button onclick="cancelReply()"
                    class="text-gray-400 hover:text-red-500 p-1 bg-white rounded-full shadow-sm ml-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <form onsubmit="submitDetailComment(event, <?= $post['post_id'] ?>)"
                class="flex gap-3 items-center max-w-3xl mx-auto">
                <input type="hidden" name="parent_id" id="parent_id_input" value="">
                <?php 
                    // FIX LOGIKA AVATAR (Mencegah Double Path)
                    $sess_avatar = $_SESSION['avatar_url'] ?? '';
                    
                    if (empty($sess_avatar)) {
                        // Jika kosong, pakai default
                        $my_avatar = '/Sinergi/public/assets/images/user.png';
                    } elseif (strpos($sess_avatar, '/') !== false) {
                        // Jika sudah ada tanda slash '/', berarti sudah full path -> Pakai apa adanya
                        $my_avatar = $sess_avatar;
                    } else {
                        // Jika cuma nama file, baru kita tambahkan path foldernya
                        $my_avatar = '/Sinergi/public/uploads/avatars/' . $sess_avatar;
                    }
                ?>
                <img src="<?= $my_avatar ?>" class="w-8 h-8 rounded-full object-cover border hidden sm:block">
                <div
                    class="flex-1 bg-gray-100 rounded-2xl px-4 py-2 border border-transparent focus-within:border-blue-300 focus-within:bg-white transition-all">
                    <textarea name="isi_komentar" id="main-comment-input" placeholder="Tulis balasan..."
                        class="w-full bg-transparent border-none focus:ring-0 text-sm resize-none overflow-hidden text-gray-800 placeholder-gray-500 text-left"
                        style="min-height: 24px; max-height: 100px;" rows="1"
                        oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                </div>
                <button type="submit"
                    class="bg-black hover:bg-gray-800 text-white p-2.5 rounded-full shadow-md transition shrink-0 flex items-center justify-center">
                    <svg class="w-5 h-5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" transform="rotate(90 12 12)"></path>
                    </svg>
                </button>
            </form>
        </div>

    </aside>
</div>

<script>
// Toggle Visibility Balasan
function toggleReplies(elementId) {
    const el = document.getElementById(elementId);
    el.classList.toggle('hidden');
}

// Trigger Reply Mode
function replyToComment(parentId, name, text) {
    document.getElementById('reply-target-name').innerText = name;
    document.getElementById('reply-target-text').innerText = text || 'Balasan...';
    document.getElementById('reply-preview').classList.remove('hidden');
    document.getElementById('parent_id_input').value = parentId; // ID Bapaknya
    document.getElementById('main-comment-input').focus();

    // Buka container balasan jika tertutup
    const repliesContainer = document.getElementById('replies-' + parentId);
    if (repliesContainer && repliesContainer.classList.contains('hidden')) {
        repliesContainer.classList.remove('hidden');
    }
}

function cancelReply() {
    document.getElementById('reply-preview').classList.add('hidden');
    document.getElementById('parent_id_input').value = '';
}

function submitDetailComment(e, postId) {
    e.preventDefault();
    const input = e.target.querySelector('textarea');
    const parentId = document.getElementById('parent_id_input').value;
    if (!input.value.trim()) return;

    const fd = new FormData();
    fd.append('post_id', postId);
    fd.append('isi_komentar', input.value);
    if (parentId) fd.append('parent_id', parentId);

    fetch('index.php?page=api-store-forum-comment', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert('Gagal: ' + (data.message || 'Error'));
            }
        });
}

let commentIdToDelete = null;

function deleteComment(commentId) {
    commentIdToDelete = commentId;
    const modal = document.getElementById('deleteCommentModal');
    const content = document.getElementById('deleteCommentContent');
    
    if (modal && content) {
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.remove('opacity-0', 'pointer-events-none');
        modal.classList.add('opacity-100');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }
}

function closeDeleteCommentModal() {
    commentIdToDelete = null;
    const modal = document.getElementById('deleteCommentModal');
    const content = document.getElementById('deleteCommentContent');
    
    if (modal && content) {
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0', 'pointer-events-none');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 200);
    }
}

function confirmDeleteComment() {
    if (!commentIdToDelete) return;
    
    const btn = document.getElementById('confirmDeleteCommentBtn');
    btn.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('comment_id', commentIdToDelete);

    fetch('index.php?page=api-delete-forum-comment', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const row = document.getElementById('comment-row-' + commentIdToDelete);

                if (row) {
                    const parentContainer = row.parentElement;
                    const isReply = parentContainer && parentContainer.id.startsWith('replies-');
                    row.remove();

                    if (isReply) {
                        const parentId = parentContainer.id.replace('replies-', '');
                        const toggleBtn = document.querySelector(
                            `button[onclick="toggleReplies('replies-${parentId}')"]`);

                        if (toggleBtn) {
                            const remaining = parentContainer.children.length;
                            if (remaining === 0) {
                                toggleBtn.parentElement.remove();
                                parentContainer.remove();
                            } else {
                                const spanText = toggleBtn.querySelector('span');
                                if (spanText) spanText.innerText = `Lihat ${remaining} balasan`;
                            }
                        }
                    }
                }
                closeDeleteCommentModal();
            } else {
                alert('Gagal menghapus');
                closeDeleteCommentModal();
            }
        })
        .catch(err => {
            console.error(err);
            closeDeleteCommentModal();
        });
}

function toggleLike(btn, postId) {
    /* Logic Like sama */
    const icon = btn.querySelector('svg');
    const countSpan = btn.querySelector('.like-count');
    let count = parseInt(countSpan.innerText);
    const isLiked = btn.classList.contains('text-red-500');
    if (isLiked) {
        btn.classList.remove('text-red-500');
        btn.classList.add('text-gray-500');
        icon.classList.remove('fill-current');
        icon.classList.add('fill-none', 'stroke-current');
        countSpan.innerText = Math.max(0, count - 1);
    } else {
        btn.classList.add('text-red-500');
        btn.classList.remove('text-gray-500');
        icon.classList.remove('fill-none', 'stroke-current');
        icon.classList.add('fill-current');
        countSpan.innerText = count + 1;
    }
    const fd = new FormData();
    fd.append('post_id', postId);
    fetch('index.php?page=api-like-forum-post', {
        method: 'POST',
        body: fd
    });
}

function deleteMainPost(postId, groupId) {
    if (!confirm('Hapus diskusi ini selamanya?')) return;
    const fd = new FormData();
    fd.append('post_id', postId);
    fetch('index.php?page=api-delete-forum-post', {
        method: 'POST',
        body: fd
    }).then(r => r.json()).then(d => {
        if (d.status === 'success') {
            window.location.href = `index.php?page=messages&group_id=${groupId}&tab=forum`;
        } else {
            alert('Gagal menghapus');
        }
    });
}

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

document.addEventListener("DOMContentLoaded", function() {
    const inputKomen = document.getElementById('main-comment-input');

    if (inputKomen) {
        inputKomen.addEventListener('keydown', function(e) {
            // Cek jika tombol yang ditekan adalah ENTER
            if (e.key === 'Enter') {
                // Jika Shift TIDAK ditekan (Enter saja)
                if (!e.shiftKey) {
                    e.preventDefault(); // Mencegah pembuatan baris baru (default enter)

                    // Cek apakah isi tidak kosong
                    if (this.value.trim() !== "") {
                        // Cari tombol submit di form terdekat dan klik secara otomatis
                        const form = this.closest('form');
                        const submitBtn = form.querySelector('button[type="submit"]');
                        if (submitBtn) submitBtn.click();
                    }
                }
                // Jika Shift DITEKAN (Shift + Enter), biarkan default (buat baris baru)
            }
        });
    }
    // Auto scroll ke bawah saat load
    const feed = document.getElementById('scroll-container');
    if (feed) feed.scrollTop = feed.scrollHeight;
});
</script>

<!-- Delete Comment Modal -->
<div id="deleteCommentModal"
    class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm transition-opacity opacity-0 pointer-events-none"
    aria-modal="true">

    <div id="deleteCommentContent"
        class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm transform scale-95 transition-transform duration-200">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-bold text-gray-900">Hapus Komentar?</h3>
            <p class="text-sm text-gray-500 mt-2">
                Komentar ini akan dihapus secara permanen.
            </p>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="button" onclick="closeDeleteCommentModal()"
                class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                Batal
            </button>
            <button type="button" id="confirmDeleteCommentBtn" onclick="confirmDeleteComment()"
                class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2.5 bg-red-600 text-base font-medium text-white hover:bg-red-700 transition cursor-pointer shadow-red-200">
                Hapus
            </button>
        </div>
    </div>
</div>