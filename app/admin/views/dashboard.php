<?php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

<main class="flex-1 p-6 ml-0 lg:ml-20">
  <div class="max-w-[1200px] mx-auto">

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Report Dashboard</h1>

      <div class="flex items-center gap-3">
        <div class="relative">
          <input id="admin-search" type="text" placeholder="Cari..." 
                 class="w-64 py-2 px-4 border rounded-full focus:outline-none focus:ring-1 focus:ring-blue-400" />
        </div>
        <select id="filter-status" class="px-4 py-2 bg-black text-white rounded-full cursor-pointer">
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="reviewed">Sedang Ditinjau</option>
          <option value="resolved">Selesai</option>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="bg-white border rounded-lg shadow-sm p-5">
        <div class="text-sm text-gray-600 font-medium">Total Laporan</div>
        <div class="text-3xl font-bold mt-1" id="stat-total">0</div>
      </div>
      <div class="bg-white border rounded-lg shadow-sm p-5">
        <div class="text-sm text-gray-600 font-medium">Pending</div>
        <div class="text-3xl font-bold text-red-500 mt-1" id="stat-pending">0</div>
      </div>
      <div class="bg-white border rounded-lg shadow-sm p-5">
        <div class="text-sm text-gray-600 font-medium">Selesai</div>
        <div class="text-3xl font-bold text-green-500 mt-1" id="stat-resolved">0</div>
      </div>
    </div>

    <div class="bg-white border rounded-lg shadow-sm p-4">
      <div class="overflow-x-auto table-scroll" style="min-height: 400px;">
        <table class="min-w-full text-sm">
          <thead class="bg-white border-b sticky top-0 z-10">
            <tr class="text-left text-xs text-gray-600 bg-gray-50">
              <th class="py-3 px-4 w-12">No.</th>
              <th class="py-3 px-4 w-64">Pelapor</th>
              <th class="py-3 px-4 w-32">Tanggal</th>
              <th class="py-3 px-4 w-32">Status</th>
              <th class="py-3 px-4">Jenis Laporan</th>
              <th class="py-3 px-4">Terlapor / Konten</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody id="reports-tbody" class="divide-y">
          </tbody>
        </table>
      </div>
      <div class="mt-4 text-center hidden" id="load-more-container">
        <button id="load-more" class="px-4 py-2 bg-gray-100 rounded-full hover:bg-gray-200 text-sm">
          Tampilkan lebih banyak ▾
        </button>
      </div>
    </div>
  </div>
</main>

<script>
let allReports = [];
let displayedCount = 10;

function loadReports() {
  const status = document.getElementById('filter-status').value;
  const tbody = document.getElementById('reports-tbody');
  
  tbody.innerHTML = `<tr><td colspan="7" class="py-12 text-center text-gray-500 animate-pulse">Sedang memuat data...</td></tr>`;
  
  // PERBAIKAN: URL API yang benar
  let url = '/Sinergi/index.php?page=admin-api-reports&limit=100';
  if (status) url += '&status=' + status;
  
  fetch(url)
    .then(res => {
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      return res.json();
    })
    .then(data => {
      if (data.status === 'success') {
        allReports = data.data;
        
        if (data.stats) {
            document.getElementById('stat-total').innerText = data.stats.total || 0;
            document.getElementById('stat-pending').innerText = data.stats.pending || 0;
            document.getElementById('stat-resolved').innerText = data.stats.resolved || 0;
        }

        displayedCount = 10;
        renderReports();
      } else {
        tbody.innerHTML = `<tr><td colspan="7" class="py-8 text-center text-red-500 font-medium">Error Server:<br>${data.message || 'Unknown error'}</td></tr>`;
      }
    })
    .catch(err => {
      console.error('Fetch error:', err);
      tbody.innerHTML = `<tr><td colspan="7" class="py-8 text-center text-red-500">Gagal terhubung ke server.<br><span class="text-xs text-gray-500">${err.message}</span></td></tr>`;
    });
}

function renderReports() {
  const tbody = document.getElementById('reports-tbody');
  
  if (allReports.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="py-8 text-center text-gray-500">Tidak ada laporan.</td></tr>`;
    document.getElementById('load-more-container').classList.add('hidden');
    return;
  }
  
  const searchVal = document.getElementById('admin-search').value.toLowerCase();
  
  const filtered = allReports.filter(r => {
      return !searchVal || 
             (r.REPORTER_USERNAME && r.REPORTER_USERNAME.toLowerCase().includes(searchVal)) ||
             (r.REASON && r.REASON.toLowerCase().includes(searchVal));
  });

  if (filtered.length === 0) {
     tbody.innerHTML = `<tr><td colspan="7" class="py-8 text-center text-gray-500">Pencarian tidak ditemukan.</td></tr>`;
     return;
  }

  const reportsToShow = filtered.slice(0, displayedCount);
  tbody.innerHTML = '';
  
  reportsToShow.forEach((report, index) => {
    const statusColor = {
      'pending': 'bg-red-100 text-red-700',
      'reviewed': 'bg-yellow-100 text-yellow-700',
      'resolved': 'bg-green-100 text-green-700'
    }[report.STATUS] || 'bg-gray-100 text-gray-600';

    // PERBAIKAN: Link detail report ke halaman admin
    const detailLink = `/Sinergi/index.php?page=admin-detail-report&id=${report.REPORT_ID}`;

    let banButton = '';
    if (report.POST_OWNER_ID) {
        if (report.IS_OWNER_BANNED) {
            banButton = `<span class="block w-full text-left px-4 py-2 text-xs text-gray-400 italic bg-gray-50">🚫 User sudah dibanned</span>`;
        } else {
            banButton = `
                <button onclick="banUser(${report.REPORT_ID}, ${report.POST_OWNER_ID}, '${report.POST_OWNER_USERNAME}')" 
                        class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-700 text-sm font-semibold border-t">
                  ⛔ Banned @${report.POST_OWNER_USERNAME}
                </button>`;
        }
    }

    const row = `
      <tr class="hover:bg-gray-50 transition-colors">
        <td class="py-4 px-4 align-top">${index + 1}.</td>
        <td class="py-4 px-4 align-top">
          <div class="flex items-center gap-3">
            <img src="${report.REPORTER_AVATAR_FIXED}" class="w-9 h-9 rounded-full object-cover border">
            <div>
              <div class="font-medium text-sm text-gray-900">${report.REPORTER_NAMA}</div>
              <div class="text-xs text-gray-500">@${report.REPORTER_USERNAME}</div>
            </div>
          </div>
        </td>
        <td class="py-4 px-4 align-top">
          <div class="text-sm text-gray-700">${report.TANGGAL_FORMAT}</div>
        </td>
        <td class="py-4 px-4 align-top">
          <span class="${statusColor} px-2 py-1 rounded-md text-xs font-semibold uppercase tracking-wide">
            ${report.STATUS}
          </span>
        </td>
        <td class="py-4 px-4 align-top">
          <div class="text-sm text-gray-700">${report.REASON}</div>
        </td>
        <td class="py-4 px-4 align-top">
           <div class="text-xs text-gray-500 mb-1">Terlapor: <span class="font-semibold">@${report.POST_OWNER_USERNAME || 'Unknown'}</span></div>
           ${report.POST_ID 
             ? `<a href="${detailLink}" class="text-blue-600 hover:underline text-sm font-medium">Lihat Detail Laporan →</a>` 
             : '<span class="text-gray-400 text-sm italic">Postingan dihapus</span>'}
        </td>
        <td class="py-4 px-4 align-top text-right relative">
          <button class="action-btn p-2 rounded-full hover:bg-gray-200" onclick="toggleMenu(this)">
            <img src="/Sinergi/public/assets/icons/dot.svg" alt="..." class="w-5 h-5">
          </button>
          
          <div class="action-menu hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-xl z-50 overflow-hidden text-left">
            ${report.POST_ID ? `
              <button onclick="deletePost(${report.REPORT_ID})" 
                      class="w-full text-left px-4 py-3 hover:bg-orange-50 text-orange-600 text-sm font-medium">
                🗑️ Hapus Postingan
              </button>
            ` : ''}
            ${banButton}
            <div class="border-t border-gray-100"></div>
            <button onclick="markStatus(${report.REPORT_ID}, 'resolved')" class="w-full text-left px-4 py-2 hover:bg-green-50 text-green-700 text-sm">✓ Tandai Selesai</button>
            <button onclick="markStatus(${report.REPORT_ID}, 'rejected')" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-gray-600 text-sm">✗ Tolak</button>
          </div>
        </td>
      </tr>`;
    tbody.innerHTML += row;
  });

  const loadMoreContainer = document.getElementById('load-more-container');
  if (displayedCount < filtered.length) {
    loadMoreContainer.classList.remove('hidden');
  } else {
    loadMoreContainer.classList.add('hidden');
  }
}

function deletePost(reportId) {
  if (!confirm('Hapus postingan ini?')) return;
  sendAction('delete_post', { report_id: reportId });
}

function banUser(reportId, userId, username) {
  if (!confirm(`Yakin ingin BANNED @${username}?`)) return;
  sendAction('ban_user', { report_id: reportId, user_id: userId });
}

function markStatus(reportId, status) {
    if (!confirm('Update status laporan?')) return;
    sendAction('mark_' + status, { report_id: reportId });
}

function sendAction(actionType, data) {
    const formData = new FormData();
    formData.append('action', actionType);
    for (const key in data) formData.append(key, data[key]);

    // PERBAIKAN: URL API yang benar
    fetch('/Sinergi/index.php?page=admin-api-process', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(resp => {
        if (resp.status === 'success' || resp.status === true) {
            alert('✓ Berhasil');
            loadReports();
        } else {
            alert('Gagal: ' + resp.message);
        }
    })
    .catch(err => alert('Error: ' + err.message));
}

function toggleMenu(btn) {
    document.querySelectorAll('.action-menu').forEach(el => el.classList.add('hidden'));
    btn.nextElementSibling.classList.remove('hidden');
    event.stopPropagation();
}

window.onclick = function(event) {
    if (!event.target.closest('.action-btn')) {
        document.querySelectorAll('.action-menu').forEach(el => el.classList.add('hidden'));
    }
};

document.getElementById('filter-status').addEventListener('change', loadReports);
document.getElementById('admin-search').addEventListener('input', () => renderReports());
document.getElementById('load-more').addEventListener('click', () => { displayedCount += 10; renderReports(); });

// Load saat halaman dibuka
loadReports();
</script>