<?php 
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
                <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-12 h-12 rounded-full mr-4">
                <div>
                    <p class="font-bold text-gray-800">empanggawing</p>
                    <p class="text-sm text-gray-500">@hdpjkw99 | Mahasiswa</p>
                </div>
            </div>
            <p class="text-gray-800 text-lg mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit,
                sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.
                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod.</p>
            <img src="/Sinergi/public/assets/images/jkw.jpeg" alt="Post Image"
                class="rounded-lg w-full mb-4 border border-gray-200">
            <p class="text-sm text-gray-500 mt-4">2 jam yang lalu</p>
            <div class=" border-gray-200 py-2 flex items-center space-x-6 text-sm text-gray-500">
                <span>
                    <span class="font-bold text-black">1.3rb</span> Suka
                </span>
                <span><span class="font-bold text-black">76</span> Balasan</span>
            </div>
        </div>
    </div>

    <div class="p-4 border-b border-gray-200">
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

    <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
        <div class="flex items-start space-x-3">
            <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Dosen" class="w-12 h-12 rounded-full">
            <div>
                <p class="font-bold text-gray-800">Dr. Budi <span class="text-sm font-normal text-gray-500">@budi_dosen
                        | Dosen • 1 jam yang
                        lalu</span></p>
                <p class="text-gray-700 mt-1">Ini adalah contoh balasan dari seorang dosen. Pastikan untuk
                    memeriksa kembali referensi yang Anda gunakan.</p>
            </div>
        </div>
    </div>

    <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
        <div class="flex items-start space-x-3">
            <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Alumni" class="w-12 h-12 rounded-full">
            <div>
                <p class="font-bold text-gray-800">Siti Alumni <span
                        class="text-sm font-normal text-gray-500">@siti_alumni | Alumni '19 • 45 menit yang
                        lalu</span></p>
                <p class="text-gray-700 mt-1">Setuju dengan Pak Budi. Coba cek dokumentasi resmi Oracle
                    untuk OCI8, biasanya lebih lengkap.</p>
            </div>
        </div>
    </div>
</main>

<?php 
require 'app/views/partials/sidebar_kanan.php';  
?>