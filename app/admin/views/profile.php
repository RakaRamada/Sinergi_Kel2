<?php
// app/admin/views/profile.php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

<main class="flex-1 p-6 ml-0 lg:ml-20">
  <div class="max-w-xl mx-auto">
    <div class="bg-white border rounded-lg shadow-sm p-6">
      <div class="flex items-center gap-4 mb-4">
        <img src="/Sinergi/public/assets/images/default_avatar.png" class="w-14 h-14 rounded-full" alt="admin avatar">
        <div>
          <div class="font-semibold">Admin Sistem</div>
          <div class="text-sm text-gray-500">admin@sinergi.com</div>
        </div>
      </div>

      <p class="text-sm text-gray-600">Informasi singkat admin. Ini halaman profile sederhana dengan tombol logout (dummy).</p>

      <div class="mt-4">
        <button class="px-4 py-2 bg-red-600 text-white rounded">Logout</button>
      </div>
    </div>
  </div>
</main>

