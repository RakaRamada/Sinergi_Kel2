<?php
// File: app/views/partials/postingan.php
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("Sistem Postingan Siap!");

    const postFeedContainer = document.getElementById('post-feed-container');
    const createPostForm = document.getElementById('create-post-form');
    const submitButton = document.getElementById('submit-post-button');
    
    const imageInput = document.getElementById('post-image-input');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const imagePreview = document.getElementById('image-preview');
    const removeImageBtn = document.getElementById('remove-image-btn');

    // CRITICAL: Ambil current user ID dari session
    const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;

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
            if(imageInput) imageInput.value = '';
            if (imagePreviewContainer) imagePreviewContainer.classList.add('hidden');
            if (imagePreview) imagePreview.src = '#';
        });
    }

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

            if(submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Memposting...';
            }

            const formData = new FormData(this);

            fetch('/Sinergi/index.php?page=post-api&method=createPost', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    createPostForm.reset(); 
                    if (imagePreviewContainer) imagePreviewContainer.classList.add('hidden');
                    if (imagePreview) imagePreview.src = '#';
                    loadPosts(); 
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    alert('Gagal: ' + (data.message || 'Terjadi kesalahan server'));
                }
            })
            .catch(error => {
                console.error('Error Upload:', error);
                alert('Terjadi kesalahan koneksi.');
            })
            .finally(() => {
                if(submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Posting';
                }
            });
        });
    }

//LOAD POSTINGAN
    function loadPosts() {
        if (!postFeedContainer) return; 

        postFeedContainer.innerHTML = '<div class="p-8 text-center text-gray-500"><div class="animate-pulse">Sedang memuat...</div></div>';

        fetch('/Sinergi/index.php?page=post-api&method=getPostings')
            .then(response => {
                if(!response.ok) throw new Error('Network response was not ok');
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
                    const postImageHTML = post.POST_IMAGE ? 
                        `<div class="mt-3 mb-1">
                            <img src="${escapeHtml(post.POST_IMAGE)}" 
                                 alt="Post image"
                                 class="rounded-lg w-full border border-gray-200 cursor-pointer hover:opacity-95 transition-opacity object-cover"
                                 onclick="openImageModal('${escapeHtml(post.POST_IMAGE)}')"
                                 style="max-height: 500px;">
                         </div>` : '';

                    const isLiked = post.USER_SUDAH_LIKE > 0;
                    const likeColorClass = isLiked ? 'text-red-500' : 'text-gray-500';
                    const iconFilterClass = isLiked ? 'filter-red' : '';

                    const kontenText = post.KONTEN ? escapeHtml(post.KONTEN) : '';

                    const isOwnPost = parseInt(post.USER_ID) === parseInt(currentUserId);
                    
                    // Button Report yang diletakkan sejajar dengan Like & Comment
                    const reportButtonHTML = !isOwnPost ? `
                        <button class="group flex items-center space-x-2 hover:text-green-500 transition-colors w-fit"
                                onclick="event.stopPropagation(); openReportModal(${post.POST_ID})" title="Laporkan">
                            <div class="p-2 rounded-full group-hover:bg-green-50 transition-colors">
                                <img src="/Sinergi/public/assets/icons/report.svg" 
                                     alt="Laporkan" 
                                     class="w-5 h-5">
                            </div>
                        </button>
                    ` : '';
                    
                    // UPDATE LAYOUT DISINI
                    const postHTML = `
                        <div class="bg-white border-b border-gray-200 hover:bg-gray-50/30 transition-colors cursor-pointer" onclick="window.location.href='index.php?page=post-detail&id=${post.POST_ID}'">
                            <div class="p-4">
                                <div class="flex items-start space-x-3">
                                    <!-- Avatar -->
                                    <div class="flex-shrink-0 cursor-pointer" onclick="event.stopPropagation(); window.location.href='index.php?page=profile&id=${post.USER_ID}'">
                                         <img src="${escapeHtml(post.AVATAR_URL_FIXED)}" 
                                              alt="Avatar" 
                                              class="w-10 h-10 rounded-full object-cover bg-gray-200 border border-gray-100">
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <!-- Header: @Username (Bold) | Role (Gray) -->
                                        <div class="flex items-center space-x-2 mb-1">
                                            <a href="index.php?page=profile&id=${post.USER_ID}" 
                                               onclick="event.stopPropagation();"
                                               class="font-bold text-gray-900 hover:underline text-[15px]">
                                                @${escapeHtml(post.USERNAME)}
                                            </a>
                                            <span class="text-gray-500 text-sm">${escapeHtml(post.ROLE_NAME || 'User')}</span>
                                        </div>
                                        
                                        <!-- Konten -->
                                        <div class="mb-2">
                                            <p class="text-gray-800 text-[15px] leading-normal break-words whitespace-pre-wrap">${kontenText}</p>
                                        </div>
                                        
                                        ${postImageHTML}

                                        <!-- Footer: Actions (Left) - Date (Right) -->
                                        <div class="flex items-center justify-between mt-3">
                                            
                                            <!-- Kiri: Like, Comment, Report -->
                                            <div class="flex items-center space-x-6 text-gray-500">
                                                <button onclick="event.stopPropagation(); handleLike(this, ${post.POST_ID})" 
                                                        class="group flex items-center space-x-2 hover:text-red-500 transition-colors ${likeColorClass}">
                                                    <div class="p-2 -ml-2 rounded-full group-hover:bg-red-50 transition-colors relative">
                                                        <img src="/Sinergi/public/assets/icons/heart.svg" 
                                                             alt="Like" 
                                                             class="w-5 h-5 icon-transition ${iconFilterClass}">
                                                    </div>
                                                    <span class="like-count text-sm font-medium">${post.TOTAL_LIKES || 0}</span>
                                                </button>

                                                <a href="index.php?page=post-detail&id=${post.POST_ID}"
                                                    class="group flex items-center space-x-2 hover:text-blue-500 transition-colors">
                                                    <div class="p-2 rounded-full group-hover:bg-blue-50 transition-colors">
                                                        <img src="/Sinergi/public/assets/icons/comment.svg" 
                                                             alt="Komentar" 
                                                             class="w-5 h-5">
                                                    </div>
                                                    <span class="text-sm font-medium">${post.TOTAL_COMMENTS || 0}</span>
                                                </a>

                                                ${reportButtonHTML}
                                            </div>

                                            <!-- Kanan: Tanggal -->
                                            <div class="text-gray-400 text-xs hover:underline" title="${escapeHtml(post.CREATED_AT_STR || '')}">
                                                ${escapeHtml(post.WAKTU_POSTING)}
                                            </div>

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
    
    loadPosts();
    window.loadPosts = loadPosts;

    function escapeHtml(text) {
        if(!text) return '';
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

function handleLike(btn, postId) {
    const countSpan = btn.querySelector('.like-count');
    const iconImg = btn.querySelector('img'); 
    let currentCount = parseInt(countSpan.innerText) || 0;
    const isCurrentlyLiked = btn.classList.contains('text-red-500');
    
    if (isCurrentlyLiked) {
        btn.classList.remove('text-red-500');
        btn.classList.add('text-gray-500');
        iconImg.classList.remove('filter-red');
        countSpan.innerText = Math.max(0, currentCount - 1);
    } else {
        btn.classList.add('text-red-500');
        btn.classList.remove('text-gray-500');
        iconImg.classList.add('filter-red');
        countSpan.innerText = currentCount + 1;
    }

    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('/Sinergi/index.php?page=post-api&method=toggleLike', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status !== 'success') {
            console.error("Like gagal:", data.message);
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

function openImageModal(imageSrc) {
    const oldModal = document.getElementById('image-modal-overlay');
    if(oldModal) oldModal.remove();

    const modal = document.createElement('div');
    modal.id = 'image-modal-overlay';
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background-color: rgba(0, 0, 0, 0.9); z-index: 99999;
        display: flex; align-items: center; justify-content: center;
        padding: 20px; backdrop-filter: blur(5px);
    `;
    
    modal.onclick = function() { modal.remove(); };

    const img = document.createElement('img');
    img.src = imageSrc;
    img.style.cssText = `
        max-width: 100%; max-height: 90vh;
        border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        object-fit: contain;
    `;
    img.onclick = function(e) { e.stopPropagation(); };

    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = `
        position: absolute; top: 20px; right: 30px;
        color: white; font-size: 40px; font-weight: bold; cursor: pointer;
        background: none; border: none; z-index: 100000;
        transition: transform 0.2s;
    `;
    closeBtn.onmouseover = function() { this.style.transform = 'scale(1.2)'; };
    closeBtn.onmouseout = function() { this.style.transform = 'scale(1)'; };
    closeBtn.onclick = function() { modal.remove(); };

    modal.appendChild(closeBtn);
    modal.appendChild(img);
    document.body.appendChild(modal);
}

// ==================================================
// 6. FUNGSI REPORT POSTINGAN
// ==================================================

function openReportModal(postId) {
    const oldModal = document.getElementById('report-modal-overlay');
    if(oldModal) oldModal.remove();

    const modal = document.createElement('div');
    modal.id = 'report-modal-overlay';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.style.backdropFilter = 'blur(5px)';
    
    modal.innerHTML = `
        <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Laporkan Postingan</h3>
                <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="report-form" onsubmit="submitReport(event, ${postId})">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Pilih alasan laporan:
                    </label>
                    
                    <div class="space-y-2">
                        <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="reason" value="Spam atau konten menyesatkan" class="mr-3" required>
                            <span class="text-sm">Spam atau konten menyesatkan</span>
                        </label>
                        
                        <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="reason" value="Ujaran kebencian atau pelecehan" class="mr-3" required>
                            <span class="text-sm">Ujaran kebencian atau pelecehan</span>
                        </label>
                        
                        <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="reason" value="Konten kekerasan atau berbahaya" class="mr-3" required>
                            <span class="text-sm">Konten kekerasan atau berbahaya</span>
                        </label>
                        
                        <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="reason" value="Konten tidak pantas atau dewasa" class="mr-3" required>
                            <span class="text-sm">Konten tidak pantas atau dewasa</span>
                        </label>
                        
                        <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="reason" value="Pelanggaran privasi" class="mr-3" required>
                            <span class="text-sm">Pelanggaran privasi</span>
                        </label>
                        
                        <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="reason" value="Lainnya" class="mr-3" required>
                            <span class="text-sm">Lainnya</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closeReportModal()"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium">
                        Batal
                    </button>
                    <button type="submit" id="report-submit-btn"
                            class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 font-medium">
                        Kirim Laporan
                    </button>
                </div>
            </form>
        </div>
    `;
    
    modal.onclick = function() { closeReportModal(); };
    document.body.appendChild(modal);
}

function closeReportModal() {
    const modal = document.getElementById('report-modal-overlay');
    if(modal) modal.remove();
}

function submitReport(event, postId) {
    event.preventDefault();
    
    const form = event.target;
    const submitBtn = document.getElementById('report-submit-btn');
    const selectedReason = form.querySelector('input[name="reason"]:checked');
    
    if (!selectedReason) {
        alert('Pilih alasan laporan terlebih dahulu');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Mengirim...';
    
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('reason', selectedReason.value);
    
    fetch('/Sinergi/index.php?page=post-api&method=addReport', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.status === true) {
            alert('✓ Laporan berhasil dikirim. Tim kami akan meninjau konten ini.');
            closeReportModal();
        } else {
            alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan koneksi');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Kirim Laporan';
    });
}

window.openReportModal = openReportModal;
window.closeReportModal = closeReportModal;
window.submitReport = submitReport;
</script>

<style>
    .filter-red {
        filter: invert(37%) sepia(93%) saturate(3646%) hue-rotate(335deg) brightness(97%) contrast(96%);
        transform: scale(1.15);
    }
    
    .icon-transition {
        transition: filter 0.3s ease, transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .break-words {
        word-break: break-word;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
</style>