<?php
// --- LOGIKA AVATAR PENGGUNA YANG LOGIN ---
// Kita set di paling atas agar bisa dipakai di form komentar utama & form reply
$myAvatar = !empty($_SESSION['avatar_url']) ? $_SESSION['avatar_url'] : '/Sinergi/public/assets/images/user.png';
?>

<main class="col-span-6 border-r border-gray-200 min-h-screen pb-20 bg-white">
    <!-- Header -->
    <div class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/95 backdrop-blur-sm z-20">
        <a href="index.php?page=dashboard" class="p-2 rounded-full hover:bg-gray-100 transition-colors">
            <img src="/Sinergi/public/assets/icons/arrow-left.svg" alt="Back" class="w-6 h-6">
        </a>
        <h2 class="text-xl font-bold text-gray-900">Postingan</h2>
    </div>

    <?php if (!empty($post)): ?>
    <div class="animate-fade-in">
        <div class="p-4 border-b border-gray-200 relative">
            
            <!-- Tombol Hapus Postingan (Hanya Pemilik) -->
            <?php if ($post['USER_ID'] == $_SESSION['user_id']): ?>
            <button onclick="deletePost(<?php echo $post['POST_ID']; ?>)" 
                    class="absolute top-4 right-4 group p-2 rounded-full hover:bg-red-50 transition-all"
                    title="Hapus Postingan">
                <img src="/Sinergi/public/assets/icons/delete.svg" 
                     class="w-5 h-5 opacity-40 group-hover:opacity-100 group-hover:filter group-hover:invert-15 group-hover:sepia group-hover:saturate-5000 group-hover:hue-rotate-350" 
                     alt="Hapus">
            </button>
            <?php endif; ?>

            <!-- Info Pemilik Postingan -->
            <div class="flex items-center mb-4"> 
                <img src="<?php echo htmlspecialchars($post['AVATAR_URL_FIXED']); ?>" 
                     class="w-12 h-12 rounded-full mr-3 object-cover border border-gray-100 shadow-sm">
                <div>
                    <p class="font-bold text-gray-900 text-lg leading-tight"><?php echo htmlspecialchars($post['NAMA_LENGKAP']); ?></p>
                    <p class="text-sm text-gray-500">@<?php echo htmlspecialchars($post['USERNAME']); ?></p>
                </div>
            </div>
            
            <!-- Konten Postingan -->
            <div class="text-gray-800 text-[15px] mb-4 whitespace-pre-wrap leading-normal break-words">
                <?php echo htmlspecialchars($post['KONTEN'] ?? ''); ?>
            </div>
            
            <!-- Gambar Postingan -->
            <?php if (!empty($post['POST_IMAGE'])): ?>
            <div class="mb-4 rounded-xl overflow-hidden border border-gray-100 shadow-sm">
                <img src="<?php echo htmlspecialchars($post['POST_IMAGE']); ?>" 
                     onclick="openImageModal('<?php echo htmlspecialchars($post['POST_IMAGE'], ENT_QUOTES); ?>')" 
                     class="w-full h-auto max-h-[600px] object-cover cursor-zoom-in hover:brightness-95 transition-all">
            </div>
            <?php endif; ?>
            
            <div class="text-gray-500 text-sm mb-4 border-b border-gray-100 pb-4">
                <?php echo htmlspecialchars($post['WAKTU_POSTING']); ?>
            </div>
            
            <!-- Stats -->
            <div class="flex items-center space-x-6 text-gray-500 text-sm font-medium">
                <span class="flex items-center space-x-1">
                    <b class="text-gray-900"><?php echo $post['TOTAL_LIKES']; ?></b> <span>Suka</span>
                </span>
                <span class="flex items-center space-x-1">
                    <b class="text-gray-900" id="count-komen"><?php echo $post['TOTAL_COMMENTS']; ?></b> <span>Balasan</span>
                </span>
            </div>
        </div>
    </div>

    <!-- FORM KOMENTAR UTAMA -->
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <form id="comment-form" class="flex items-start space-x-3"> 
            <input type="hidden" name="post_id" value="<?php echo $post['POST_ID']; ?>">
            
            <!-- FIX: Menggunakan variabel $myAvatar yang sudah didefinisikan di atas -->
            <img src="<?php echo htmlspecialchars($myAvatar); ?>" 
                 class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm bg-white"
                 alt="Avatar Saya">
                 
            <div class="flex-1">
                <textarea name="isi_komen" 
                          class="w-full border border-gray-300 rounded-lg p-3 text-gray-800 focus:ring-2 focus:ring-black focus:border-transparent resize-none bg-white min-h-[50px] transition-all" 
                          rows="2" 
                          placeholder="Kirim balasan Anda..."
                          maxlength="500"></textarea>
                <div class="flex justify-end mt-2">
                    <button type="submit" 
                            id="btn-reply" 
                            class="bg-black text-white font-bold py-2 px-6 rounded-full hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                        Balas
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- LIST KOMENTAR -->
    <div id="comment-list" class="pb-10">
        <?php if (empty($comments)): ?>
            <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                <img src="/Sinergi/public/assets/icons/chat.svg" class="w-12 h-12 opacity-20 mb-2">
                <p>Belum ada balasan. Jadilah yang pertama!</p>
            </div>
        <?php else: ?>
            <?php foreach ($comments as $c): ?>
            <div id="comment-<?php echo $c['COMMENT_ID']; ?>" class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                <div class="p-4 relative group">
                    <!-- Tombol Hapus Komentar (Milik Sendiri) -->
                    <?php if ($c['USER_ID'] == $_SESSION['user_id']): ?>
                        <button onclick="deleteComment(<?php echo $c['COMMENT_ID']; ?>)" 
                                class="absolute top-3 right-3 p-1.5 rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    <?php endif; ?>

                    <div class="flex items-start space-x-3">
                        <img src="<?php echo htmlspecialchars($c['AVATAR_URL_FIXED']); ?>" 
                             class="w-10 h-10 rounded-full border border-gray-200 object-cover">
                        <div class="flex-1 min-w-0 pr-8">
                            <div class="flex items-baseline justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($c['NAMA_LENGKAP']); ?></span>
                                    <span class="text-gray-500 text-xs">@<?php echo htmlspecialchars($c['USERNAME']); ?> · <?php echo htmlspecialchars($c['WAKTU_KOMEN']); ?></span>
                                </div>
                            </div>
                            
                            <p class="text-gray-800 text-sm mt-1 whitespace-pre-wrap break-words"><?php echo htmlspecialchars($c['ISI_KOMEN']); ?></p>
                            
                            <button onclick="toggleReplyForm(<?php echo $c['COMMENT_ID']; ?>)" 
                                    class="mt-2 text-gray-500 hover:text-blue-600 text-xs font-medium flex items-center space-x-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                <span>Balas</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- FORM REPLY (Hidden by default) -->
                <div id="reply-form-<?php echo $c['COMMENT_ID']; ?>" class="hidden pl-16 pr-4 pb-4">
                    <form onsubmit="submitReply(event, <?php echo $c['COMMENT_ID']; ?>)" class="flex items-start space-x-3">
                        <input type="hidden" name="post_id" value="<?php echo $post['POST_ID']; ?>">
                        <input type="hidden" name="parent_comment_id" value="<?php echo $c['COMMENT_ID']; ?>">
                        
                        <!-- FIX: Menggunakan variabel $myAvatar juga disini -->
                        <img src="<?php echo htmlspecialchars($myAvatar); ?>" 
                             class="w-8 h-8 rounded-full border border-gray-200 object-cover bg-white">
                             
                        <div class="flex-1">
                            <textarea name="isi_komen" 
                                      class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" 
                                      rows="1" 
                                      placeholder="Balas @<?php echo htmlspecialchars($c['USERNAME']); ?>..."></textarea>
                            <div class="flex justify-end mt-2 space-x-2">
                                <button type="button" onclick="toggleReplyForm(<?php echo $c['COMMENT_ID']; ?>)" 
                                        class="text-xs px-3 py-1.5 text-gray-600 hover:bg-gray-100 rounded-md">Batal</button>
                                <button type="submit" 
                                        class="text-xs px-4 py-1.5 bg-blue-600 text-white rounded-full hover:bg-blue-700 font-medium">Kirim</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- REPLIES LIST -->
                <?php if (!empty($c['REPLIES'])): ?>
                    <div class="pl-16 pr-4 pb-2 space-y-3">
                        <?php foreach ($c['REPLIES'] as $r): ?>
                        <div class="relative group pl-3 border-l-2 border-gray-100">
                            <?php if ($r['USER_ID'] == $_SESSION['user_id']): ?>
                                <button onclick="deleteComment(<?php echo $r['COMMENT_ID']; ?>)" 
                                        class="absolute top-0 right-0 p-1 rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            <?php endif; ?>

                            <div class="flex items-start space-x-2">
                                <img src="<?php echo htmlspecialchars($r['AVATAR_URL_FIXED']); ?>" class="w-8 h-8 rounded-full border border-gray-100 object-cover">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-gray-900 text-xs"><?php echo htmlspecialchars($r['NAMA_LENGKAP']); ?></span>
                                        <span class="text-gray-400 text-xs"><?php echo htmlspecialchars($r['WAKTU_KOMEN']); ?></span>
                                    </div>
                                    <p class="text-gray-700 text-xs mt-0.5 whitespace-pre-wrap"><?php echo htmlspecialchars($r['ISI_KOMEN']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php else: ?>
        <div class="p-10 text-center">
            <h3 class="text-lg font-bold text-gray-700">Postingan Tidak Ditemukan</h3>
            <p class="text-gray-500">Mungkin postingan ini telah dihapus oleh pemiliknya.</p>
            <a href="index.php?page=dashboard" class="mt-4 inline-block text-blue-600 hover:underline">Kembali ke Beranda</a>
        </div>
    <?php endif; ?>
</main>

<script>
// --- Helper Fetch Wrapper ---
async function fetchAPI(url, formData) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error("Raw Server Response:", text);
            throw new Error("Respon server bukan JSON valid.");
        }
    } catch (error) {
        throw error;
    }
}

// --- Delete Post ---
function deletePost(postId) {
    if (!confirm('Yakin ingin menghapus postingan ini?')) return;
    const formData = new FormData();
    formData.append('post_id', postId);
    
    fetch('index.php?page=delete-post', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            window.location.href = 'index.php?page=dashboard'; 
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => { console.error('Error:', error); alert('Terjadi kesalahan jaringan'); });
}

// --- Delete Comment ---
function deleteComment(commentId) {
    if(!confirm('Hapus komentar ini?')) return;
    const fd = new FormData();
    fd.append('comment_id', commentId);
    
    fetchAPI('index.php?page=post-api&method=deleteComment', fd)
        .then(data => {
            if(data.status === 'success') location.reload();
            else alert('Gagal: ' + data.message);
        })
        .catch(err => alert('Terjadi kesalahan: ' + err.message));
}

// --- Submit Komentar Utama ---
document.addEventListener('DOMContentLoaded', () => {
    const mainForm = document.getElementById('comment-form');
    if(mainForm) {
        mainForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-reply');
            const txt = this.querySelector('textarea');
            if(!txt.value.trim()) return;
            
            btn.disabled = true;
            btn.innerText = '...';
            
            fetchAPI('index.php?page=post-api&method=addComment', new FormData(this))
                .then(data => {
                    if(data.status === 'success') location.reload();
                    else alert(data.message);
                })
                .catch(err => alert(err.message))
                .finally(() => {
                    btn.disabled = false;
                    btn.innerText = 'Balas';
                });
        });
    }
});

// --- Submit Reply ---
function submitReply(event, parentId) {
    event.preventDefault();
    const form = event.target;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    
    fetchAPI('index.php?page=post-api&method=addComment', new FormData(form))
        .then(data => {
            if(data.status === 'success') location.reload();
            else alert(data.message);
        })
        .catch(err => alert(err.message))
        .finally(() => btn.disabled = false);
}

// --- Toggle UI ---
function toggleReplyForm(id) {
    const el = document.getElementById('reply-form-' + id);
    if(el) {
        el.classList.toggle('hidden');
        if(!el.classList.contains('hidden')) el.querySelector('textarea').focus();
    }
}

function openImageModal(src) {
    const div = document.createElement('div');
    div.className = "fixed inset-0 bg-black/90 z-[9999] flex items-center justify-center p-4 cursor-pointer animate-fade-in";
    div.onclick = () => div.remove();
    div.innerHTML = `<img src="${src}" class="max-w-full max-h-full rounded shadow-2xl transition-transform transform scale-95 hover:scale-100">`;
    document.body.appendChild(div);
}
</script>

<?php require __DIR__ . '/partials/sidebar_kanan.php'; ?>