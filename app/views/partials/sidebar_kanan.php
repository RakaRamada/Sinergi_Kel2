<style>
/* ====== SEMBUNYIKAN SCROLLBAR DI SEMUA BROWSER ====== */
.sidebar-kanan-container {
    overflow-y: scroll;
    /* pastikan bisa digulir */
    height: 100vh;
    /* tinggi penuh layar */
    scrollbar-width: none;
    /* Firefox */
    -ms-overflow-style: none;
    /* Edge lama & IE */
}

/* Chrome, Safari, Edge baru */
.sidebar-kanan-container::-webkit-scrollbar {
    width: 0;
    height: 0;
}
</style>

<div class="w-[300px] fixed right-5 top-20">
<aside class="sidebar-kanan-container col-span-4 p-6 pt-3 space-y-6 w-80 bg-transparent">
    <!-- Mungkin Anda Kenal -->
    <div class="border border-black bg-white p-4 rounded-lg">
        <h3 class="font-bold text-lg mb-3">Mungkin Anda Kenal</h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-10 h-10 rounded-full mr-3">
                    <span class="font-semibold text-sm">Asep Taufik Muh...</span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-10 h-10 rounded-full mr-3">
                    <span class="font-semibold text-sm">Raka Ramada</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Rekomendasi Komunitas -->
    <div class="border border-black bg-white p-4 rounded-lg">
        <h3 class="font-bold text-lg mb-3">Rekomendasi Komunitas</h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-10 h-10 rounded-full mr-3">
                    <span class="font-semibold text-sm">Timpa Teks.</span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-10 h-10 rounded-full mr-3">
                    <span class="font-semibold text-sm">Web Lanjut</span>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-10 h-10 rounded-full mr-3">
                    <span class="font-semibold text-sm">Grafika Komputer</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Ramai Dibicarakan -->
    <div class="border border-black bg-white p-4 rounded-lg">
        <h3 class="font-bold text-lg mb-3">Ramai Dibicarakan</h3>
        <div class="space-y-3">
            <div>
                <p class="font-bold text-sm text-gray-800">#TIKbutuhgedung</p>
                <p class="text-xs text-gray-500">2.189 postingan</p>
            </div>
        </div>
    </div>
</aside>
</div>