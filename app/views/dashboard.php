<?php 
// 1. Memanggil header (membuka HTML, body, container, grid, dan menampilkan sidebar kiri)
require 'app/views/partials/header.php'; 

// Ambil avatar user dari session untuk form postingan
$user_avatar = $_SESSION['avatar_url'] ?? '/Sinergi/public/assets/images/default_avatar.png'; 
?>

<main class="col-span-6 border-r border-gray-200">
    <?php
    // Logika PHP untuk menentukan tampilan mana yang aktif dari URL
    // Logika ini dikembalikan sesuai permintaan Anda
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
                <form id="create-post-form" enctype="multipart/form-data">
                    <div class="flex items-start space-x-3 p-4">
                        
                        <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar anda" class="w-12 h-12 rounded-full">
                    
                        <textarea id="post-content-input" name="post_content"
                        class="w-full border-0 focus:ring-0 resize-none p-2"
                        rows="2" placeholder="Apa yang ingin anda diskusikan?"></textarea>
                    </div>

                    <div id="image-preview-container" class="hidden p-4 pt-0">
                        <img id="image-preview" src="#" alt="Image Preview" class="rounded-lg max-h-60 w-auto border border-gray-200">
                    </div>

                    <div class="flex justify-between items-center mt-4 p-4 border-t border-b border-gray-200">
                        <div class="flex items-center space-x-2 text-gray-500">
                                <label for="post-image-input" title="Gambar" class="p-2 rounded-full cursor-pointer hover:bg-gray-100">
                                <img src="/Sinergi/public/assets/icons/image.svg" alt="Gambar" class="w-6 h-6">
                            </label>
                            <input type="file" id="post-image-input" name="post_image" accept="image/*" class="hidden">
                            <button title="GIF" type="button" class="p-2 rounded-full cursor-pointer hover:bg-gray-100 font-bold text-sm">GIF</button>
                            <button title="Emoji" type="button" class="p-2 rounded-full cursor-pointer hover:bg-gray-100">
                                <img src="/Sinergi/public/assets/icons/emoji.svg" alt="Emoji" class="w-6 h-6">
                            </button> 
                        </div>
                        <button type="submit" id="submit-post-button"
                        class="bg-black text-white font-bold py-2 px-6 rounded-full hover:bg-gray-800 cursor-pointer">
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
// 3. Memanggil sidebar kanan secara dinamis (DIKEMBALIKAN SESUAI PERMINTAAN)
if (($_GET['view'] ?? 'teman') === 'komunitas') {
    // Jika di halaman komunitas, panggil sidebar komunitas
    require 'app/views/partials/sidebar_komunitas.php';
} else {
    // Jika di halaman teman (default), panggil sidebar teman
    require 'app/views/partials/sidebar_teman.php';
}

// 4. Memanggil footer (berisi skrip JS dan tag penutup HTML)
require 'app/views/partials/footer.php'; 
?>