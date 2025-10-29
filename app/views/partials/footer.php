</div>
</div>
<script>
    // Jalankan kode hanya setelah semua HTML dimuat
    document.addEventListener('DOMContentLoaded', function() {

        // ==================================================
        // BAGIAN 1: FUNGSI MEMUAT POSTINGAN (READ)
        // ==================================================
        const postFeedContainer = document.getElementById('post-feed-container');

        function loadPosts() {
            // Cek jika kita berada di halaman/tab yang tepat
            if (!postFeedContainer) return; 

            postFeedContainer.innerHTML = '<div class="p-4 text-center text-gray-500 border-b border-gray-200">Memuat postingan...</div>';

            // Panggil API yang kita buat
            fetch('/Sinergi/api/ambil_postingan.php')
                .then(response => response.json())
                .then(posts => {
                    postFeedContainer.innerHTML = ''; // Kosongkan loading

                    if (posts.length === 0) {
                        postFeedContainer.innerHTML = '<div class="p-4 text-center text-gray-500 border-b border-gray-200">Belum ada postingan.</div>';
                        return;
                    }

                    // Loop setiap data post dan buat HTML-nya
                    posts.forEach(post => {
                        // 'post' adalah object JSON. Key-nya HURUF BESAR
                        // karena itu adalah output default dari oci_fetch_assoc()
                        
                        // Cek jika ada gambar
                        const postImageHTML = post.POST_IMAGE ? 
                            `<img src="${post.POST_IMAGE}" alt="Post Image" class="rounded-lg w-full mt-4 border border-gray-200">` : '';

                        // Tentukan kelas tombol like (merah jika sudah di-like)
                        // 'USER_SUDAH_LIKE' adalah COUNT, jadi nilainya 0 atau 1
                        const likeButtonClass = post.USER_SUDAH_LIKE > 0 ? 
                            'text-red-500' : // Sudah di-like
                            'text-gray-500 hover:text-red-500'; // Belum

                        // Template HTML ini disalin dari dashboard.php Anda
                        // Data statis diganti dengan data dari 'post'
                        const postHTML = `
                        <div class="bg-white border-b border-gray-200 mb-6">
                            <div class="p-4">
                                <div class="flex items-center mb-3">
                                    <!-- Arahkan ke profile.php (belum dibuat) dengan id_users -->
                                    <a href="index.php?page=profile&id=${post.ID_USER}">
                                        <img src="${post.AVATAR_URL_FIXED}" alt="Avatar"
                                            class="w-12 h-12 rounded-full mr-4">
                                    </a>
                                    <div>
                                        <a href="index.php?page=profile&id=${post.ID_USER}"
                                            class="font-bold text-gray-800 hover:underline">${post.NAMA_LENGKAP}</a>
                                        <!-- Tampilkan USERNAME asli -->
                                        <p class="text-sm font-normal text-gray-500">@${post.USERNAME} | Mahasiswa</p>
                                        <p class="text-xs text-gray-500">${post.WAKTU_POSTING}</p>
                                    </div>
                                </div>

                                <!-- Arahkan ke post-detail dengan id_postingan -->
                                <a href="index.php?page=post-detail&id=${post.ID_POSTINGAN}" class="block">
                                    <p class="text-gray-700 mb-4">${post.KONTEN ? post.KONTEN.replace(/\\n/g, '<br>') : ''}</p>
                                    ${postImageHTML}
                                </a>
                            </div>

                            <div class="p-4 flex items-center justify-center text-gray-500 space-x-8 border-t border-gray-200">
                                <!-- Beri ID unik dan data-post-id untuk tombol -->
                                <button data-post-id="${post.ID_POSTINGAN}" class="like-button flex items-center space-x-2 ${likeButtonClass} cursor-pointer">
                                    <img src="/Sinergi/public/assets/icons/heart.svg" alt="Suka" class="w-6 h-6">
                                    <span class="like-count">${post.TOTAL_LIKES}</span>
                                </button>
                                
                                <!-- Link ke detail post -->
                                <a href="index.php?page=post-detail&id=${post.ID_POSTINGAN}"
                                    class="flex items-center space-x-2 hover:text-blue-500 cursor-pointer">
                                    <img src="/Sinergi/public/assets/icons/comment.svg" alt="Komentar" class="w-7 h-7">
                                    <span>${post.TOTAL_COMMENTS}</span>
                                </a>
                                <button class="flex items-center space-x-2 hover:text-green-500 cursor-pointer">
                                    <img src="/Sinergi/public/assets/icons/share.svg" alt="Bagikan" class="w-6 h-6">
                                    <span>0</span>
                                </button>
                            </div>
                        </div>
                        `;
                        postFeedContainer.innerHTML += postHTML;
                    });
                })
                .catch(error => {
                    console.error('Error memuat postingan:', error);
                    postFeedContainer.innerHTML = '<div class="p-4 text-center text-red-500 border-b border-gray-200">Gagal memuat postingan.</div>';
                });
        }
        
        // Panggil fungsi ini saat halaman dimuat
        loadPosts();

        // ==================================================
        // BAGIAN 2: FUNGSI MEMBUAT POSTINGAN (CREATE)
        // ==================================================
        const createPostForm = document.getElementById('create-post-form');
        const submitButton = document.getElementById('submit-post-button');
        
        // --- Handle Image Preview ---
        const imageInput = document.getElementById('post-image-input');
        const imagePreviewContainer = document.getElementById('image-preview-container');
        const imagePreview = document.getElementById('image-preview');

        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.setAttribute('src', e.target.result);
                    imagePreviewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                imagePreviewContainer.classList.add('hidden');
            }
        });

        // --- Handle Form Submission ---
        if (createPostForm) {
            createPostForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Hentikan reload halaman

                submitButton.disabled = true;
                submitButton.textContent = 'Memposting...';

                const formData = new FormData(this);

                // Panggil API upload
                fetch('/Sinergi/api/upload_postingan.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        createPostForm.reset(); // Kosongkan form
                        imagePreviewContainer.classList.add('hidden'); // Sembunyikan preview
                        
                        // Muat ulang feed agar postingan baru muncul di atas
                        loadPosts(); 
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan koneksi.');
                })
                .finally(() => {
                    // Kembalikan tombol ke normal
                    submitButton.disabled = false;
                    submitButton.textContent = 'Posting';
                });
            });
        }
    });
</script>

</body>

</html>