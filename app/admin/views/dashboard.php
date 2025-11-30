<?php

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

<main class="flex-1 p-6 ml-0 lg:ml-20">
  <div class="max-w-[1200px] mx-auto">

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Report</h1>

      <div class="flex items-center gap-3">
        <div class="relative">
          <input id="admin-search" type="text" placeholder="Cari..." class="w-64 py-2 px-4 border rounded-full focus:outline-none focus:ring-1 focus:ring-blue-400" />
          <button id="search-button" class="absolute right-1 top-1/2 -translate-y-1/2 px-3 py-1">
          </button>
        </div>
        <button id="filter-btn" class="px-3 py-2 bg-black text-white rounded-full">Filter</button>
      </div>
    </div>

    <!-- Table card -->
    <div class="bg-white border rounded-lg shadow-sm p-4">
      <div class="overflow-x-auto table-scroll">
        <table class="min-w-full text-sm">
          <thead class="bg-white">
            <tr class="text-left text-xs text-gray-600">
              <th class="py-3 pr-6 w-12">No.</th>
              <th class="py-3 pr-6 w-48">Pelapor</th>
              <th class="py-3 pr-6">Tanggal</th>
              <th class="py-3 pr-6">Status</th>
              <th class="py-3 pr-6">Jenis Laporan</th>
              <th class="py-3 pr-6">Konten yang dilaporkan</th>
              <th class="py-3 pr-6 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody id="reports-tbody" class="divide-y">

            <!-- Example rows (dummy). Backend nanti akan render rows here. -->
            <?php for ($i = 1; $i <= 10; $i++): ?>
            <tr class="hover:bg-gray-50">
              <td class="py-4 pr-6 align-top">1.</td>

              <td class="py-4 pr-6 align-top">
                <a href="#" class="text-blue-600 hover:underline">@User1231290</a>
              </td>

              <td class="py-4 pr-6 align-top">30 Des 2025</td>

              <td class="py-4 pr-6 align-top">
                <?php if ($i % 3 == 0): ?>
                  <span class="text-green-600 font-medium">Selesai</span>
                <?php else: ?>
                  <span class="text-red-500 font-medium">Pending</span>
                <?php endif; ?>
              </td>

              <td class="py-4 pr-6 align-top">Konten Kekerasan</td>

              <td class="py-4 pr-6 align-top">
                <!-- link to detail; uses plain file link for now -->
                <a href="/Sinergi/app/admin/views/detail_report.php?post_id=<?= $i ?>" class="text-blue-600 hover:underline">Postingan →</a>
              </td>

              <td class="py-4 pr-6 align-top text-right">
                <!-- actions button (3 dots) -->
                <div class="relative inline-block text-left">
                  <button class="action-more inline-flex items-center justify-center w-10 h-8 rounded-full hover:bg-gray-100" aria-expanded="false" data-id="<?= $i ?>">
                    <img src="/Sinergi/public/assets/icons/dot.svg" alt="..." class="w-5 h-5">
                  </button>

                  <!-- dropdown (hidden by default) -->
                  <div class="action-menu hidden absolute right-0 mt-2 w-48 bg-white border rounded-md shadow-lg z-50">
                    <button data-action="view" data-id="<?= $i ?>" class="w-full text-left px-4 py-2 hover:bg-gray-50">Lihat Postingan</button>
                    <button data-action="delete" data-id="<?= $i ?>" class="w-full text-left px-4 py-2 hover:bg-gray-50 text-red-600">Hapus Postingan</button>
                    <button data-action="block" data-id="<?= $i ?>" class="w-full text-left px-4 py-2 hover:bg-gray-50">Blokir User</button>
                    <button data-action="mark_done" data-id="<?= $i ?>" class="w-full text-left px-4 py-2 hover:bg-gray-50">Tandai Selesai</button>
                    <button data-action="mark_pending" data-id="<?= $i ?>" class="w-full text-left px-4 py-2 hover:bg-gray-50">Tandai Pending</button>
                  </div>
                </div>
              </td>
            </tr>
            <?php endfor; ?>

          </tbody>
        </table>
      </div>

      <!-- show more -->
      <div class="mt-4 text-center">
        <button id="load-more" class="px-4 py-2 bg-black text-white rounded-full">Tampilkan lebih banyak ▾</button>
      </div>
    </div>
  </div>
</main>

<!-- small inline script for dropdown and actions (frontend-only) -->
<script>
  // toggle action dropdown
  document.querySelectorAll('.action-more').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const parent = btn.parentElement;
      const menu = parent.querySelector('.action-menu');
      // hide any other open menus
      document.querySelectorAll('.action-menu').forEach(m => {
        if (m !== menu) m.classList.add('hidden');
      });
      menu.classList.toggle('hidden');
    });
  });

  // global click to close menus
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.action-more') && !e.target.closest('.action-menu')) {
      document.querySelectorAll('.action-menu').forEach(m => m.classList.add('hidden'));
    }
  });

  // handle action clicks (dummy handlers for frontend preview)
  document.querySelectorAll('.action-menu button').forEach(b => {
    b.addEventListener('click', (e) => {
      const action = b.dataset.action;
      const id = b.dataset.id;
      if (action === 'view') {
        window.location.href = '/Sinergi/app/admin/views/detail_report.php?post_id=' + id;
      } else if (action === 'delete') {
        if (confirm('Hapus postingan #' + id + ' ?')) {
          alert('(Dummy) Post #' + id + ' dihapus. Integrasikan API untuk eksekusi nyata.');
          // TODO: call backend API to delete
        }
      } else if (action === 'block') {
        if (confirm('Blokir user pembuat postingan #' + id + ' ?')) {
          alert('(Dummy) User diblokir. Integrasikan API untuk eksekusi nyata.');
        }
      } else if (action === 'mark_done') {
        alert('(Dummy) Laporan ditandai selesai.');
      } else if (action === 'mark_pending') {
        alert('(Dummy) Laporan ditandai pending.');
      }
      // close menus
      document.querySelectorAll('.action-menu').forEach(m => m.classList.add('hidden'));
    });
  });
</script>
