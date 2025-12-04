<!-- app/views/dashboard.php -->
<?php 
// Ambil avatar user dari session untuk form postingan
$user_avatar = $_SESSION['avatar_url'] ?? '/Sinergi/public/assets/images/default_avatar.png'; 
?>


<main class="col-span-6 border-r border-gray-200">
    <?php
    $current_view = $_GET['view'] ?? 'teman';
    ?>
    <div class="flex border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <a href="index.php?page=dashboard&view=teman"
            class="flex-1 text-center py-3 font-semibold hover:bg-gray-100 <?php if ($current_view === 'teman') echo 'border-b-2 border-black text-black'; else echo 'text-gray-500'; ?>">
            Home
        </a>
    </div>

    <div>
        <?php if ($current_view === 'teman'): ?>
        <div class="bg-white border-x border-t border-b border-gray-200">

            <form id="create-post-form" method="POST" enctype="multipart/form-data">
                <div class="flex items-start space-x-3 p-4">
                    <textarea id="post-content-input" name="konten"
                        class="w-full border-0 focus:ring-0 resize-none p-2 text-lg" rows="2"
                        placeholder="Apa yang ingin anda diskusikan?"></textarea>
                </div>

                <div id="image-preview-container" class="hidden p-4 pt-0 relative">
                    <img id="image-preview" src="#" alt="Image Preview"
                        class="rounded-lg max-h-60 w-auto border border-gray-200">
                    <button type="button" id="remove-image-btn"
                        class="absolute top-2 left-6 bg-black bg-opacity-50 text-white rounded-full p-1 hover:bg-opacity-70">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="flex justify-between items-center mt-2 p-4 border-t border-gray-200">
                    <div class="flex items-center space-x-2 text-gray-500">
                        <label for="post-image-input" title="Gambar"
                            class="p-2 rounded-full cursor-pointer hover:bg-gray-100 text-blue-500 transition-colors">
                            <img src="/Sinergi/public/assets/icons/image.svg" alt="Gambar" class="w-6 h-6">

                            <input type="file" id="post-image-input" name="post_image" accept="image/*" class="hidden">
                        </label>
                    </div>

                    <button type="submit" id="submit-post-button"
                        class="bg-black text-white font-bold py-2 px-6 rounded-full hover:bg-gray-800 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        Posting
                    </button>
                </div>
            </form>
        </div>

        <div id="post-feed-container">
            <div class="p-4 text-center text-gray-500 border-b border-gray-200">
                Memuat postingan...
            </div>
        </div>

        <?php else: ?>
        <div class="bg-white border-x border-t border-gray-200 p-4">
            <h2 class="text-xl font-bold">Halaman Komunitas</h2>
            <p>Konten komunitas akan dimuat di sini...</p>
        </div>
        <?php endif; ?>

    </div>
</main>

<?php 
// Sidebar Kanan
require __DIR__ . '/partials/sidebar_kanan.php';

// Footer & Script JS (postingan.php yang berisi logika AJAX tadi)
require 'app/views/partials/postingan.php'; 
?>