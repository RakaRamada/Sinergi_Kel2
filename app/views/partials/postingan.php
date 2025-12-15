<?php
// File: app/views/partials/postingan.php
// VERSI FINAL: GABUNGAN + TEMA HITAM PUTIH + ALERT MODERN
?>

<div id="post-feed-container" class="space-y-4"></div>

<div id="modal-container"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("Sistem Postingan Modern (Multi-Image + Full Features) Siap!");

    // 1. SETUP & VARIABEL
    const postFeedContainer = document.getElementById('post-feed-container');
    const createPostForm = document.getElementById('create-post-form');
    const submitButton = document.getElementById('submit-post-button');
    const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;

    // [BARU] Array Penampung File & Limit
    let uploadedFiles = [];
    const MAX_IMAGES = 10;

    // [UPDATE] Pastikan name dihapus agar tidak double submit via form default
    const imageInput = document.getElementById('post-image-input');
    if (imageInput) {
        imageInput.removeAttribute('name'); // Hapus name bawaan HTML
        imageInput.setAttribute('multiple', 'multiple');
    }

    const imagePreviewContainer = document.getElementById('image-preview-container');
    const imagePreview = document.getElementById('image-preview'); // Preview lama (single)
    const removeImageBtn = document.getElementById('remove-image-btn');

    // 2. LOGIKA IMAGE PREVIEW (MULTI GRID)
    function renderPreview() {
        // Buat Grid jika belum ada
        let previewGrid = document.getElementById('preview-grid');
        if (!previewGrid && imagePreviewContainer) {
            previewGrid = document.createElement('div');
            previewGrid.id = 'preview-grid';
            previewGrid.className = "flex gap-2 overflow-x-auto pb-2 custom-scrollbar";
            imagePreviewContainer.insertBefore(previewGrid, removeImageBtn);
            if (imagePreview) imagePreview.style.display = 'none';
        }

        if (!previewGrid) return;
        previewGrid.innerHTML = ''; // Reset tampilan grid

        if (uploadedFiles.length > 0) {
            if (imagePreviewContainer) imagePreviewContainer.classList.remove('hidden');

            uploadedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const wrapper = document.createElement('div');
                    wrapper.className =
                        "relative w-20 h-20 shrink-0 rounded-lg overflow-hidden border border-gray-200 group";
                    wrapper.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-full object-cover">
                        <button type="button" class="absolute top-0 right-0 bg-red-600 text-white p-0.5 rounded-bl-lg opacity-0 group-hover:opacity-100 transition-opacity" 
                                onclick="removeSingleFile(${index})">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    `;
                    previewGrid.appendChild(wrapper);
                }
                reader.readAsDataURL(file);
            });
        } else {
            if (imagePreviewContainer) imagePreviewContainer.classList.add('hidden');
        }
    }

    // Listener Input
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const newFiles = Array.from(this.files);

            // Cek Limit
            if (uploadedFiles.length + newFiles.length > MAX_IMAGES) {
                showModernAlert('Limit Tercapai', `Maksimal hanya ${MAX_IMAGES} gambar.`);
                this.value = '';
                return;
            }

            // Masukkan ke Array Global
            newFiles.forEach(file => {
                if (file.type.startsWith('image/')) {
                    uploadedFiles.push(file);
                }
            });

            renderPreview();
            this.value = ''; // PENTING: Reset agar bisa pilih file lagi
        });
    }

    // Fungsi Hapus Satu Gambar
    window.removeSingleFile = function(index) {
        uploadedFiles.splice(index, 1);
        renderPreview();
    }

    // Tombol Reset Semua
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            uploadedFiles = [];
            renderPreview();
            if (imageInput) imageInput.value = '';
        });
    }

    // 3. LOGIKA SUBMIT POST
    if (createPostForm) {
        createPostForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const kontenInput = this.querySelector('textarea[name="konten"]');
            const konten = kontenInput ? kontenInput.value.trim() : '';
            const hasImage = imageInput && imageInput.files.length > 0;

            if (!konten && !hasImage) {
                showModernAlert('Info', "Tulis sesuatu atau pilih gambar.");
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Memposting...';
            }

            const formData = new FormData(this); // Ambil data text area

            // [BARU] Masukkan file dari Array Accumulative ke FormData
            uploadedFiles.forEach(file => {
                formData.append('post_image[]', file);
            });

            fetch('index.php?page=post-api&method=createPost', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        createPostForm.reset();
                        // Reset Preview
                        if (imageInput) imageInput.value = '';
                        if (imagePreviewContainer) imagePreviewContainer.classList.add('hidden');
                        const grid = document.getElementById('preview-grid');
                        if (grid) grid.innerHTML = '';

                        loadPosts();
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    } else {
                        showModernAlert('Gagal', data.message || 'Error server');
                    }
                })
                .catch(error => showModernAlert('Error', 'Koneksi bermasalah.'))
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Posting';
                    }
                });
        });
    }

    // 4. LOAD POSTS (CAROUSEL + REPORT BUTTON RESTORED)
    function loadPosts() {
        if (!postFeedContainer) return;

        postFeedContainer.innerHTML =
            '<div class="p-8 text-center text-gray-500"><div class="animate-pulse">Sedang memuat...</div></div>';

        fetch('index.php?page=post-api&method=getPostings')
            .then(res => res.json())
            .then(posts => {
                postFeedContainer.innerHTML = '';

                if (!Array.isArray(posts) || posts.length === 0) {
                    postFeedContainer.innerHTML = `
                        <div class="p-8 text-center text-gray-500 border-b border-gray-200">
                            <p class="mb-2">Belum ada postingan.</p>
                            <p class="text-sm">Jadilah yang pertama!</p>
                        </div>`;
                    return;
                }

                posts.forEach(post => {
                    // --- A. MULTI IMAGE CAROUSEL LOGIC ---
                    let imagesHTML = '';
                    if (post.POST_IMAGE) {
                        const images = post.POST_IMAGE.split(',').filter(p => p.trim() !== '');

                        if (images.length === 1) {
                            // Single Image
                            imagesHTML = `
                                <div class="mt-3 mb-1">
                                    <img src="${escapeHtml(images[0])}" 
                                         class="rounded-lg w-full border border-gray-200 cursor-pointer hover:opacity-95 transition-opacity object-cover"
                                         style="max-height: 500px;"
                                         onclick="openImageModal('${escapeHtml(images[0])}')">
                                </div>`;
                        } else if (images.length > 1) {
                            // Carousel / Slider
                            const carouselId = `carousel-${post.POST_ID}`;

                            const slides = images.map(img => `
                                <div class="snap-center shrink-0 w-full flex justify-center items-center bg-gray-50 aspect-[4/3] sm:aspect-auto sm:h-[500px]">
                                    <img src="${escapeHtml(img)}" 
                                         class="max-h-full max-w-full object-contain cursor-pointer"
                                         onclick="event.stopPropagation(); openImageModal('${escapeHtml(img)}')"> 
                                </div>
                            `).join('');

                            const dots = images.map((_, idx) => `
                                <div class="w-2 h-2 rounded-full bg-white/50 transition-all ${idx===0?'bg-white scale-125':''}" data-index="${idx}"></div>
                            `).join('');

                            const prevBtnId = `btn-prev-${post.POST_ID}`;
                            const nextBtnId = `btn-next-${post.POST_ID}`;

                            imagesHTML = `
                                <div class="mt-3 mb-1 relative group rounded-lg overflow-hidden border border-gray-200" id="${carouselId}">
                                    <div class="carousel-track flex overflow-x-auto snap-x snap-mandatory scrollbar-hide bg-black"
                                         style="scrollbar-width: none; -ms-overflow-style: none;"
                                         onscroll="updateCarouselUI('${carouselId}', '${prevBtnId}', '${nextBtnId}')">
                                        ${slides}
                                    </div>

                                    <div class="absolute bottom-3 left-0 right-0 flex justify-center gap-2 z-10 pointer-events-none">
                                        ${dots}
                                    </div>

                                    <button id="${prevBtnId}" onclick="event.stopPropagation(); scrollCarousel('${carouselId}', -1)" 
                                            class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-1.5 rounded-full opacity-0 group-hover:opacity-100 transition hover:bg-black/70 cursor-pointer hidden">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    
                                    <button id="${nextBtnId}" onclick="event.stopPropagation(); scrollCarousel('${carouselId}', 1)" 
                                            class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-1.5 rounded-full opacity-0 group-hover:opacity-100 transition hover:bg-black/70 cursor-pointer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            `;
                        }
                    }

                    // --- B. REPORT BUTTON LOGIC (RESTORED) ---
                    const isOwnPost = parseInt(post.USER_ID) === parseInt(currentUserId);
                    const reportButtonHTML = !isOwnPost ? `
                        <button class="group flex items-center space-x-2 hover:text-green-500 transition-colors w-fit cursor-pointer"
                                onclick="event.stopPropagation(); openReportModal(${post.POST_ID})" title="Laporkan">
                            <div class="p-2 rounded-full group-hover:bg-green-50 transition-colors cursor-pointer">
                                <img src="/Sinergi/public/assets/icons/report.svg" alt="Laporkan" class="w-5 h-5 cursor-pointer">
                            </div>
                        </button>
                    ` : '';

                    const likeClass = post.USER_SUDAH_LIKE > 0 ? 'text-red-500' : 'text-gray-500';
                    const fillClass = post.USER_SUDAH_LIKE > 0 ? 'fill-current' :
                        'fill-none stroke-current';
                    const likeCount = post.LIKE_COUNT || post.TOTAL_LIKES || 0;
                    const commentCount = post.COMMENT_COUNT || post.TOTAL_COMMENTS || 0;

                    // --- HTML STRUCTURE ---
                    const postHTML = `
                        <div class="bg-white border-b border-gray-200 hover:bg-gray-50/30 transition-colors cursor-pointer" 
                             onclick="window.location.href='index.php?page=post-detail&id=${post.POST_ID}'">
                            <div class="p-4">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 cursor-pointer" onclick="event.stopPropagation(); window.location.href='index.php?page=profile&id=${post.USER_ID}'">
                                         <img src="${escapeHtml(post.AVATAR_URL_FIXED)}" 
                                              class="w-10 h-10 rounded-full object-cover bg-gray-200 border border-gray-100">
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-x-2 mb-1">
                                            <a href="index.php?page=profile&id=${post.USER_ID}" 
                                               onclick="event.stopPropagation();"
                                               class="font-bold text-gray-900 text-[15px] hover:underline cursor-pointer">
                                                @${escapeHtml(post.USERNAME)}
                                            </a>
                                            ${post.ROLE_NAME ? 
                                                `<span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-[10px] rounded-full font-medium border border-gray-100">${escapeHtml(post.ROLE_NAME)}</span>` : ''
                                            }
                                            <span class="text-gray-400 text-sm">·</span>
                                            <div class="text-xs text-gray-500" title="${escapeHtml(post.CREATED_AT_STR || '')}">
                                                ${escapeHtml(post.WAKTU_POSTING)}
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <p class="text-gray-800 text-[15px] leading-normal break-words whitespace-pre-wrap">${escapeHtml(post.KONTEN)}</p>
                                        </div>
                                        
                                        ${imagesHTML}

                                        <div class="flex items-center justify-between mt-3 max-w-md text-gray-500">
                                            <button onclick="event.stopPropagation(); handleLike(this, ${post.POST_ID})" 
                                                    class="group flex items-center space-x-2 hover:text-red-500 transition-colors ${likeClass} w-fit cursor-pointer">
                                                <div class="p-2 -ml-2 rounded-full group-hover:bg-red-50 relative cursor-pointer">
                                                    <svg class="w-5 h-5 transition-transform duration-200 ${fillClass}" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                                                </div>
                                                <span class="like-count text-sm font-medium">${likeCount}</span>
                                            </button>

                                            <a href="index.php?page=post-detail&id=${post.POST_ID}"
                                                onclick="event.stopPropagation();"
                                                class="group flex items-center space-x-2 hover:text-blue-500 transition-colors w-fit cursor-pointer">
                                                <div class="p-2 rounded-full group-hover:bg-blue-50 cursor-pointer">
                                                    <img src="/Sinergi/public/assets/icons/comment.svg" alt="Komentar" class="w-5 h-5">
                                                </div>
                                                <span class="text-sm font-medium">${commentCount}</span>
                                            </a>

                                            ${reportButtonHTML}
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
                console.error(error);
                postFeedContainer.innerHTML =
                    `<div class="p-8 text-center text-red-500">Gagal memuat postingan.</div>`;
            });
    }

    loadPosts();
    window.loadPosts = loadPosts;

    function escapeHtml(text) {
        if (!text) return '';
        return text.toString().replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[m]);
    }
});

// ==================================================
// 5. HELPER FUNCTIONS (CAROUSEL, LIKE, REPORT, MODAL)
// ==================================================

function scrollCarousel(id, direction) {
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

function updateCarouselUI(id, prevBtnId, nextBtnId) {
    const container = document.getElementById(id);
    if (!container) return;

    const track = container.querySelector('.carousel-track');
    const dots = container.querySelectorAll('.absolute.bottom-3 div');
    const prevBtn = document.getElementById(prevBtnId);
    const nextBtn = document.getElementById(nextBtnId);

    if (!track) return;

    // 1. Update Dots
    const index = Math.round(track.scrollLeft / track.clientWidth);
    if (dots.length > 0) {
        dots.forEach((dot, idx) => {
            if (idx === index) {
                dot.classList.add('bg-white', 'scale-125');
                dot.classList.remove('bg-white/50');
            } else {
                dot.classList.remove('bg-white', 'scale-125');
                dot.classList.add('bg-white/50');
            }
        });
    }

    // 2. LOGIKA TOMBOL HILANG (Smart Nav)
    // Toleransi 5px untuk browser yang hitungannya desimal
    const isAtStart = track.scrollLeft <= 5;
    const isAtEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 5;

    if (prevBtn) {
        if (isAtStart) prevBtn.classList.add('hidden');
        else prevBtn.classList.remove('hidden');
    }

    if (nextBtn) {
        if (isAtEnd) nextBtn.classList.add('hidden');
        else nextBtn.classList.remove('hidden');
    }
}

function handleLike(btn, postId) {
    const countSpan = btn.querySelector('.like-count');
    const svgIcon = btn.querySelector('svg');
    let currentCount = parseInt(countSpan.innerText) || 0;
    const isLiked = btn.classList.contains('text-red-500');

    if (isLiked) {
        btn.classList.remove('text-red-500');
        btn.classList.add('text-gray-500');
        svgIcon.classList.remove('fill-current');
        svgIcon.classList.add('fill-none', 'stroke-current');
        countSpan.innerText = Math.max(0, currentCount - 1);
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
    });
}

function openImageModal(src) {
    const d = document.createElement('div');
    d.className = "fixed inset-0 bg-black/90 z-[9999] flex items-center justify-center p-4 backdrop-blur-sm";
    d.onclick = () => d.remove();
    d.innerHTML = `<img src="${src}" class="max-w-full max-h-screen object-contain rounded shadow-2xl">`;
    document.body.appendChild(d);
}

// --- RESTORED: REPORT MODAL LOGIC ---
function openReportModal(postId) {
    const oldModal = document.getElementById('report-modal-overlay');
    if (oldModal) oldModal.remove();

    const modal = document.createElement('div');
    modal.id = 'report-modal-overlay';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/20 backdrop-blur-md';

    modal.innerHTML = `
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl border border-gray-100 transform transition-all scale-100" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-gray-900">Laporkan Postingan</h3>
                <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form id="report-form" onsubmit="submitReport(event, ${postId})">
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Mengapa Anda melaporkan ini?</label>
                    <div class="space-y-2 max-h-60 overflow-y-auto pr-1 custom-scrollbar">
                        ${['Spam atau konten menyesatkan', 'Ujaran kebencian atau pelecehan', 'Konten kekerasan', 'Konten dewasa', 'Pelanggaran privasi'].map(r => `
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer group">
                                <input type="radio" name="reason" value="${r}" class="mr-3 text-black focus:ring-black cursor-pointer" required>
                                <span class="text-sm text-gray-700 group-hover:text-black">${r}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="closeReportModal()" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700 font-medium transition-colors cursor-pointer">Batal</button>
                    <button type="submit" id="report-submit-btn" class="flex-1 px-4 py-2.5 bg-black text-white rounded-lg hover:bg-gray-800 font-medium transition-colors cursor-pointer shadow-lg hover:shadow-xl">Kirim Laporan</button>
                </div>
            </form>
        </div>
    `;
    modal.onclick = closeReportModal;
    document.body.appendChild(modal);
}

function closeReportModal() {
    const modal = document.getElementById('report-modal-overlay');
    if (modal) modal.remove();
}

function submitReport(event, postId) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = document.getElementById('report-submit-btn');
    const selectedReason = form.querySelector('input[name="reason"]:checked');

    if (!selectedReason) {
        showModernAlert('Perhatian', 'Pilih alasan laporan terlebih dahulu');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="animate-pulse">Mengirim...</span>';

    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('reason', selectedReason.value);

    fetch('index.php?page=post-api&method=addReport', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' || data.status === true) {
                closeReportModal();
                showModernAlert('Berhasil', 'Laporan Anda telah kami terima.');
            } else {
                showModernAlert('Gagal', data.message || 'Terjadi kesalahan');
            }
        })
        .catch(error => showModernAlert('Error', 'Terjadi kesalahan koneksi'))
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Kirim Laporan';
            }
        });
}

// --- RESTORED: MODERN ALERT ---
function showModernAlert(title, message) {
    const oldAlert = document.getElementById('modern-alert-overlay');
    if (oldAlert) oldAlert.remove();

    const isSuccess = title.toLowerCase().includes('berhasil');
    const icon = isSuccess ?
        `<div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-black mb-4"><svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></div>` :
        `<div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-4"><svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg></div>`;

    const overlay = document.createElement('div');
    overlay.id = 'modern-alert-overlay';
    overlay.className =
        'fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/20 backdrop-blur-md transition-opacity duration-300';
    overlay.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-sm w-full text-center border border-gray-100 animate-bounce-in">
            ${icon}
            <h3 class="text-lg font-bold text-gray-900 mb-2">${title}</h3>
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">${message}</p>
            <button onclick="document.getElementById('modern-alert-overlay').remove()" class="w-full bg-black text-white rounded-lg px-4 py-2.5 hover:bg-gray-800 transition-colors font-medium cursor-pointer shadow-md">OK</button>
        </div>
    `;
    document.body.appendChild(overlay);
}
</script>

<style>
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
    animation: bounceIn 0.2s ease-out forwards;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Hide Scrollbar for Carousel but keep functionality */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>