<?php
// app/admin/views/detail_report.php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

<main class="flex-1 p-6 ml-0 lg:ml-20">
  <div class="max-w-4xl mx-auto">
    <div class="mb-4">
      <a href="/Sinergi/app/admin/views/dashboard.php" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:underline">
        ← Kembali ke daftar laporan
      </a>
    </div>

    <div class="bg-white border rounded-lg shadow-sm p-6 mb-6">
      <!-- post header -->
      <div class="flex items-start gap-4 mb-4">
        <img src="/Sinergi/public/assets/images/default_avatar.png" alt="avatar" class="w-12 h-12 rounded-full">
        <div>
          <div class="font-semibold">@User1234345 <span class="text-xs text-gray-500">| Mahasiswa</span></div>
          <div class="text-xs text-gray-500">2 jam yang lalu</div>
        </div>
      </div>

      <!-- post content -->
      <div class="mb-4 text-gray-700">
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua...</p>
      </div>

      <!-- post image -->
      <div class="mb-4">
        <img src="/Sinergi/public/assets/images/default.jpg" alt="post" class="w-full rounded-lg border">
      </div>

      <!-- post stats -->
      <div class="flex items-center justify-between text-sm text-gray-600">
        <div class="flex items-center gap-6">
          <div>❤️ 1,3rb</div>
          <div>💬 76</div>
          <div>🔁 200</div>
        </div>
        <div class="text-xs text-gray-500">2 jam yang lalu</div>
      </div>
    </div>

    <!-- report info -->
    <div class="bg-white border rounded-lg shadow-sm p-6">
      <h2 class="text-lg font-semibold mb-3">Informasi Laporan</h2>
      <p><strong>Pelapor:</strong> @UserPelapor</p>
      <p><strong>Jenis Laporan:</strong> Konten Kekerasan</p>
      <p><strong>Deskripsi:</strong> Mengandung gambar yang tidak pantas.</p>
      <p><strong>Status:</strong> <span class="text-red-500 font-medium">Pending</span></p>

      <div class="mt-4 flex gap-3">
        <button class="px-4 py-2 bg-red-600 text-white rounded" id="btn-delete">Hapus Postingan</button>
        <button class="px-4 py-2 bg-yellow-500 text-black rounded" id="btn-mark-done">Tandai Selesai</button>
        <button class="px-4 py-2 border rounded" id="btn-block">Blokir User</button>
      </div>
    </div>
  </div>
</main>

<script>
  document.getElementById('btn-delete').addEventListener('click', function(){
    if (confirm('Hapus postingan ini (dummy)?')) {
      alert('Postingan dihapus (dummy). Hubungkan API untuk eksekusi nyata.');
    }
  });
  document.getElementById('btn-mark-done').addEventListener('click', function(){
    alert('Laporan ditandai selesai (dummy).');
  });
  document.getElementById('btn-block').addEventListener('click', function(){
    if (confirm('Blokir user ini (dummy)?')) {
      alert('User diblokir (dummy).');
    }
  });
</script>

