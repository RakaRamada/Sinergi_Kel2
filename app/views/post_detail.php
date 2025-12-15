<?php 
    // Logika Penentuan Avatar (FIX DOUBLE PATH)
    $sess_avatar = $_SESSION['avatar_url'] ?? '';
    
    if (empty($sess_avatar)) {
        $myAvatar = '/Sinergi/public/assets/images/user.png';
    } elseif (strpos($sess_avatar, '/') !== false) {
        $myAvatar = $sess_avatar;
    } else {
        $myAvatar = '/Sinergi/public/uploads/avatars/' . $sess_avatar;
    }
?>

<main class="col-span-6 border-r border-gray-200 min-h-screen pb-20 bg-white">
    <div
        class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/95 backdrop-blur-sm z-20">
        <button onclick="history.back()"
            class="p-2 rounded-full hover:bg-gray-100 transition-colors cursor-pointer group">
            <img src="/Sinergi/public/assets/icons/arrow-left.svg" alt="Back"
                class="w-6 h-6 group-hover:-translate-x-1 transition-transform">
        </button>
        <h2 class="text-xl font-bold text-gray-900">Postingan</h2>
    </div>

    <?php if (!empty($post)): ?>
    <div class="animate-fade-in">
        <div class="p-4 border-b border-gray-200 relative">

            <?php if ($post['USER_ID'] == $_SESSION['user_id']): ?>
            <button onclick="deletePost(<?php echo $post['POST_ID']; ?>)"
                class="absolute top-4 right-4 group p-2 rounded-full hover:bg-red-50 transition-all cursor-pointer z-10"
                title="Hapus Postingan">
                <svg class="w-5 h-5 text-gray-400 group-hover:text-red-500 transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                    </path>
                </svg>
            </button>
            <?php endif; ?>

            <div class="flex items-center mb-4">
                <img src="<?php echo htmlspecialchars($post['AVATAR_URL_FIXED']); ?>"
                    class="w-12 h-12 rounded-full mr-3 object-cover border border-gray-100 shadow-sm cursor-pointer hover:opacity-90 transition"
                    onclick="window.location.href='index.php?page=profile&id=<?= $post['USER_ID'] ?>'">

                <div>
                    <div class="flex items-center gap-2">
                        <a href="index.php?page=profile&id=<?= $post['USER_ID'] ?>"
                            class="font-bold text-gray-900 text-lg leading-tight hover:underline">
                            @<?php echo htmlspecialchars($post['USERNAME']); ?>
                        </a>

                        <?php if (!empty($post['ROLE_NAME'])): ?>
                        <span
                            class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full font-medium border border-gray-200">
                            <?= htmlspecialchars($post['ROLE_NAME']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="text-gray-800 text-[15px] mb-4 whitespace-normal break-words leading-relaxed">
                <?php echo nl2br(htmlspecialchars(trim($post['KONTEN'] ?? ''))); ?>
            </div>

            <?php if (!empty($post['POST_IMAGE'])): ?>
            <?php 
                    $raw_images = explode(',', $post['POST_IMAGE']);
                    $images = array_filter($raw_images, function($value) { return !empty(trim($value)); });
                    $total_images = count($images);
                ?>

            <?php if ($total_images === 1): ?>
            <?php $oneImg = reset($images); ?>
            <div class="mb-6 rounded-xl overflow-hidden border border-gray-100 shadow-sm bg-gray-50">
                <img src="<?= htmlspecialchars(trim($oneImg)) ?>"
                    onclick="openImageModal('<?= htmlspecialchars(trim($oneImg)) ?>')"
                    class="w-full h-auto max-h-[600px] object-contain cursor-zoom-in hover:brightness-95 transition-all mx-auto">
            </div>

            <?php elseif ($total_images > 1): ?>
            <div class="mb-6 relative group rounded-xl overflow-hidden border border-gray-200 bg-black"
                id="carousel-detail">
                <div class="carousel-track flex overflow-x-auto snap-x snap-mandatory scrollbar-hide"
                    style="scrollbar-width: none; -ms-overflow-style: none;" onscroll="updateDetailDots()">
                    <?php foreach($images as $idx => $img): ?>
                    <div
                        class="snap-center shrink-0 w-full flex justify-center items-center bg-black aspect-[4/3] sm:aspect-auto sm:h-[500px]">
                        <img src="<?= htmlspecialchars(trim($img)) ?>"
                            onclick="openImageModal('<?= htmlspecialchars(trim($img)) ?>')"
                            class="max-h-full max-w-full object-contain cursor-zoom-in">
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 z-10 pointer-events-none">
                    <?php for($i=0; $i<$total_images; $i++): ?>
                    <div class="dot-indicator w-2 h-2 rounded-full bg-white/50 transition-all <?= $i===0 ? 'bg-white scale-125' : '' ?>"
                        data-index="<?= $i ?>"></div>
                    <?php endfor; ?>
                </div>

                <button id="btn-prev-detail" onclick="scrollDetail(-1)"
                    class="absolute left-3 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/40 text-white p-2 rounded-full backdrop-blur-md transition-all hidden cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button id="btn-next-detail" onclick="scrollDetail(1)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/40 text-white p-2 rounded-full backdrop-blur-md transition-all cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <div class="text-gray-500 text-sm mb-4 border-b border-gray-100 pb-4">
                <?php echo htmlspecialchars($post['WAKTU_POSTING']); ?>
            </div>
            <div class="flex items-center space-x-6 text-gray-500 text-sm font-medium">
                <span class="flex items-center space-x-1"><b
                        class="text-gray-900"><?php echo $post['TOTAL_LIKES']; ?></b> <span>Suka</span></span>
                <span class="flex items-center space-x-1"><b
                        class="text-gray-900"><?php echo $post['TOTAL_COMMENTS']; ?></b> <span>Balasan</span></span>
            </div>
        </div>
    </div>

    <div class="p-4 border-b border-gray-200 bg-gray-50 sticky bottom-0 z-10">
        <form id="comment-form" class="flex items-start space-x-3">
            <input type="hidden" name="post_id" value="<?php echo $post['POST_ID']; ?>">
            <img src="<?php echo htmlspecialchars($myAvatar); ?>"
                class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm bg-white">
            <div class="flex-1">
                <textarea name="isi_komen"
                    class="w-full border border-gray-300 rounded-lg p-3 text-gray-800 focus:ring-2 focus:ring-black focus:border-transparent resize-none bg-white min-h-[50px] auto-expand"
                    rows="2" placeholder="Kirim balasan..."></textarea>
                <div class="flex justify-end mt-2">
                    <button type="submit" id="btn-reply"
                        class="bg-black text-white font-bold py-2 px-6 rounded-full hover:bg-gray-800 transition-all shadow-sm cursor-pointer">Balas</button>
                </div>
            </div>
        </form>
    </div>

    <div id="comment-list" class="pb-10">
        <?php if (!empty($comments)): ?>
        <?php foreach ($comments as $c): ?>
        <div class="border-b border-gray-100 hover:bg-gray-50/50 p-4 transition-colors">
            <div class="p-2 relative group">
                <?php if ($c['USER_ID'] == $_SESSION['user_id']): ?>
                <button onclick="deleteComment(<?php echo $c['COMMENT_ID']; ?>)"
                    class="absolute top-0 right-0 p-1.5 rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all cursor-pointer"
                    title="Hapus Komentar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                </button>
                <?php endif; ?>

                <div class="flex items-start space-x-3">
                    <img src="<?php echo htmlspecialchars($c['AVATAR_URL_FIXED']); ?>"
                        class="w-10 h-10 rounded-full border border-gray-200 object-cover cursor-pointer"
                        onclick="window.location.href='index.php?page=profile&id=<?= $c['USER_ID'] ?>'">
                    <div class="flex-1 min-w-0 pr-8">
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-gray-900 text-sm cursor-pointer hover:underline"
                                onclick="window.location.href='index.php?page=profile&id=<?= $c['USER_ID'] ?>'"><?php echo htmlspecialchars($c['NAMA_LENGKAP']); ?></span>
                            <span class="text-gray-500 text-xs">@<?php echo htmlspecialchars($c['USERNAME']); ?> ·
                                <?php echo htmlspecialchars($c['WAKTU_KOMEN']); ?></span>
                        </div>
                        <div class="text-gray-800 text-sm mt-1 whitespace-normal break-words leading-relaxed">
                            <?php echo nl2br(htmlspecialchars(trim($c['ISI_KOMEN']))); ?>
                        </div>
                        <button onclick="toggleReplyForm(<?php echo $c['COMMENT_ID']; ?>)"
                            class="mt-2 text-gray-500 hover:text-gray-600 text-xs font-medium flex items-center space-x-1 transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            <span>Balas</span>
                        </button>
                    </div>
                </div>
            </div>

            <div id="reply-form-<?php echo $c['COMMENT_ID']; ?>" class="hidden pl-16 pr-4 pb-4 animate-fade-in">
                <form onsubmit="submitReply(event, <?php echo $c['COMMENT_ID']; ?>)" class="flex items-start space-x-3">
                    <input type="hidden" name="post_id" value="<?php echo $post['POST_ID']; ?>">
                    <input type="hidden" name="parent_comment_id" value="<?php echo $c['COMMENT_ID']; ?>">
                    <img src="<?php echo htmlspecialchars($myAvatar); ?>"
                        class="w-8 h-8 rounded-full border border-gray-200 object-cover bg-white">
                    <div class="flex-1">
                        <textarea name="isi_komen"
                            class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-black focus:border-transparent resize-none auto-expand min-h-[60px]"
                            rows="2" placeholder="Balas..."></textarea>
                        <div class="flex justify-end mt-2 space-x-2">
                            <button type="button" onclick="toggleReplyForm(<?php echo $c['COMMENT_ID']; ?>)"
                                class="text-xs px-3 py-1.5 text-gray-600 hover:bg-gray-100 rounded-md cursor-pointer transition">Batal</button>
                            <button type="submit"
                                class="text-xs px-4 py-1.5 bg-black text-white rounded-full hover:bg-gray-800 font-medium cursor-pointer transition shadow-sm">Kirim</button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (!empty($c['REPLIES'])): ?>
            <div class="pl-16 pr-4 pb-2 space-y-3">
                <?php foreach ($c['REPLIES'] as $r): ?>
                <div
                    class="relative group pl-3 border-l-2 border-gray-100 hover:bg-gray-50/50 p-2 rounded-r-lg transition-colors">
                    <?php if ($r['USER_ID'] == $_SESSION['user_id']): ?>
                    <button onclick="deleteComment(<?php echo $r['COMMENT_ID']; ?>)"
                        class="absolute top-2 right-2 p-1 rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all cursor-pointer"
                        title="Hapus Balasan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </button>
                    <?php endif; ?>

                    <div class="flex items-start space-x-2">
                        <img src="<?php echo htmlspecialchars($r['AVATAR_URL_FIXED']); ?>"
                            class="w-8 h-8 rounded-full border border-gray-100 object-cover cursor-pointer"
                            onclick="window.location.href='index.php?page=profile&id=<?= $r['USER_ID'] ?>'">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-gray-900 text-xs cursor-pointer hover:underline"
                                    onclick="window.location.href='index.php?page=profile&id=<?= $r['USER_ID'] ?>'"><?php echo htmlspecialchars($r['NAMA_LENGKAP']); ?></span>
                                <span
                                    class="text-gray-400 text-xs"><?php echo htmlspecialchars($r['WAKTU_KOMEN']); ?></span>
                            </div>
                            <div class="text-gray-700 text-xs mt-0.5 whitespace-normal break-words leading-relaxed">
                                <?php echo nl2br(htmlspecialchars(trim($r['ISI_KOMEN']))); ?>
                            </div>
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
    <?php endif; ?>
</main>

<script>
// --- UTILITY: ENTER TO SUBMIT ---
function handleEnterSubmit(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        const form = e.target.closest('form');
        if (form) form.dispatchEvent(new Event('submit', {
            cancelable: true,
            bubbles: true
        }));
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Textarea Utama
    const mainTx = document.querySelector('textarea[name="isi_komen"]');
    if (mainTx) {
        mainTx.addEventListener('keydown', handleEnterSubmit);
        mainTx.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }

    // 2. Logic Form Submit Utama
    const mf = document.getElementById('comment-form');
    if (mf) {
        mf.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-reply');
            const txt = this.querySelector('textarea');
            if (!txt.value.trim()) return;
            btn.disabled = true;
            btn.innerText = '...';

            fetchAPI('index.php?page=post-api&method=addComment', new FormData(this))
                .then(d => {
                    if (d.status === 'success') location.reload();
                    else showModernAlert('Gagal', d.message);
                })
                .catch(e => showModernAlert('Error', e.message))
                .finally(() => {
                    btn.disabled = false;
                    btn.innerText = 'Balas';
                });
        });
    }
});

function toggleReplyForm(id) {
    const el = document.getElementById('reply-form-' + id);
    if (el) {
        el.classList.toggle('hidden');
        if (!el.classList.contains('hidden')) {
            const tx = el.querySelector('textarea');
            tx.focus();
            if (!tx.dataset.enterAttached) {
                tx.addEventListener('keydown', handleEnterSubmit);
                tx.dataset.enterAttached = "true";
            }
        }
    }
}

function submitReply(e, pid) {
    e.preventDefault();
    const fm = e.target;
    const btn = fm.querySelector('button[type="submit"]');
    btn.disabled = true;
    fetchAPI('index.php?page=post-api&method=addComment', new FormData(fm))
        .then(d => {
            if (d.status === 'success') location.reload();
            else showModernAlert('Gagal', d.message);
        })
        .catch(err => showModernAlert('Error', err.message))
        .finally(() => btn.disabled = false);
}

// --- API FETCH HELPER ---
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
            throw new Error("Server Error: " + text);
        }
    } catch (error) {
        throw error;
    }
}

// ==========================================
// 1. MODERN DELETE LOGIC (DENGAN MODAL)
// ==========================================

function deletePost(postId) {
    showConfirmModal(
        'Hapus Postingan?',
        'Apakah Anda yakin ingin menghapus postingan ini? Tindakan ini tidak dapat dibatalkan.',
        () => {
            const fd = new FormData();
            fd.append('post_id', postId);
            fetch('index.php?page=post-api&method=deletePost', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'success') window.location.href = 'index.php?page=dashboard';
                    else showModernAlert('Gagal', d.message);
                })
                .catch(e => showModernAlert('Error', 'Jaringan Error'));
        }
    );
}

function deleteComment(cid) {
    showConfirmModal(
        'Hapus Komentar?',
        'Komentar yang dihapus tidak dapat dikembalikan lagi.',
        () => {
            const fd = new FormData();
            fd.append('comment_id', cid);
            fetchAPI('index.php?page=post-api&method=deleteComment', fd)
                .then(d => {
                    if (d.status === 'success') location.reload();
                    else showModernAlert('Gagal', d.message);
                })
                .catch(e => showModernAlert('Error', e.message));
        }
    );
}

// ==========================================
// 2. MODAL SYSTEM (ALERT & CONFIRM)
// ==========================================

// Alert Modern (Sukses/Gagal)
function showModernAlert(title, message) {
    const old = document.getElementById('modern-alert');
    if (old) old.remove();

    const isError = title.toLowerCase().includes('gagal') || title.toLowerCase().includes('error');
    const colorClass = isError ? 'text-red-500 bg-red-50' : 'text-green-500 bg-green-50';
    const icon = isError ?
        `<svg class="w-8 h-8 ${colorClass} rounded-full p-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>` :
        `<svg class="w-8 h-8 ${colorClass} rounded-full p-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`;

    const el = document.createElement('div');
    el.id = 'modern-alert';
    el.className =
        'fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm animate-fade-in';
    el.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center transform transition-all scale-100 animate-bounce-in">
            <div class="mb-4 flex justify-center">${icon}</div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">${title}</h3>
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">${message}</p>
            <button onclick="document.getElementById('modern-alert').remove()" class="w-full py-2.5 bg-black text-white rounded-xl font-medium hover:bg-gray-800 transition shadow-lg cursor-pointer">OK</button>
        </div>`;
    document.body.appendChild(el);
}

// Confirm Modal (Ya/Batal)
function showConfirmModal(title, message, onConfirm) {
    const old = document.getElementById('modern-confirm');
    if (old) old.remove();

    const el = document.createElement('div');
    el.id = 'modern-confirm';
    el.className =
        'fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm animate-fade-in';

    el.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center transform transition-all scale-100 animate-bounce-in">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">${title}</h3>
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">${message}</p>
            <div class="flex space-x-3">
                <button id="btn-cancel" class="flex-1 py-2.5 border border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-gray-50 transition cursor-pointer">Batal</button>
                <button id="btn-confirm" class="flex-1 py-2.5 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition shadow-md cursor-pointer">Hapus</button>
            </div>
        </div>`;

    document.body.appendChild(el);

    document.getElementById('btn-cancel').onclick = () => el.remove();
    document.getElementById('btn-confirm').onclick = () => {
        el.remove();
        onConfirm();
    };

    // Klik background tutup
    el.onclick = (e) => {
        if (e.target === el) el.remove();
    };
}

// Modal Gambar Zoom
function openImageModal(src) {
    const d = document.createElement('div');
    d.className =
        "fixed inset-0 bg-black/95 z-[9999] flex items-center justify-center p-4 cursor-pointer animate-fade-in";
    d.onclick = () => d.remove();
    d.innerHTML = `<img src="${src}" class="max-w-full max-h-full rounded-lg shadow-2xl animate-zoom-in">`;
    document.body.appendChild(d);
}

// Carousel Logic
function scrollDetail(direction) {
    const container = document.getElementById('carousel-detail');
    if (!container) return;
    const track = container.querySelector('.carousel-track');
    track.scrollBy({
        left: direction * track.clientWidth,
        behavior: 'smooth'
    });
}

function updateDetailDots() {
    const container = document.getElementById('carousel-detail');
    if (!container) return;
    const track = container.querySelector('.carousel-track');
    const dots = container.querySelectorAll('.dot-indicator');
    const prevBtn = document.getElementById('btn-prev-detail');
    const nextBtn = document.getElementById('btn-next-detail');

    if (!track) return;
    const index = Math.round(track.scrollLeft / track.clientWidth);
    dots.forEach((dot, idx) => {
        if (idx === index) {
            dot.classList.add('bg-white', 'scale-125');
            dot.classList.remove('bg-white/50');
        } else {
            dot.classList.remove('bg-white', 'scale-125');
            dot.classList.add('bg-white/50');
        }
    });

    const atStart = track.scrollLeft <= 5;
    const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 5;
    if (prevBtn) prevBtn.style.display = atStart ? 'none' : 'block';
    if (nextBtn) nextBtn.style.display = atEnd ? 'none' : 'block';
}
</script>

<style>
/* Animasi Tambahan */
@keyframes fade-in {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.animate-fade-in {
    animation: fade-in 0.2s ease-out forwards;
}

@keyframes bounceIn {
    0% {
        opacity: 0;
        transform: scale(0.95);
    }

    100% {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-bounce-in {
    animation: bounceIn 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

@keyframes zoom-in {
    from {
        transform: scale(0.95);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-zoom-in {
    animation: zoom-in 0.2s ease-out forwards;
}
</style>

<?php require __DIR__ . '/partials/sidebar_kanan.php'; ?>