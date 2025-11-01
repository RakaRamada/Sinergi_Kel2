<?php 
?>

<main class="col-span-6 border-r border-gray-200">
    <?php
    // Logika PHP untuk menentukan tampilan mana yang aktif dari URL
    $current_view = $_GET['view'] ?? 'teman';
    ?>

    <div class="flex border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <a href="index.php?page=dashboard&view=teman"
            class="flex-1 text-center py-3 font-semibold hover:bg-gray-100 <?php if ($current_view === 'teman') echo 'border-b-2 border-black text-black'; else echo 'text-gray-500'; ?>">
            Teman
        </a>
        <a href="index.php?page=dashboard&view=komunitas"
            class="flex-1 text-center py-3 font-semibold hover:bg-gray-100 <?php if ($current_view === 'komunitas') echo 'border-b-2 border-black text-black'; else echo 'text-gray-500'; ?>">
            Komunitas
        </a>
    </div>

    <div>
        <?php if ($current_view === 'teman'): ?>

        <div class="bg-white border-x border-t border-b border-gray-200">
            <div class="flex items-start space-x-3 p-4">
                <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Anda" class="w-12 h-12 rounded-full">
                <textarea class="w-full border-0 focus:ring-0 resize-none p-2" rows="2"
                    placeholder="Apa yang ingin Anda diskusikan?"></textarea>
            </div>

            <div class="flex justify-between items-center mt-4 p-4 border-t border-b border-gray-200">
                <div class="flex items-center space-x-2 text-gray-500">

                    <button title="Gambar" class="p-2 rounded-full cursor-pointer hover:bg-gray-100">
                        <img src="/Sinergi/public/assets/icons/image.svg" alt="Gambar" class="w-6 h-6">
                    </button>

                    <button title="GIF" class="p-2 rounded-full cursor-pointer hover:bg-gray-100 font-bold text-sm">
                        GIF
                    </button>

                    <button title="Emoji" class="p-2 rounded-full cursor-pointer hover:bg-gray-100">
                        <img src="/Sinergi/public/assets/icons/emoji.svg" alt="Emoji" class="w-6 h-6">
                    </button>

                </div>
                <button class="bg-black text-white font-bold py-2 px-6 rounded-full hover:bg-gray-800 cursor-pointer">
                    Posting
                </button>
            </div>

            <div class="bg-white border-b border-gray-200 mb-6">
                <div class="p-4">
                    <div class="flex items-center mb-3">
                        <a href="index.php?page=profile">
                            <img src="/Sinergi/public/assets/images/user.png" alt="Avatar"
                                class="w-12 h-12 rounded-full mr-4">
                        </a>
                        <div>
                            <a href="index.php?page=profile"
                                class="font-bold text-gray-800 hover:underline">empanggawing</a>
                            <p class="text-sm font-normal text-gray-500">@hdpjkw99 | Mahasiswa</p>
                            <p class="text-xs text-gray-500">2 jam yang lalu</p>
                        </div>
                    </div>

                    <a href="index.php?page=post-detail" class="block">
                        <p class="text-gray-700 mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit,
                            sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
                            veniam...</p>
                        <img src="/Sinergi/public/assets/images/jkw.jpeg" alt="Post Image" class="rounded-lg w-full">
                    </a>
                </div>

                <div class="p-4 flex items-center justify-center text-gray-500 space-x-8 border-t border-gray-200">
                    <button class="flex items-center space-x-2 hover:text-red-500 cursor-pointer">
                        <img src="/Sinergi/public/assets/icons/heart.svg" alt="Suka" class="w-6 h-6">
                        <span>1.3rb</span>
                    </button>
                    <button data-post-id="1"
                        class="comment-button flex items-center space-x-2 hover:text-blue-500 cursor-pointer">
                        <img src="/Sinergi/public/assets/icons/comment.svg" alt="Komentar" class="w-7 h-7">
                        <span>76</span>
                    </button>
                    <button class="flex items-center space-x-2 hover:text-green-500 cursor-pointer">
                        <img src="/Sinergi/public/assets/icons/share.svg" alt="Bagikan" class="w-6 h-6">
                        <span>200</span>
                    </button>
                </div>

                <div id="comment-section-1" class="hidden p-4 border-t border-gray-200">
                    <div class="flex items-start space-x-3">
                        <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Anda"
                            class="w-10 h-10 rounded-full">
                        <textarea class="w-full border-gray-200 rounded-lg p-2 focus:ring-0 focus:border-blue-300"
                            rows="1" placeholder="Tulis balasan Anda..."></textarea>
                    </div>
                    <div class="flex justify-end mt-2">
                        <button
                            class="bg-black text-white font-bold py-1 px-4 rounded-full text-sm hover:bg-gray-600 cursor-pointer">Balas</button>
                    </div>
                </div>
            </div>

            <!-- post 2 -->
            <div class="bg-white border-b border-gray-200  mb-6">
                <div class="p-4">
                    <div class="flex items-center mb-3">
                        <img src="/Sinergi/public/assets/images/user.png?u=1" alt="Avatar"
                            class="w-12 h-12 rounded-full mr-4">
                        <div>
                            <p class="font-bold text-gray-800">fafafufu <span
                                    class="text-sm font-normal text-gray-500">@fafafufu69 | Mahasewa</span></p>
                            <p class="text-xs text-gray-500">2 jam yang lalu</p>
                        </div>
                    </div>
                    <p class="text-gray-700 mb-3 pl-16">Lorem ipsum dolor sit amet, consectetur adipiscing elit,
                        sed
                        do
                        eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam...
                    </p>
                </div>
                <img src="/Sinergi/public/assets/images/jkw-metal.jpg" alt=" Post Image"
                    class=" ml-20 items-center rounded w-lg">
                <div class="p-4 flex items-center ml-18 text-gray-500 space-x-8 border-gray-200">
                    <button class="flex items-center space-x-1 hover:text-red-500">
                        <img src="/Sinergi/public/assets/icons/heart.svg" alt="Suka" class="w-6 h-6">
                        <span>1.3rb</span>
                    </button>
                    <button class="flex items-center space-x-1 hover:text-blue-500">
                        <img src="/Sinergi/public/assets/icons/comment.svg" alt="Komentar" class="w-7 h-7">
                        <span>76</span>
                    </button>
                    <button class="flex items-center space-x-1 hover:text-green-500">
                        <img src="/Sinergi/public/assets/icons/share.svg" alt="Bagikan" class="w-6 h-6">
                        <span>200</span>
                    </button>
                </div>
            </div>

            <?php else: ?>

            <div class="bg-white border-x border-t border-b border-gray-200">
                <div class="flex items-start space-x-3 p-4">
                    <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Anda" class="w-12 h-12 rounded-full">
                    <textarea class="w-full border-0 focus:ring-0 resize-none p-2" rows="2"
                        placeholder="Apa yang ingin Anda diskusikan?"></textarea>
                </div>

                <div class="flex justify-between items-center mt-4 p-4 border-t border-b border-gray-200">
                    <div class="flex items-center space-x-2 text-gray-500">

                        <button title="Gambar" class="p-2 rounded-full hover:bg-gray-100">
                            <img src="/Sinergi/public/assets/icons/image.svg" alt="Gambar" class="w-6 h-6">
                        </button>

                        <button title="GIF" class="p-2 rounded-full hover:bg-gray-100 font-bold text-sm">
                            GIF
                        </button>

                        <button title="Emoji" class="p-2 rounded-full hover:bg-gray-100">
                            <img src="/Sinergi/public/assets/icons/emoji.svg" alt="Emoji" class="w-6 h-6">
                        </button>

                    </div>
                    <button class="bg-black text-white font-bold py-2 px-6 rounded-full hover:bg-gray-800">
                        Posting
                    </button>
                </div>

                <div class="bg-white border-b border-gray-200 mb-6">
                    <div class="p-4">
                        <div class="flex items-center mb-3">
                            <a href="index.php?page=profile">
                                <img src="/Sinergi/public/assets/images/user.png" alt="Avatar"
                                    class="w-12 h-12 rounded-full mr-4">
                            </a>
                            <div>
                                <a href="index.php?page=profile"
                                    class="font-bold text-gray-800 hover:underline">empanggawing</a>
                                <p class="text-sm font-normal text-gray-500">@hdpjkw99 | Mahasiswa</p>
                                <p class="text-xs text-gray-500">2 jam yang lalu</p>
                            </div>
                        </div>

                        <a href="index.php?page=post-detail" class="block">
                            <p class="text-gray-700 mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit,
                                sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
                                veniam...</p>
                            <img src="/Sinergi/public/assets/images/sore.jpg" alt="Post Image"
                                class="rounded-lg w-full">
                        </a>
                    </div>

                    <div class="p-4 flex items-center justify-center text-gray-500 space-x-8 border-t border-gray-200">
                        <button class="flex items-center space-x-2 hover:text-red-500 cursor-pointer">
                            <img src="/Sinergi/public/assets/icons/heart.svg" alt="Suka" class="w-6 h-6">
                            <span>1.3rb</span>
                        </button>
                        <button data-post-id="1"
                            class="comment-button flex items-center space-x-2 hover:text-blue-500 cursor-pointer">
                            <img src="/Sinergi/public/assets/icons/comment.svg" alt="Komentar" class="w-7 h-7">
                            <span>76</span>
                        </button>
                        <button class="flex items-center space-x-2 hover:text-green-500 cursor-pointer">
                            <img src="/Sinergi/public/assets/icons/share.svg" alt="Bagikan" class="w-6 h-6">
                            <span>200</span>
                        </button>
                    </div>

                    <div id="comment-section-1" class="hidden p-4 border-t border-gray-200">
                        <div class="flex items-start space-x-3">
                            <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Anda"
                                class="w-10 h-10 rounded-full">
                            <textarea class="w-full border-gray-200 rounded-lg p-2 focus:ring-0 focus:border-blue-300"
                                rows="1" placeholder="Tulis balasan Anda..."></textarea>
                        </div>
                        <div class="flex justify-end mt-2">
                            <button
                                class="bg-black text-white font-bold py-1 px-4 rounded-full text-sm hover:bg-gray-600 cursor-pointer">Balas</button>
                        </div>
                    </div>
                </div>

                <!-- post 2 -->
                <div class="bg-white border-b border-gray-200  mb-6">
                    <div class="p-4">
                        <div class="flex items-center mb-3">
                            <img src="/Sinergi/public/assets/images/user.png?u=1" alt="Avatar"
                                class="w-12 h-12 rounded-full mr-4">
                            <div>
                                <p class="font-bold text-gray-800">fafafufu <span
                                        class="text-sm font-normal text-gray-500">@fafafufu69 | Mahasewa</span></p>
                                <p class="text-xs text-gray-500">2 jam yang lalu</p>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-3 pl-16">Lorem ipsum dolor sit amet, consectetur adipiscing elit,
                            sed
                            do
                            eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam...
                        </p>
                    </div>
                    <img src="/Sinergi/public/assets/images/jkw-metal.jpg" alt=" Post Image"
                        class=" ml-20 items-center rounded w-lg">
                    <div class="p-4 flex items-center ml-18 text-gray-500 space-x-8 border-gray-200">
                        <button class="flex items-center space-x-1 hover:text-red-500">
                            <img src="/Sinergi/public/assets/icons/heart.svg" alt="Suka" class="w-6 h-6">
                            <span>1.3rb</span>
                        </button>
                        <button class="flex items-center space-x-1 hover:text-blue-500">
                            <img src="/Sinergi/public/assets/icons/comment.svg" alt="Komentar" class="w-7 h-7">
                            <span>76</span>
                        </button>
                        <button class="flex items-center space-x-1 hover:text-green-500">
                            <img src="/Sinergi/public/assets/icons/share.svg" alt="Bagikan" class="w-6 h-6">
                            <span>200</span>
                        </button>
                    </div>
                </div>

                <?php endif; ?>
            </div>
</main>

<?php 
// 3. Memanggil sidebar kanan secara dinamis
if (($_GET['view'] ?? 'teman') === 'komunitas') {
    // Jika di halaman komunitas, panggil sidebar komunitas
    require 'app/views/partials/sidebar_komunitas.php';
} else {
    // Jika di halaman teman (default), panggil sidebar teman
    require 'app/views/partials/sidebar_teman.php';
}

?>