<?php
// File: app/views/partials/postingan.php
?>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("Sistem Postingan Siap!");

    // ==================================================
    // 1. INISIALISASI VARIABEL
    // ==================================================
    const postFeedContainer = document.getElementById('post-feed-container');
    const createPostForm = document.getElementById('create-post-form');
    const submitButton = document.getElementById('submit-post-button');

    // Variabel Gambar Preview
    const imageInput = document.getElementById('post-image-input');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const imagePreview = document.getElementById('image-preview');
    const removeImageBtn = document.getElementById('remove-image-btn');

    // ==================================================
    // 2. LOGIKA PREVIEW GAMBAR
    // ==================================================
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Validasi tipe file gambar
                if (!file.type.startsWith('image/')) {
                    alert('Mohon pilih file gambar yang valid.');
                    this.value = '';
                    return;
                }

                // Validasi ukuran file (max 5MB)
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

    // Tombol Hapus Preview (X)
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            if (imageInput) imageInput.value = '';
            if (imagePreviewContainer) imagePreviewContainer.classList.add('hidden');
            if (imagePreview) imagePreview.src = '#';
        });
    }

    // ==================================================
    // 3. LOGIKA SUBMIT POSTINGAN (UPLOAD AJAX)
    // ==================================================
    if (createPostForm) {
        createPostForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validasi Input
            const kontenInput = this.querySelector('textarea[name="konten"]');
            const konten = kontenInput ? kontenInput.value.trim() : '';
            const hasImage = imageInput && imageInput.files.length > 0;

            if (!konten && !hasImage) {
                alert("Tulis sesuatu atau pilih gambar untuk memposting.");
                return;
            }

            // UI Loading
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Memposting...';
            }

            const formData = new FormData(this);

            fetch('/Sinergi/api/upload_postingan.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Reset Form
                        createPostForm.reset();
                        if (imagePreviewContainer) imagePreviewContainer.classList.add('hidden');
                        if (imagePreview) imagePreview.src = '#';

                        // Reload Feed
                        loadPosts();

                        // Scroll ke atas
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
    // 4. FUNGSI LOAD POSTINGAN (FEED)
    // ==================================================
    function loadPosts() {
        if (!postFeedContainer) return;

        postFeedContainer.innerHTML =
            '<div class="p-8 text-center text-gray-500"><div class="animate-pulse">Sedang memuat...</div></div>';

        fetch('api/ambil_postingan.php')
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

                    // Logic Warna Like (Pakai Filter Merah)
                    const isLiked = post.USER_SUDAH_LIKE > 0;
                    const likeColorClass = isLiked ? 'text-red-500' : 'text-gray-500';
                    const iconFilterClass = isLiked ? 'filter-red' : '';

                    // Escape HTML untuk keamanan
                    const kontenText = post.KONTEN ? escapeHtml(post.KONTEN) : '';

                    // HTML Template
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
                                                    class="group flex items-center space-x-2 hover:text-red-500 transition-colors ${likeColorClass} w-fit">
                                                <div class="p-2 rounded-full group-hover:bg-red-50 transition-colors relative">
                                                    <img src="/Sinergi/public/assets/icons/heart.svg" 
                                                         alt="Like" 
                                                         class="w-5 h-5 icon-transition ${iconFilterClass}">
                                                </div>
                                                <span class="like-count text-sm font-medium">${post.TOTAL_LIKES || 0}</span>
                                            </button>

                                            <a href="index.php?page=post-detail&id=${post.POST_ID}"
                                                class="group flex items-center space-x-2 hover:text-blue-500 transition-colors w-fit">
                                                <div class="p-2 rounded-full group-hover:bg-blue-50 transition-colors">
                                                    <img src="/Sinergi/public/assets/icons/comment.svg" 
                                                         alt="Komentar" 
                                                         class="w-5 h-5">
                                                </div>
                                                <span class="text-sm font-medium">${post.TOTAL_COMMENTS || 0}</span>
                                            </a>

                                            <button class="group flex items-center space-x-2 hover:text-green-500 transition-colors w-fit"
                                                    onclick="alert('Fitur laporan akan segera hadir!')">
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

    // Jalankan Load Posts Saat Awal
    loadPosts();

    // Expose ke global agar bisa dipanggil dari luar
    window.loadPosts = loadPosts;

    // Helper function untuk escape HTML (keamanan XSS)
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    window.escapeHtml = escapeHtml;

}); // Akhir DOMContentLoaded

// ==================================================
// 5. FUNGSI GLOBAL (Like & Modal)
// ==================================================

// Fungsi Handle Like
function handleLike(btn, postId) {
    const countSpan = btn.querySelector('.like-count');
    const iconImg = btn.querySelector('img');
    let currentCount = parseInt(countSpan.innerText) || 0;

    // Cek apakah sedang di-like atau unlike
    const isCurrentlyLiked = btn.classList.contains('text-red-500');

    // Toggle UI immediately (optimistic update)
    if (isCurrentlyLiked) {
        // UNLIKE
        btn.classList.remove('text-red-500');
        btn.classList.add('text-gray-500');
        iconImg.classList.remove('filter-red');
        countSpan.innerText = Math.max(0, currentCount - 1);
    } else {
        // LIKE
        btn.classList.add('text-red-500');
        btn.classList.remove('text-gray-500');
        iconImg.classList.add('filter-red');
        countSpan.innerText = currentCount + 1;
    }

    // Kirim ke Server
    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('/Sinergi/api/like_post.php', {
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
                    btn.classList.remove('text-gray-500');
                    iconImg.classList.add('filter-red');
                    countSpan.innerText = currentCount;
                } else {
                    btn.classList.remove('text-red-500');
                    btn.classList.add('text-gray-500');
                    iconImg.classList.remove('filter-red');
                    countSpan.innerText = currentCount;
                }
                alert(data.message || 'Gagal melakukan like.');
            }
        })
        .catch(err => {
            console.error('Error Like API:', err);
            // Revert UI jika error
            if (isCurrentlyLiked) {
                btn.classList.add('text-red-500');
                btn.classList.remove('text-gray-500');
                iconImg.classList.add('filter-red');
                countSpan.innerText = currentCount;
            } else {
                btn.classList.remove('text-red-500');
                btn.classList.add('text-gray-500');
                iconImg.classList.remove('filter-red');
                countSpan.innerText = currentCount;
            }
            alert('Terjadi kesalahan koneksi.');
        });
}

// Fungsi Modal Gambar (Posisi Tengah Layar)
function openImageModal(imageSrc) {
    // Hapus modal lama jika ada
    const oldModal = document.getElementById('image-modal-overlay');
    if (oldModal) oldModal.remove();

    // Buat Overlay
    const modal = document.createElement('div');
    modal.id = 'image-modal-overlay';
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background-color: rgba(0, 0, 0, 0.9); z-index: 99999;
        display: flex; align-items: center; justify-content: center;
        padding: 20px; backdrop-filter: blur(5px);
    `;

    // Klik background tutup modal
    modal.onclick = function() {
        modal.remove();
    };

    // Gambar
    const img = document.createElement('img');
    img.src = imageSrc;
    img.style.cssText = `
        max-width: 100%; max-height: 90vh;
        border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        object-fit: contain;
    `;
    img.onclick = function(e) {
        e.stopPropagation();
    };

    // Tombol Close
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = `
        position: absolute; top: 20px; right: 30px;
        color: white; font-size: 40px; font-weight: bold; cursor: pointer;
        background: none; border: none; z-index: 100000;
        transition: transform 0.2s;
    `;
    closeBtn.onmouseover = function() {
        this.style.transform = 'scale(1.2)';
    };
    closeBtn.onmouseout = function() {
        this.style.transform = 'scale(1)';
    };
    closeBtn.onclick = function() {
        modal.remove();
    };

    modal.appendChild(closeBtn);
    modal.appendChild(img);
    document.body.appendChild(modal);
}
</script>

<style>
/* Helper Class untuk mengubah icon hitam menjadi merah */
.filter-red {
    filter: invert(37%) sepia(93%) saturate(3646%) hue-rotate(335deg) brightness(97%) contrast(96%);
    transform: scale(1.15);
}

/* Transisi halus untuk icon */
.icon-transition {
    transition: filter 0.3s ease, transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Utility tambahan */
.break-words {
    word-break: break-word;
}

/* Loading animation */
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

</body>

</html>