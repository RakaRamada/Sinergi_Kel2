    <?php 
    ?>

    <main class="col-span-6 border-r border-gray-200">
        <div
            class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
            <a href="javascript:history.back()" title="Kembali" class="p-2 rounded-full hover:bg-gray-200">
                <img src="/Sinergi/public/assets/icons/arrow-left.svg" alt="Kembali" class="w-6 h-6">
            </a>
            <div>
                <h2 class="text-xl font-bold">
                    <?= htmlspecialchars($user_profile['nama_lengkap'] ?? 'Profil Pengguna') ?>
                </h2>
                <p class="text-sm text-gray-500"><?= $user_profile['post_count'] ?? 0 ?> postingan</p>
            </div>
        </div>

        <div>
            <div class="relative">
                <img src="/Sinergi/public/assets/images/sore.jpg" alt="Header Profil" class="w-full h-48 object-cover">
                <img src="/Sinergi/public/assets/images/pp-sore.jpg" alt="Avatar"
                    class="w-32 h-32 rounded-full absolute -bottom-16 left-6 border-4 border-white object-cover">
            </div>

            <div class="text-right p-4 border-b border-gray-200 mt-16">
                <?php // Anda bisa tambahkan logika if($_SESSION['user_id'] == $user_profile['user_id']) di sini nanti ?>
                <button class="border border-gray-300 font-bold py-2 px-4 rounded-full hover:bg-gray-100 text-sm">Edit
                    profile</button>
            </div>

            <div class="p-4">
                <h2 class="text-2xl font-bold"><?= htmlspecialchars($user_profile['nama_lengkap'] ?? 'Nama Pengguna') ?>
                </h2>
                <p class="text-gray-500 mb-2">@<?= htmlspecialchars($user_profile['username'] ?? 'username') ?> |
                    <?= htmlspecialchars($user_profile['role_name'] ?? 'Role') ?></p>
                <p class="text-gray-700 mb-4">
                    <?= htmlspecialchars($user_profile['bio'] ?? 'Bio belum diatur.') // Ganti 'bio' dengan nama kolom bio Anda ?>
                </p>
                <div class="flex space-x-6 text-sm">
                    <a href="#" class="hover:underline"><span
                            class="font-bold text-black"><?= $user_profile['following_count'] ?? 0 ?></span> <span
                            class="text-gray-500">Mengikuti</span></a>
                    <a href="#" class="hover:underline"><span
                            class="font-bold text-black"><?= $user_profile['followers_count'] ?? 0 ?></span> <span
                            class="text-gray-500">Pengikut</span></a>
                </div>
            </div>

            <div class="flex border-b border-gray-200">
                <button
                    class="flex-1 text-center py-3 font-semibold border-b-2 border-blue-500 text-blue-500">Postingan</button>
                <button class="flex-1 text-center py-3 font-semibold text-gray-500 hover:bg-gray-100">Komunitas</button>
            </div>

            <div>
                <?php // foreach ($user_posts as $post): ?>
                <?php // endforeach; ?>

                <div class="border-b border-gray-200 p-4 hover:bg-gray-50">
                    <p class="text-gray-500">Daftar postingan oleh
                        <?= htmlspecialchars($user_profile['nama_lengkap'] ?? 'pengguna ini') ?> akan muncul di sini.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php 
    // 3. Panggil sidebar kanan
    require 'app/views/partials/sidebar_kanan.php'; 


    ?>