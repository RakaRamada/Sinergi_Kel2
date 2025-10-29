<?php 
require 'app/views/partials/header.php'; 
?>

<main class="col-span-6 border-r border-gray-200">
    <div
        class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <a href="index.php?page=dashboard" title="Kembali" class="p-2 rounded-full hover:bg-gray-200">
            <img src="/Sinergi/public/assets/icons/arrow-left.svg" alt="Kembali" class="w-6 h-6">
        </a>
        <div>
            <h2 class="text-xl font-bold">Postingan</h2>
        </div>
    </div>

    <div>
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center mb-3">
                <img src="<?php echo $post['AVATAR_URL_FIXED']; ?>" alt="Avatar" class="w-12 h-12 rounded-full mr-4">
                <div>
                    <p class="font-bold text-gray-800"><?php echo htmlspecialchars($post['NAMA_LENGKAP']); ?></p>
                    <p class="text-sm text-gray-500">@<?php echo htmlspecialchars($post['USERNAME']); ?> | Mahasiswa</p>
                </div>
            </div>
            
            <p class="text-gray-800 text-lg mb-4"><?php echo nl2br(htmlspecialchars($post['KONTEN'])); ?></p>
            
            <?php if (!empty($post['POST_IMAGE'])): ?>
            <img src="<?php echo $post['POST_IMAGE']; ?>" alt="Post Image"
                 class="rounded-lg w-full mb-4 border border-gray-200">
            <?php endif; ?>
            
            <p class="text-sm text-gray-500 mt-4"><?php echo $post['WAKTU_POSTING']; ?></p>
            
            <div class=" border-gray-200 py-2 flex items-center space-x-6 text-sm text-gray-500">
                <span>
                    <span class="font-bold text-black"><?php echo $post['TOTAL_LIKES']; ?></span> Suka
                </span>
                <span>
                    <span class="font-bold text-black"><?php echo $post['TOTAL_COMMENTS']; ?></span> Balasan
                </span>
            </div>
            <!-- Di sini Anda bisa tambahkan tombol Like/Comment (sama seperti di feed) -->
        </div>
    </div>

    <!-- 
      PERUBAHAN 2: FORM KOMENTAR
    -->
    <div class="p-4 border-b border-gray-200">
        <!-- Nanti kita akan buat ini jadi form AJAX -->
        <div class="flex items-start space-x-3">
            <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Anda" class="w-12 h-12 rounded-full">
            <textarea class="w-full border-gray-200 rounded-lg p-2 focus:ring-0 focus:border-blue-300" rows="1"
                placeholder="Posting balasan Anda..."></textarea>
        </div>
        <div class="flex justify-end mt-3">
            <button
                class="bg-black text-white font-bold py-2 px-6 rounded-full hover:bg-gray-600 cursor-pointer">Balas</button>
        </div>
    </div>

    <div id="comment-list-container">
        <?php if (empty($comments)): ?>
            <div class="p-4 text-center text-gray-500">
                Belum ada balasan.
            </div>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
            <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
                <div class="flex items-start space-x-3">
                    <img src="<?php echo $comment['AVATAR_URL_FIXED']; ?>" alt="Avatar" class="w-12 h-12 rounded-full">
                    <div>
                        <p class="font-bold text-gray-800">
                            <?php echo htmlspecialchars($comment['NAMA_LENGKAP']); ?>
                            <span class="text-sm font-normal text-gray-500">
                                @<?php echo htmlspecialchars($comment['USERNAME']); ?> | Mahasiswa • <?php echo $comment['WAKTU_KOMEN']; ?>
                            </span>
                        </p>
                        <p class="text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars($comment['ISI_KOMEN'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php 
require 'app/views/partials/sidebar_kanan.php'; 
require 'app/views/partials/footer.php'; 
?>