<main class="col-span-6 border-r border-gray-200 min-h-screen pb-20">
    <div class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <a href="index.php?page=dashboard" class="p-2 rounded-full hover:bg-gray-200 transition-colors">
            <img src="/Sinergi/public/assets/icons/arrow-left.svg" alt="Back" class="w-6 h-6">
        </a>
        <h2 class="text-xl font-bold">Postingan</h2>
    </div>

    <?php if (!empty($post)): ?>
    <div>
        <div class="p-4 border-b border-gray-200 relative" pr-20>
        <?php if ($post['USER_ID'] == $_SESSION['user_id']): ?>
            <button onclick="deletePost(<?php echo $post['POST_ID']; ?>)" 
                    class="absolute top-4 right-4 bg-white hover:bg-red-100 p-2 rounded-full shadow-sm border border-gray-200 transition">
                <img src="/Sinergi/public/assets/icons/delete.svg"
                    class="w-6 h-6 opacity-60 hover:opacity-100"
                    alt="Hapus">
            </button>
        <?php endif; ?>

            <div class="flex items-center mb-3 pr-16"> 
                <img src="<?php echo htmlspecialchars($post['AVATAR_URL_FIXED']); ?>" class="w-12 h-12 rounded-full mr-4 object-cover border border-gray-200">
                <div>
                    <p class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($post['NAMA_LENGKAP']); ?></p>
                    <p class="text-sm text-gray-500">@<?php echo htmlspecialchars($post['USERNAME']); ?></p>
                </div>
            </div>
            
            <p class="text-gray-800 text-xl mb-4 whitespace-pre-wrap leading-relaxed"><?php echo nl2br(htmlspecialchars($post['KONTEN'] ?? '')); ?></p>
            
            <?php if (!empty($post['POST_IMAGE'])): ?>
            <img src="<?php echo htmlspecialchars($post['POST_IMAGE']); ?>" 
                 onclick="openImageModal('<?php echo htmlspecialchars($post['POST_IMAGE'], ENT_QUOTES); ?>')" 
                 class="rounded-xl w-full border border-gray-200 cursor-pointer mb-4 max-h-[600px] object-cover hover:opacity-95 transition">
            <?php endif; ?>
            
            <div class="text-gray-500 text-sm mb-4"><?php echo htmlspecialchars($post['WAKTU_POSTING']); ?></div>
            
            <div class="border-t border-gray-200 py-3 flex items-center space-x-6 text-gray-500">
                <span><b class="text-black"><?php echo $post['TOTAL_LIKES']; ?></b> Suka</span>
                <span><b class="text-black" id="count-komen"><?php echo $post['TOTAL_COMMENTS']; ?></b> Balasan</span>
            </div>
        </div>
    </div>

    <div class="p-4 border-b border-gray-200 bg-gray-50/50">
        <form id="comment-form" class="flex items-start space-x-3"> 
            <input type="hidden" name="post_id" value="<?php echo $post['POST_ID']; ?>">
            <img src="<?php echo htmlspecialchars($_SESSION['avatar_url'] ?? '/Sinergi/public/assets/images/user.png'); ?>" 
                 class="w-10 h-10 rounded-full object-cover bg-gray-300 border border-gray-200">
            <div class="flex-1">
                <textarea name="isi_komen" 
                          class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-black focus:border-transparent resize-none" 
                          rows="2" 
                          placeholder="Kirim balasan Anda..."
                          maxlength="500"></textarea>
                <div class="flex justify-end mt-2">
                    <button type="submit" 
                            id="btn-reply" 
                            class="bg-black text-white font-bold py-2 px-6 rounded-full hover:bg-gray-800 text-sm transition-colors">
                        Balas
                    </button>
                </div>
            </div>
        </form>
    </div>

<div id="comment-list">
    <?php if (empty($comments)): ?>
        <div id="no-comment" class="p-8 text-center text-gray-500">Belum ada balasan.</div>
    <?php else: ?>
        <?php foreach ($comments as $c): ?>
        <div id="comment-<?php echo $c['COMMENT_ID']; ?>" class="border-b border-gray-200">
            <!-- KOMENTAR UTAMA -->
            <div class="p-4 hover:bg-gray-50 relative transition-colors pr-16">
                <?php if ($c['USER_ID'] == $_SESSION['user_id']): ?>
                    <button onclick="deleteComment(<?php echo $c['COMMENT_ID']; ?>)" 
                            class="absolute top-3 right-3 bg-white hover:bg-red-100 p-2 rounded-full shadow-sm border border-gray-200 transition">
                        <img src="/Sinergi/public/assets/icons/delete.svg" class="w-5 h-5 opacity-60 hover:opacity-100">
                    </button>
                <?php endif; ?>

                <div class="flex items-start space-x-3 pr-8">
                    <img src="<?php echo htmlspecialchars($c['AVATAR_URL_FIXED']); ?>" 
                         class="w-10 h-10 rounded-full border border-gray-200 object-cover">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <p class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($c['NAMA_LENGKAP']); ?></p>
                            <span class="text-gray-500 text-xs">@<?php echo htmlspecialchars($c['USERNAME']); ?> · <?php echo htmlspecialchars($c['WAKTU_KOMEN']); ?></span>
                        </div>
                        <p class="text-gray-800 text-sm mt-1 leading-relaxed whitespace-pre-wrap break-words"><?php echo nl2br(htmlspecialchars($c['ISI_KOMEN'])); ?></p>
                        
                        <!-- Tombol Balas -->
                        <button onclick="toggleReplyForm(<?php echo $c['COMMENT_ID']; ?>, '<?php echo htmlspecialchars($c['USERNAME']); ?>')" 
                                class="text-blue-600 hover:text-blue-800 text-xs font-medium mt-2 flex items-center space-x-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                            <span>Balas</span>
                            <?php if ($c['REPLY_COUNT'] > 0): ?>
                                <span class="text-gray-500">(<?php echo $c['REPLY_COUNT']; ?>)</span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- FORM BALASAN (hidden) -->
            <div id="reply-form-<?php echo $c['COMMENT_ID']; ?>" class="hidden pl-14 pr-4 pb-3 bg-gray-50">
                <form onsubmit="submitReply(event, <?php echo $c['COMMENT_ID']; ?>)" class="flex items-start space-x-2">
                    <input type="hidden" name="post_id" value="<?php echo $post['POST_ID']; ?>">
                    <input type="hidden" name="parent_comment_id" value="<?php echo $c['COMMENT_ID']; ?>">
                    <img src="<?php echo htmlspecialchars($_SESSION['avatar_url'] ?? '/Sinergi/public/assets/images/user.png'); ?>" 
                         class="w-8 h-8 rounded-full object-cover bg-gray-300 border border-gray-200">
                    <div class="flex-1">
                        <textarea name="isi_komen" 
                                  class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" 
                                  rows="2" 
                                  placeholder="Balas @<?php echo htmlspecialchars($c['USERNAME']); ?>..."
                                  maxlength="500"></textarea>
                        <div class="flex justify-end mt-1 space-x-2">
                            <button type="button" 
                                    onclick="toggleReplyForm(<?php echo $c['COMMENT_ID']; ?>)"
                                    class="text-gray-600 hover:text-gray-800 text-xs font-medium py-1 px-3 rounded-full">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium py-1 px-4 rounded-full transition">
                                Kirim
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- DAFTAR BALASAN -->
            <?php if (!empty($c['REPLIES'])): ?>
            <div class="pl-14 bg-gray-50/50">
                <?php foreach ($c['REPLIES'] as $r): ?>
                <div id="reply-<?php echo $r['COMMENT_ID']; ?>" class="p-3 border-t border-gray-200 hover:bg-gray-50 relative transition-colors pr-12">
                    <?php if ($r['USER_ID'] == $_SESSION['user_id']): ?>
                        <button onclick="deleteComment(<?php echo $r['COMMENT_ID']; ?>, true)" 
                                class="absolute top-2 right-2 bg-white hover:bg-red-100 p-1.5 rounded-full shadow-sm border border-gray-200 transition">
                            <img src="/Sinergi/public/assets/icons/delete.svg" class="w-4 h-4 opacity-60 hover:opacity-100">
                        </button>
                    <?php endif; ?>

                    <div class="flex items-start space-x-2 pr-8">
                        <img src="<?php echo htmlspecialchars($r['AVATAR_URL_FIXED']); ?>" 
                             class="w-8 h-8 rounded-full border border-gray-200 object-cover">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <p class="font-bold text-gray-900 text-xs"><?php echo htmlspecialchars($r['NAMA_LENGKAP']); ?></p>
                                <span class="text-gray-500 text-xs">@<?php echo htmlspecialchars($r['USERNAME']); ?> · <?php echo htmlspecialchars($r['WAKTU_KOMEN']); ?></span>
                            </div>
                            <p class="text-gray-800 text-xs mt-0.5 leading-relaxed whitespace-pre-wrap break-words"><?php echo nl2br(htmlspecialchars($r['ISI_KOMEN'])); ?></p>
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
        <div class="p-8 text-center text-gray-500">Postingan tidak ditemukan.</div>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('comment-form');
    const list = document.getElementById('comment-list');
    const counter = document.getElementById('count-komen');

    // === KIRIM KOMENTAR ===
    if(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const btn = document.getElementById('btn-reply');
            const txt = this.querySelector('textarea[name="isi_komen"]');
            
            if(!txt.value.trim()) { alert('Komentar kosong!'); return; }

            btn.disabled = true; btn.textContent = 'Mengirim...';

            fetch('/Sinergi/api/tambah_komentar.php', {
                method:'POST', body: new FormData(this)
            })
            .then(r => r.json())
            .then(d => {
                if(d.status === 'success'){
                    form.reset();
                    const noComment = document.getElementById('no-comment');
                    if(noComment) noComment.remove();
                    
                    const html = `
                    <div id="comment-${d.data.comment_id}" class="p-4 border-b bg-blue-50 hover:bg-gray-50 relative transition-colors">
                        <button onclick="deleteComment(${d.data.comment_id})" 
                                class="absolute top-3 right-3 text-gray-400 hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition-colors z-10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        <div class="flex items-start space-x-3 pr-8">
                            <img src="${d.data.avatar_url}" class="w-10 h-10 rounded-full border border-gray-200 object-cover">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <p class="font-bold text-sm text-gray-900">${d.data.nama_lengkap}</p>
                                    <span class="text-gray-500 text-xs">@${d.data.username} · ${d.data.waktu}</span>
                                </div>
                                <p class="text-gray-800 text-sm mt-1 leading-relaxed whitespace-pre-wrap break-words">${d.data.isi_komen}</p>
                            </div>
                        </div>
                    </div>`;
                    list.insertAdjacentHTML('afterbegin', html);
                    if(counter) counter.innerText = parseInt(counter.innerText) + 1;
                } else { alert(d.message); }
            })
            .catch(e => alert('Error: ' + e))
            .finally(() => { btn.disabled = false; btn.textContent = 'Balas'; });
        });
    }
});

// === HAPUS KOMENTAR ===
function deleteComment(id, isReply = false){
    if(!confirm('Hapus komentar ini?')) return;
    const fd = new FormData(); fd.append('comment_id', id);
    fetch('/Sinergi/api/hapus_komentar.php', { method:'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success'){
            if (isReply) {
                const el = document.getElementById('reply-' + id);
                if(el) el.remove();
            } else {
                const el = document.getElementById('comment-' + id);
                if(el) el.remove();
            }
            const c = document.getElementById('count-komen');
            if(c) c.innerText = Math.max(0, parseInt(c.innerText) - 1);
            if(document.querySelectorAll('[id^="comment-"]').length === 0) 
                document.getElementById('comment-list').innerHTML = '<div id="no-comment" class="p-8 text-center text-gray-500">Belum ada balasan.</div>';
        } else { alert(d.message); }
    });
}

// === TOGGLE FORM BALASAN ===
function toggleReplyForm(commentId, username) {
    const form = document.getElementById(`reply-form-${commentId}`);
    
    if (!form) {
        console.error('Form reply tidak ditemukan untuk comment:', commentId);
        return;
    }
    
    const textarea = form.querySelector('textarea');
    
    if (form.classList.contains('hidden')) {
        document.querySelectorAll('[id^="reply-form-"]').forEach(f => f.classList.add('hidden'));
        form.classList.remove('hidden');
        textarea.focus();
    } else {
        form.classList.add('hidden');
        textarea.value = '';
    }
}

// === SUBMIT BALASAN ===
// === SUBMIT BALASAN ===
function submitReply(event, parentCommentId) {
    event.preventDefault();
    
    const form = event.target;
    const textarea = form.querySelector('textarea[name="isi_komen"]');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (!textarea.value.trim()) {
        alert('Balasan tidak boleh kosong!');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Mengirim...';
    
    fetch('/Sinergi/api/tambah_komentar.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(r => r.text()) // Ubah jadi .text() dulu untuk debug
    .then(text => {
        console.log('Response:', text); // Debug response
        try {
            const d = JSON.parse(text);
            if (d.status === 'success') {
                location.reload();
            } else {
                alert(d.message || 'Gagal mengirim balasan');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Kirim';
            }
        } catch (e) {
            console.error('JSON Parse Error:', e);
            console.error('Response text:', text);
            alert('Error parsing response. Check console for details.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Kirim';
        }
    })
    .catch(e => {
        console.error('Fetch Error:', e);
        alert('Error: ' + e);
        submitBtn.disabled = false;
        submitBtn.textContent = 'Kirim';
    });
}

// === DELETE POSTINGAN ===
function deletePost(postId) {
    if(!confirm('Hapus postingan ini secara permanen?')) return;
    const fd = new FormData(); fd.append('post_id', postId);
    fetch('/Sinergi/api/hapus_postingan.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') { window.location.href = 'index.php?page=dashboard'; } 
        else { alert(d.message); }
    });
}

function openImageModal(src) {
    const m = document.createElement('div');
    m.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;display:flex;justify-content:center;align-items:center;';
    m.onclick = () => m.remove();
    const i = document.createElement('img');
    i.src = src;
    i.style.cssText = 'max-width:90%;max-height:90%;border-radius:8px;';
    m.appendChild(i); document.body.appendChild(m);
}
</script>

<?php require __DIR__ . '/partials/sidebar_kanan.php'; ?>