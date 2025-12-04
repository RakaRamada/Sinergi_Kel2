<?php
// File: app/views/partials/postingan.php
// VERSI FINAL: DASHBOARD FEED DENGAN SVG LIKE (MERAH PEKAT)
?>

<div id="post-feed-container" class="space-y-4"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("Sistem Postingan Siap (SVG Mode)!");

    // ==================================================
    // 1. INISIALISASI VARIABEL
    // ==================================================
    const postFeedContainer = document.getElementById('post-feed-container');
    const createPostForm = document.getElementById('create-post-form');
    const submitButton = document.getElementById('submit-post-button');

    // Variabel Gambar Preview (Untuk Form Create Post)
    const imageInput = document.getElementById('post-image-input');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const imagePreview = document.getElementById('image-preview');
    const removeImageBtn = document.getElementById('remove-image-btn');

    // ==================================================
    // 2. LOGIKA PREVIEW GAMBAR (FORM CREATE)
    // ==================================================
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    alert('Mohon pilih file gambar yang valid.');
                    this.value = '';
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar. Maksimal 5MB.');
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (imagePreview) imagePreview.setAttribute('src', e.target.result);
                    if (imagePreviewContainer) imagePreviewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    }

    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            if (imageInput) imageInput.value = '';
            if (imagePreviewContainer) imagePreviewContainer.classList.add('hidden');
            if (imagePreview) imagePreview.src = '#';
        });
    }

    // ==================================================
    // 3. LOGIKA SUBMIT POSTINGAN BARU
    // ==================================================
    if (createPostForm) {
        createPostForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const kontenInput = this.querySelector('textarea[name="konten"]');
            const konten = kontenInput ? kontenInput.value.trim() : '';
            const hasImage = imageInput && imageInput.files.length > 0;

            if (!konten && !hasImage) {
                alert("Tulis sesuatu atau pilih gambar untuk memposting.");
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Memposting...';
            }

            const formData = new FormData(this);

            fetch('index.php?page=post-api&method=createPost', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        createPostForm.reset();
                        if (imagePreviewContainer) imagePreviewContainer.classList.add('hidden');
                        if (imagePreview) imagePreview.src = '#';
                        loadPosts(); // Refresh Feed Otomatis
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    } else {
                        alert('Gagal: ' + (data.message || 'Terjadi kesalahan server'));
                    }
                })
                .catch(error => {
                    console.error('Error Upload:', error);
                    alert('Terjadi kesalahan koneksi.');
                })
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Posting';
                    }
                });
        });
    }

    // ==================================================
    // 4. FUNGSI LOAD POSTINGAN (FEED) - MENGGUNAKAN SVG
    // ==================================================
    function loadPosts() {
        if (!postFeedContainer) return;

        postFeedContainer.innerHTML =
            '<div class="p-8 text-center text-gray-500"><div class="animate-pulse">Sedang memuat...</div></div>';

        fetch('index.php?page=post-api&method=getPostings')
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(posts => {
                postFeedContainer.innerHTML = '';

                if (!Array.isArray(posts) || posts.length === 0) {
                    postFeedContainer.innerHTML = `
                        <div class="p-8 text-center text-gray-500 border-b border-gray-200">
                            <p class="mb-2">Belum ada postingan.</p>
                            <p class="text-sm">Jadilah yang pertama memposting sesuatu!</p>
                        </div>`;
                    return;
                }

                posts.forEach(post => {
                    // Render Gambar Postingan
                    const postImageHTML = post.POST_IMAGE ?
                        `<div class="mt-3 mb-1">
                            <img src="${escapeHtml(post.POST_IMAGE)}" 
                                 alt="Post image"
                                 class="rounded-lg w-full border border-gray-200 cursor-pointer hover:opacity-95 transition-opacity object-cover"
                                 onclick="openImageModal('${escapeHtml(post.POST_IMAGE)}')"
                                 style="max-height: 500px;">
                         </div>` : '';

                    const kontenText = post.KONTEN ? escapeHtml(post.KONTEN) : '';

                    // LOGIKA STYLE LIKE (SVG)
                    const isLiked = post.USER_SUDAH_LIKE > 0;
                    // Class Text: Merah vs Abu
                    const likeBtnClass = isLiked ? 'text-red-500' : 'text-gray-500';
                    // Class SVG: Solid vs Outline
                    const svgClass = isLiked ? 'fill-current' : 'fill-none stroke-current';

                    const postHTML = `
                        <div class="bg-white border-b border-gray-200 hover:bg-gray-50/30 transition-colors">
                            <div class="p-4">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 cursor-pointer" onclick="window.location.href='index.php?page=profile&id=${post.USER_ID}'">
                                         <img src="${escapeHtml(post.AVATAR_URL_FIXED)}" 
                                              alt="Avatar" 
                                              class="w-10 h-10 rounded-full object-cover bg-gray-200 border border-gray-100">
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-1 mb-1">
                                            <a href="index.php?page=profile&id=${post.USER_ID}" 
                                               class="font-bold text-gray-900 hover:underline text-base">
                                                ${escapeHtml(post.NAMA_LENGKAP)}
                                            </a>
                                            <span class="text-gray-500 text-sm">@${escapeHtml(post.USERNAME)}</span>
                                            <span class="text-gray-400 text-sm">·</span>
                                            <span class="text-gray-500 text-sm hover:underline cursor-pointer" 
                                                  title="${escapeHtml(post.CREATED_AT_STR || '')}">
                                                ${escapeHtml(post.WAKTU_POSTING)}
                                            </span>
                                        </div>
                                        
                                        <div class="cursor-pointer" onclick="window.location.href='index.php?page=post-detail&id=${post.POST_ID}'">
                                            <p class="text-gray-800 text-[15px] leading-normal break-words whitespace-pre-wrap mb-2">${kontenText}</p>
                                        </div>
                                        
                                        ${postImageHTML}

                                        <div class="flex items-center justify-between mt-3 max-w-md text-gray-500">
                                            
                                            <button onclick="handleLike(this, ${post.POST_ID})" 
                                                    class="group flex items-center space-x-2 hover:text-red-500 transition-colors ${likeBtnClass} w-fit">
                                                <div class="p-2 rounded-full group-hover:bg-red-50 transition-colors relative">
                                                    <svg xmlns="http://www.w3.org/2000/svg" 
                                                         class="w-5 h-5 transition-transform duration-200 ${svgClass}" 
                                                         viewBox="0 0 24 24" 
                                                         stroke-width="1.5" 
                                                         stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                                    </svg>
                                                </div>
                                                <span class="like-count text-sm font-medium">${post.LIKE_COUNT || 0}</span>
                                            </button>

                                            <a href="index.php?page=post-detail&id=${post.POST_ID}"
                                                class="group flex items-center space-x-2 hover:text-blue-500 transition-colors w-fit">
                                                <div class="p-2 rounded-full group-hover:bg-blue-50 transition-colors">
                                                    <img src="/Sinergi/public/assets/icons/comment.svg" 
                                                         alt="Komentar" 
                                                         class="w-5 h-5">
                                                </div>
                                                <span class="text-sm font-medium">${post.COMMENT_COUNT || 0}</span>
                                            </a>

                                            <button class="group flex items-center space-x-2 hover:text-green-500 transition-colors w-fit"
                                                    onclick="handleReport(${post.POST_ID})">
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
                    `;
                    postFeedContainer.innerHTML += postHTML;
                });
            })
            .catch(error => {
                console.error('Error Load Posts:', error);
                postFeedContainer.innerHTML = `
                    <div class="p-8 text-center text-red-500 border-b border-gray-200">
                        <p class="mb-2">Gagal memuat postingan.</p>
                        <button onclick="loadPosts()" class="mt-2 px-4 py-2 bg-black text-white rounded-full hover:bg-gray-800">
                            Coba Lagi
                        </button>
                    </div>`;
            });
    }

    // Panggil fungsi saat load
    loadPosts();
    window.loadPosts = loadPosts; // Expose ke global window agar bisa dipanggil ulang

    // Helper Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return text.toString().replace(/[&<>"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            } [m];
        });
    }
});

// ==================================================
// 5. FUNGSI GLOBAL (HANDLE LIKE - SVG MODE)
// ==================================================

function handleLike(btn, postId) {
    const countSpan = btn.querySelector('.like-count');
    const svgIcon = btn.querySelector('svg'); // Target elemen SVG

    let currentCount = parseInt(countSpan.innerText) || 0;
    const isCurrentlyLiked = btn.classList.contains('text-red-500');

    // UI OPTIMISTIC UPDATE (Langsung ubah tampilan sebelum request selesai)
    if (isCurrentlyLiked) {
        // PROSES UNLIKE
        btn.classList.remove('text-red-500');
        btn.classList.add('text-gray-500');

        // Ubah SVG: Hapus fill, tambah stroke (outline)
        svgIcon.classList.remove('fill-current');
        svgIcon.classList.add('fill-none', 'stroke-current');

        countSpan.innerText = Math.max(0, currentCount - 1);
    } else {
        // PROSES LIKE
        btn.classList.remove('text-gray-500');
        btn.classList.add('text-red-500');

        // Ubah SVG: Hapus outline, tambah fill (solid)
        svgIcon.classList.remove('fill-none', 'stroke-current');
        svgIcon.classList.add('fill-current');

        countSpan.innerText = currentCount + 1;

        // Efek "Pop" kecil animasi
        svgIcon.style.transform = "scale(1.2)";
        setTimeout(() => svgIcon.style.transform = "scale(1)", 200);
    }

    // KIRIM REQUEST KE SERVER
    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('index.php?page=post-api&method=toggleLike', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') {
                console.error("Like gagal:", data.message);
                // Revert UI jika gagal
                if (isCurrentlyLiked) {
                    btn.classList.add('text-red-500');
                    svgIcon.classList.remove('fill-none', 'stroke-current');
                    svgIcon.classList.add('fill-current');
                    countSpan.innerText = currentCount;
                } else {
                    btn.classList.remove('text-red-500');
                    svgIcon.classList.add('fill-none', 'stroke-current');
                    svgIcon.classList.remove('fill-current');
                    countSpan.innerText = currentCount;
                }
            } else {
                // Update dengan jumlah pasti dari server
                if (data.new_count !== undefined) countSpan.innerText = data.new_count;
            }
        })
        .catch(err => {
            console.error(err);
        });
}

// Fungsi Report
function handleReport(postId) {
    const reason = prompt("Apa alasan Anda melaporkan postingan ini?");
    if (reason) {
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('reason', reason);

        fetch('index.php?page=post-api&method=addReport', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(d => alert(d.message))
            .catch(e => alert("Gagal lapor"));
    }
}

// Fungsi Modal Gambar
function openImageModal(imageSrc) {
    const oldModal = document.getElementById('image-modal-overlay');
    if (oldModal) oldModal.remove();

    const modal = document.createElement('div');
    modal.id = 'image-modal-overlay';
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background-color: rgba(0, 0, 0, 0.9); z-index: 99999;
        display: flex; align-items: center; justify-content: center;
        padding: 20px; backdrop-filter: blur(5px);
    `;
    modal.onclick = function() {
        modal.remove();
    };

    const img = document.createElement('img');
    img.src = imageSrc;
    img.style.cssText =
        `max-width: 100%; max-height: 90vh; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); object-fit: contain;`;
    img.onclick = function(e) {
        e.stopPropagation();
    };

    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText =
        `position: absolute; top: 20px; right: 30px; color: white; font-size: 40px; font-weight: bold; cursor: pointer; background: none; border: none; z-index: 100000;`;
    closeBtn.onclick = function() {
        modal.remove();
    };

    modal.appendChild(closeBtn);
    modal.appendChild(img);
    document.body.appendChild(modal);
}
</script>

<style>
/* Animasi Loading */
@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>