<?php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

<main class="flex-1 p-6 ml-0 lg:ml-20 bg-gray-50 min-h-screen">
    <div class="max-w-[1200px] mx-auto">

        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Report Dashboard</h1>
                <p class="text-gray-500 text-sm">Kelola laporan konten dan pengguna</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <input id="admin-search" type="text" placeholder="Cari pelapor/alasan..."
                        class="w-64 py-2 px-4 pl-10 bg-white border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm shadow-sm" />
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <select id="filter-status"
                    class="px-4 py-2 bg-black text-white text-sm rounded-full cursor-pointer hover:bg-gray-800 transition-colors shadow-md outline-none">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="reviewed">Sedang Ditinjau</option>
                    <option value="resolved">Selesai</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Total Laporan</div>
                    <div class="text-3xl font-bold text-gray-800" id="stat-total">0</div>
                </div>
                <div class="p-3 bg-blue-50 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 21v-8a2 2 0 012-2h14a2 2 0 012 2v8M3 13l4-8h10l4 8M12 5v13"></path>
                    </svg>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Pending</div>
                    <div class="text-3xl font-bold text-red-500" id="stat-pending">0</div>
                </div>
                <div class="p-3 bg-red-50 rounded-full">
                    <div
                        class="w-6 h-6 rounded-full bg-red-200 flex items-center justify-center text-xs font-bold text-red-600">
                        !</div>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Selesai</div>
                    <div class="text-3xl font-bold text-green-500" id="stat-resolved">0</div>
                </div>
                <div class="p-3 bg-green-50 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden flex flex-col min-h-[500px]">
            <div class="overflow-x-auto flex-1">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <th class="py-4 px-6 w-16 text-center">No.</th>
                            <th class="py-4 px-6 w-64">Pelapor</th>
                            <th class="py-4 px-6">Tanggal</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6">Jenis Laporan</th>
                            <th class="py-4 px-6">Konten / Terlapor</th>
                            <th class="py-4 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="reports-tbody" class="divide-y divide-gray-100">
                    </tbody>
                </table>
            </div>

            <div id="pagination-container"
                class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-white hidden">
                <div class="text-sm text-gray-500">
                    Menampilkan <span id="page-info-start" class="font-bold text-gray-800">0</span> - <span
                        id="page-info-end" class="font-bold text-gray-800">0</span> dari <span id="page-info-total"
                        class="font-bold text-gray-800">0</span>
                </div>
                <div class="flex items-center gap-2">
                    <button id="btn-prev"
                        class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium">Sebelumnya</button>
                    <div id="page-numbers" class="flex items-center gap-1"></div>
                    <button id="btn-next"
                        class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium">Selanjutnya</button>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
// Variabel Global
let currentPage = 1;
let totalPages = 1;
let currentLimit = 10;

// 1. Fungsi Load Data dari API
function loadReports(page = 1) {
    currentPage = page;
    const status = document.getElementById('filter-status').value;
    const tbody = document.getElementById('reports-tbody');
    const paginationContainer = document.getElementById('pagination-container');

    // Tampilkan Loading State
    tbody.innerHTML =
        `<tr><td colspan="7" class="py-12 text-center text-gray-500 animate-pulse">Sedang memuat data...</td></tr>`;

    // Susun URL API
    let url = `/Sinergi/index.php?page=admin-api-reports&limit=${currentLimit}&p=${page}`;
    if (status) url += '&status=' + status;

    fetch(url)
        .then(res => {
            // Cek jika response bukan OK
            if (!res.ok) throw new Error("Gagal koneksi server");
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Update Statistik Header
                if (data.stats) {
                    document.getElementById('stat-total').innerText = data.stats.total || 0;
                    document.getElementById('stat-pending').innerText = data.stats.pending || 0;
                    document.getElementById('stat-resolved').innerText = data.stats.resolved || 0;
                }

                // Render Tabel
                renderTable(data.data, (page - 1) * currentLimit);

                // Render Pagination Buttons
                if (data.pagination) {
                    renderPagination(data.pagination);
                }

            } else {
                // Tampilkan Error dari API
                tbody.innerHTML =
                    `<tr><td colspan="7" class="py-8 text-center text-red-500 font-medium">Error: ${data.message}</td></tr>`;
                paginationContainer.classList.add('hidden');
            }
        })
        .catch(err => {
            console.error(err);
            // Tampilkan Error JSON Parse / Koneksi
            tbody.innerHTML =
                `<tr><td colspan="7" class="py-8 text-center text-red-500">Terjadi kesalahan sistem.<br><span class="text-xs text-gray-400">Pastikan tidak ada error PHP di Controller</span></td></tr>`;
        });
}

// 2. Fungsi Render Baris Tabel
function renderTable(reports, offset) {
    const tbody = document.getElementById('reports-tbody');
    const paginationContainer = document.getElementById('pagination-container');

    if (!reports || reports.length === 0) {
        tbody.innerHTML =
            `<tr><td colspan="7" class="py-20 text-center text-gray-400 italic">Tidak ada laporan ditemukan.</td></tr>`;
        paginationContainer.classList.add('hidden');
        return;
    }

    tbody.innerHTML = ''; // Bersihkan loading
    paginationContainer.classList.remove('hidden');

    reports.forEach((report, index) => {
        // Tentukan warna status
        const statusColor = {
            'pending': 'bg-red-50 text-red-600 border border-red-100',
            'reviewed': 'bg-yellow-50 text-yellow-600 border border-yellow-100',
            'resolved': 'bg-green-50 text-green-600 border border-green-100',
            'rejected': 'bg-gray-100 text-gray-600 border border-gray-200'
        } [report.STATUS] || 'bg-gray-50 text-gray-600';

        const detailLink = `/sinergi/index.php?page=admin-detail-report&id=${report.REPORT_ID}`;
        // Tombol Ban User (jika belum dibanned)
        let banButton = '';
        if (report.POST_OWNER_ID && !report.IS_OWNER_BANNED) {
            banButton = `
                    <button onclick="banUser(${report.REPORT_ID}, ${report.POST_OWNER_ID}, '${report.POST_OWNER_USERNAME}')" 
                            class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600 text-xs font-medium border-t flex items-center gap-2">
                    ⛔ Banned User
                    </button>`;
        }

        // HTML Baris
        const row = `
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="py-4 px-6 text-center align-top text-gray-500 font-medium">${offset + index + 1}</td>
                    
                    <td class="py-4 px-6 align-top">
                        <div class="flex items-center gap-3">
                            <img src="${report.REPORTER_AVATAR_FIXED}" class="w-9 h-9 rounded-full object-cover border bg-gray-200">
                            <div>
                                <div class="font-semibold text-gray-900 text-sm line-clamp-1">${report.REPORTER_NAMA}</div>
                                <div class="text-xs text-gray-500">@${report.REPORTER_USERNAME}</div>
                            </div>
                        </div>
                    </td>
                    
                    <td class="py-4 px-6 align-top text-gray-600 text-xs whitespace-nowrap">${report.TANGGAL_FORMAT}</td>
                    
                    <td class="py-4 px-6 align-top">
                        <span class="${statusColor} px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide">
                            ${report.STATUS}
                        </span>
                    </td>
                    
                    <td class="py-4 px-6 align-top text-gray-700 text-sm font-medium">${report.REASON}</td>
                    
                    <td class="py-4 px-6 align-top">
                        <div class="text-xs text-gray-500 mb-1">Terlapor: <span class="font-semibold text-gray-700">@${report.POST_OWNER_USERNAME || 'Unknown'}</span></div>
                        ${report.POST_ID 
                            ? `<a href="${detailLink}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold hover:underline flex items-center gap-1">Lihat Detail Laporan →</a>` 
                            : '<span class="text-gray-400 text-xs italic">Postingan sudah dihapus</span>'}
                    </td>
                    
                    <td class="py-4 px-6 align-top text-right relative">
                        <button class="action-btn p-1.5 rounded-full hover:bg-gray-200 text-gray-400 hover:text-gray-600 transition-colors" onclick="toggleMenu(this)">
                             <img src="/Sinergi/public/assets/icons/dot.svg" alt="..." class="w-5 h-5">
                        </button>
                        
                        <div class="action-menu hidden absolute right-0 mt-2 w-48 bg-white border border-gray-100 rounded-lg shadow-xl z-50 overflow-hidden text-left animate-fade-in-down">
                            ${report.POST_ID ? `
                            <button onclick="deletePost(${report.REPORT_ID})" 
                                    class="w-full text-left px-4 py-2 hover:bg-orange-50 text-orange-600 text-xs font-medium flex items-center gap-2">
                                🗑️ Hapus Postingan
                            </button>
                            ` : ''}
                            ${banButton}
                            <div class="border-t border-gray-100 my-1"></div>
                            <button onclick="markStatus(${report.REPORT_ID}, 'resolved')" class="w-full text-left px-4 py-2 hover:bg-green-50 text-green-700 text-xs font-medium">✓ Tandai Selesai</button>
                            <button onclick="markStatus(${report.REPORT_ID}, 'rejected')" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-gray-600 text-xs font-medium">✗ Tolak</button>
                        </div>
                    </td>
                </tr>`;
        tbody.innerHTML += row;
    });
}

// 3. Fungsi Render Pagination Button
function renderPagination(pg) {
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const pageNumbers = document.getElementById('page-numbers');

    // Update teks Info
    const start = (pg.current_page - 1) * pg.limit + 1;
    const end = Math.min(pg.current_page * pg.limit, pg.total_records);
    document.getElementById('page-info-start').innerText = start;
    document.getElementById('page-info-end').innerText = end;
    document.getElementById('page-info-total').innerText = pg.total_records;

    totalPages = pg.total_pages;

    // Disable tombol jika di ujung
    btnPrev.disabled = pg.current_page <= 1;
    btnNext.disabled = pg.current_page >= pg.total_pages;

    // Event Listener Tombol Prev/Next
    btnPrev.onclick = () => loadReports(pg.current_page - 1);
    btnNext.onclick = () => loadReports(pg.current_page + 1);

    // Render Angka Halaman (Max 5 tombol angka)
    pageNumbers.innerHTML = '';

    let startPage = Math.max(1, pg.current_page - 2);
    let endPage = Math.min(pg.total_pages, startPage + 4);

    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }

    for (let i = startPage; i <= endPage; i++) {
        const btn = document.createElement('button');
        btn.innerText = i;
        // Style berbeda untuk halaman aktif
        if (i === pg.current_page) {
            btn.className = `w-8 h-8 rounded border border-black bg-black text-white text-sm font-bold`;
        } else {
            btn.className =
                `w-8 h-8 rounded border border-gray-200 hover:bg-gray-100 text-gray-600 text-sm font-medium transition-colors`;
        }
        btn.onclick = () => loadReports(i);
        pageNumbers.appendChild(btn);
    }
}

// --- Action Handlers (Proses Tombol Aksi) ---

function deletePost(reportId) {
    if (!confirm('Hapus postingan ini?')) return;
    sendAction('delete_post', {
        report_id: reportId
    });
}

function banUser(reportId, userId, username) {
    if (!confirm(`Yakin ingin BANNED @${username}?`)) return;
    sendAction('ban_user', {
        report_id: reportId,
        user_id: userId
    });
}

function markStatus(reportId, status) {
    if (!confirm('Update status laporan?')) return;
    sendAction('mark_' + status, {
        report_id: reportId
    });
}

function sendAction(actionType, data) {
    const formData = new FormData();
    formData.append('action', actionType);
    for (const key in data) formData.append(key, data[key]);

    fetch('/Sinergi/index.php?page=admin-api-process', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(resp => {
            if (resp.status === 'success' || resp.status === true) {
                alert('✓ Berhasil');
                loadReports(currentPage); // Reload halaman yang sedang aktif
            } else {
                alert('Gagal: ' + resp.message);
            }
        })
        .catch(err => alert('Error: ' + err.message));
}

// --- UI Helper Functions ---

function toggleMenu(btn) {
    // Tutup semua menu lain dulu
    document.querySelectorAll('.action-menu').forEach(el => {
        if (el !== btn.nextElementSibling) el.classList.add('hidden');
    });
    // Toggle menu ini
    btn.nextElementSibling.classList.toggle('hidden');
    event.stopPropagation();
}

// Tutup menu jika klik di luar
window.onclick = function(event) {
    if (!event.target.closest('.action-btn')) {
        document.querySelectorAll('.action-menu').forEach(el => el.classList.add('hidden'));
    }
};

// --- Event Listeners Awal ---

// Saat ganti filter, kembali ke page 1
document.getElementById('filter-status').addEventListener('change', () => loadReports(1));

// Fitur Search Client-Side Sederhana (Filter visual)
document.getElementById('admin-search').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    const rows = document.querySelectorAll('#reports-tbody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});

// Jalankan saat halaman pertama kali dibuka
loadReports(1);
</script>