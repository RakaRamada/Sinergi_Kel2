<?php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/bottom_nav.php';
?>

<main class="flex-1 p-4 sm:p-6 ml-0 lg:ml-20 bg-gray-50 min-h-screen pb-24 lg:pb-6 overflow-x-hidden">
    <div class="max-w-[1200px] mx-auto">

        <div class="flex flex-col gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Manajemen Laporan</h1>
                <p class="text-gray-500 text-sm">Kelola laporan konten dan pengguna</p>
            </div>

            <!-- Search and Filter Row -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="relative flex-1">
                    <input id="admin-search" type="text" placeholder="Cari pelapor/alasan..."
                        class="w-full py-2.5 px-4 pl-10 bg-white border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent text-sm shadow-sm" />
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <div class="flex gap-2">
                    <select id="filter-status"
                        class="flex-1 sm:flex-none px-4 py-2.5 bg-black text-white text-sm rounded-full cursor-pointer hover:bg-gray-800 transition-colors shadow-md outline-none">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="resolved">Selesai</option>
                    </select>
                    <!-- Tombol Hapus Semua - Hidden on Mobile -->
                    <button id="btn-purge-reports" onclick="purgeAllResolvedReports()"
                        class="hidden sm:flex px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm rounded-full cursor-pointer transition-colors shadow-md items-center gap-2"
                        title="Hapus semua laporan yang sudah selesai">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        <span class="hidden lg:inline">Hapus Selesai</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards - Responsive Grid -->
        <div class="grid grid-cols-3 gap-3 sm:gap-6 mb-8">
            <div
                class="bg-white border border-gray-200 rounded-xl shadow-sm p-3 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                <div>
                    <div class="text-[10px] sm:text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Total
                    </div>
                    <div class="text-xl sm:text-3xl font-bold text-gray-800" id="stat-total">0</div>
                </div>
                <div class="p-2 sm:p-3 bg-gray-50 rounded-full hidden sm:flex">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 21v-8a2 2 0 012-2h14a2 2 0 012 2v8M3 13l4-8h10l4 8M12 5v13"></path>
                    </svg>
                </div>
            </div>
            <div
                class="bg-white border border-gray-200 rounded-xl shadow-sm p-3 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                <div>
                    <div class="text-[10px] sm:text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">
                        Pending</div>
                    <div class="text-xl sm:text-3xl font-bold text-red-500" id="stat-pending">0</div>
                </div>
                <div class="p-2 sm:p-3 bg-red-50 rounded-full hidden sm:flex">
                    <div
                        class="w-6 h-6 rounded-full bg-red-200 flex items-center justify-center text-xs font-bold text-red-600">
                        !</div>
                </div>
            </div>
            <div
                class="bg-white border border-gray-200 rounded-xl shadow-sm p-3 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                <div>
                    <div class="text-[10px] sm:text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">
                        Selesai</div>
                    <div class="text-xl sm:text-3xl font-bold text-green-500" id="stat-resolved">0</div>
                </div>
                <div class="p-2 sm:p-3 bg-green-50 rounded-full hidden sm:flex">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Modern Table Container -->
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[500px]">
            <div class="overflow-x-auto flex-1 custom-scrollbar">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50/50 border-b border-gray-100">
                        <tr class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <th class="py-5 px-6 w-16 text-center">No.</th>
                            <th class="py-5 px-6 w-64">Pelapor</th>
                            <th class="py-5 px-6">Tanggal</th>
                            <th class="py-5 px-6">Status</th>
                            <th class="py-5 px-6">Jenis Laporan</th>
                            <th class="py-5 px-6">Konten / Terlapor</th>
                            <th class="py-5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="reports-tbody" class="divide-y divide-gray-50">
                    </tbody>
                </table>
            </div>

            <!-- Footer Pagination -->
            <div id="pagination-container"
                class="px-6 py-4 border-t border-gray-50 flex items-center justify-between bg-white hidden">
                <div class="text-sm text-gray-500">
                    Menampilkan <span id="page-info-start" class="font-bold text-gray-900">0</span> - <span
                        id="page-info-end" class="font-bold text-gray-900">0</span> dari <span id="page-info-total"
                        class="font-bold text-gray-900">0</span>
                </div>
                <div class="flex items-center gap-2">
                    <button id="btn-prev"
                        class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium transition-colors">Previous</button>
                    <div id="page-numbers" class="flex items-center gap-1"></div>
                    <button id="btn-next"
                        class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium transition-colors">Next</button>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Load Modern Modal Script -->
<script src="/sinergi/public/assets/js/admin-modal.js"></script>

<script>
// Variabel Global
let currentPage = 1;
let totalPages = 1;
let currentLimit = 5;

// 1. Fungsi Load Data dari API
function loadReports(page = 1) {
    currentPage = page;
    const status = document.getElementById('filter-status').value;
    const tbody = document.getElementById('reports-tbody');
    const paginationContainer = document.getElementById('pagination-container');

    // Tampilkan Loading State
    tbody.innerHTML =
        `<tr><td colspan="7" class="py-24 text-center text-gray-400 animate-pulse">
            <div class="flex flex-col items-center justify-center gap-3">
                <svg class="w-8 h-8 animate-spin text-gray-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="text-xs font-medium uppercase tracking-wider">Memuat Data...</span>
            </div>
        </td></tr>`;

    let url = `/sinergi/index.php?page=admin-api-reports&limit=${currentLimit}&p=${page}&t=${new Date().getTime()}`;
    if (status) url += '&status=' + status;

    fetch(url)
        .then(res => {
            if (!res.ok) throw new Error("Gagal koneksi server (404/500)");
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Update Statistik Header (Animasi Counter Sederhana bisa ditambahkan nanti)
                if (data.stats) {
                    document.getElementById('stat-total').innerText = data.stats.total || 0;
                    document.getElementById('stat-pending').innerText = data.stats.pending || 0;
                    document.getElementById('stat-resolved').innerText = data.stats.resolved || 0;
                }

                renderTable(data.data, (page - 1) * currentLimit);

                if (data.pagination) {
                    renderPagination(data.pagination);
                }

            } else {
                tbody.innerHTML =
                    `<tr><td colspan="7" class="py-12 text-center text-red-500 font-medium bg-red-50/50 rounded-lg m-4">Error: ${data.message}</td></tr>`;
                paginationContainer.classList.add('hidden');
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML =
                `<tr><td colspan="7" class="py-12 text-center text-red-500">Terjadi kesalahan sistem.<br><span class="text-xs text-gray-400">Cek Console Log untuk detail</span></td></tr>`;
        });
}

// 2. Fungsi Render Baris Tabel
function renderTable(reports, offset) {
    const tbody = document.getElementById('reports-tbody');
    const paginationContainer = document.getElementById('pagination-container');
    if (!reports || reports.length === 0) {
        tbody.innerHTML =
            `<tr><td colspan="7" class="p-0 border-none">
                <div class="flex flex-col items-center justify-center py-32 text-gray-400 italic">
                    <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Tidak ada laporan ditemukan.
                </div>
            </td></tr>`;
        paginationContainer.classList.add('hidden');
        return;
    }

    tbody.innerHTML = '';
    paginationContainer.classList.remove('hidden');

    reports.forEach((report, index) => {
        // Status Badge Style
        const statusConfig = {
            'pending': {
                class: 'bg-red-50 text-red-700 border border-red-100 ring-1 ring-red-100',
                text: 'PENDING'
            },
            'reviewed': {
                class: 'bg-yellow-50 text-yellow-700 border border-yellow-100 ring-1 ring-yellow-100',
                text: 'REVIEWED'
            },
            'resolved': {
                class: 'bg-green-50 text-green-700 border border-green-100 ring-1 ring-green-100',
                text: 'SOLVED'
            },
            'rejected': {
                class: 'bg-gray-100 text-gray-600 border border-gray-200 ring-1 ring-gray-200',
                text: 'REJECTED'
            }
        } [report.STATUS] || {
            class: 'bg-gray-50 text-gray-600',
            text: report.STATUS
        };

        const detailLink = `/sinergi/index.php?page=admin-detail-report&id=${report.REPORT_ID}`;

        // Tombol Ban (Context Menu)
        let banButton = '';
        if (report.POST_OWNER_ID && !report.IS_OWNER_BANNED) {
            banButton = `
                    <button onclick="banUser(${report.REPORT_ID}, ${report.POST_OWNER_ID}, '${report.POST_OWNER_USERNAME}')" 
                            class="w-full text-left px-4 py-2.5 hover:bg-red-50 text-red-600 text-xs font-medium border-t flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                        Banned User
                    </button>`;
        }

        // HTML Baris
        const row = `
                <tr class="hover:bg-gray-50/80 transition-all duration-200 group border-l-2 border-transparent hover:border-black">
                    <td class="py-5 px-6 text-center align-top text-gray-400 font-medium text-xs">${offset + index + 1}</td>
                    
                    <td class="py-5 px-6 align-top">
                        <div class="flex items-center gap-4">
                            <img src="${report.REPORTER_AVATAR_FIXED}" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm bg-gray-100">
                            <div>
                                <div class="font-bold text-gray-900 text-sm line-clamp-1 group-hover:text-black transition-colors">${report.REPORTER_NAMA}</div>
                                <div class="text-xs text-gray-500 font-medium">@${report.REPORTER_USERNAME}</div>
                            </div>
                        </div>
                    </td>
                    
                    <td class="py-5 px-6 align-top text-gray-500 text-xs whitespace-nowrap font-medium tracking-wide">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            ${report.TANGGAL_FORMAT}
                        </div>
                    </td>
                    
                    <td class="py-5 px-6 align-top">
                        <span class="${statusConfig.class} px-3 py-1 rounded-full text-[10px] font-bold tracking-widest shadow-sm inline-flex items-center gap-1">
                            ${statusConfig.text}
                        </span>
                    </td>
                    
                    <td class="py-5 px-6 align-top text-gray-700 text-sm font-medium">
                        <span class="inline-block bg-gray-100 px-2 py-1 rounded text-gray-600 text-xs">${report.REASON}</span>
                    </td>
                    
                    <td class="py-5 px-6 align-top">
                        <div class="text-xs text-gray-400 mb-1 font-medium uppercase tracking-wider">Terlapor</div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-bold text-gray-800 text-xs bg-gray-50 px-2 py-0.5 rounded border border-gray-200">@${report.POST_OWNER_USERNAME || 'Unknown'}</span>
                        </div>
                        ${report.POST_ID 
                            ? `<a href="${detailLink}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline decoration-blue-200 underline-offset-2 transition-all">
                                Lihat Detail
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                               </a>` 
                            : '<span class="text-gray-400 text-xs italic flex items-center gap-1"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg> Post dihapus</span>'}
                    </td>
                    
                    <td class="py-5 px-6 align-top text-right relative">
                        <button class="action-btn p-2 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-all cursor-pointer" onclick="toggleMenu(this)">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                        </button>
                        
                        <div class="action-menu hidden absolute right-0 mt-2 w-56 bg-white border border-gray-100 rounded-xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1)] z-50 overflow-hidden text-left animate-bounce-in ring-1 ring-black/5">
                            ${report.POST_ID ? `
                            <button onclick="deletePost(${report.REPORT_ID})" 
                                    class="w-full text-left px-4 py-2.5 hover:bg-orange-50 text-orange-600 text-xs font-medium flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Hapus Postingan
                            </button>
                            ` : ''}
                            ${banButton}
                            <div class="border-t border-gray-50 my-1"></div>
                            <button onclick="markStatus(${report.REPORT_ID}, 'resolved')" class="w-full text-left px-4 py-2.5 hover:bg-green-50 text-green-700 text-xs font-medium flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Tandai Selesai
                            </button>
                            <button onclick="markStatus(${report.REPORT_ID}, 'rejected')" class="w-full text-left px-4 py-2.5 hover:bg-gray-50 text-gray-600 text-xs font-medium flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                Tolak / Abaikan
                            </button>
                            ${report.STATUS === 'resolved' ? `
                            <div class="border-t border-gray-50 my-1"></div>
                            <button onclick="deleteResolvedReport(${report.REPORT_ID})" class="w-full text-left px-4 py-2.5 hover:bg-red-50 text-red-600 text-xs font-medium flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Hapus Data Laporan
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>`;
        tbody.innerHTML += row;
    });
}

// 3. Render Pagination (Modern Style)
function renderPagination(pg) {
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const pageNumbers = document.getElementById('page-numbers');

    const start = (pg.current_page - 1) * currentLimit + 1;
    const end = Math.min(pg.current_page * currentLimit, pg.total_records);
    document.getElementById('page-info-start').innerText = start;
    document.getElementById('page-info-end').innerText = end;
    document.getElementById('page-info-total').innerText = pg.total_records;

    totalPages = pg.total_pages;

    btnPrev.disabled = pg.current_page <= 1;
    btnNext.disabled = pg.current_page >= pg.total_pages;

    btnPrev.onclick = () => loadReports(pg.current_page - 1);
    btnNext.onclick = () => loadReports(pg.current_page + 1);

    pageNumbers.innerHTML = '';

    let startPage = Math.max(1, pg.current_page - 2);
    let endPage = Math.min(pg.total_pages, startPage + 4);

    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }

    // Simplifikasi logic pagination
    for (let i = startPage; i <= endPage; i++) {
        const btn = document.createElement('button');
        btn.innerText = i;
        if (i === pg.current_page) {
            btn.className =
                `w-9 h-9 rounded-lg border border-black bg-black text-white text-sm font-bold shadow-md transform scale-105`;
        } else {
            btn.className =
                `w-9 h-9 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-500 text-sm font-medium transition-all hover:border-gray-300`;
        }
        btn.onclick = () => loadReports(i);
        pageNumbers.appendChild(btn);
    }
}

// --- Action Handlers (MODERN MODAL) ---

async function deletePost(reportId) {
    if (await showModernConfirm('Hapus Postingan?',
            'Postingan akan dihapus permanen beserta komentar dan likes.')) {
        sendAction('delete_post', {
            report_id: reportId
        });
    }
}

async function banUser(reportId, userId, username) {
    if (await showModernConfirm('Banned User?', `User @${username} tidak akan bisa login lagi.`)) {
        sendAction('ban_user', {
            report_id: reportId,
            user_id: userId
        });
    }
}

async function markStatus(reportId, status) {
    const labels = {
        'resolved': 'Selesai',
        'rejected': 'Ditolak'
    };
    if (await showModernConfirm(`Tandai ${labels[status]}?`, 'Status laporan akan diperbarui.')) {
        sendAction('mark_' + status, {
            report_id: reportId
        });
    }
}

async function deleteResolvedReport(reportId) {
    if (await showModernConfirm('Hapus Laporan?', 'Data laporan akan dihapus dari database.')) {

        const formData = new FormData();
        formData.append('report_id', reportId);

        fetch('/sinergi/index.php?page=admin-api-delete-report', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(resp => {
                if (resp.status === true) {
                    showModernAlert('Berhasil', 'Laporan telah dihapus', 'success');
                    loadReports(currentPage);
                } else {
                    showModernAlert('Gagal', resp.message, 'error');
                }
            })
            .catch(err => showModernAlert('Error', err.message, 'error'));
    }
}

async function purgeAllResolvedReports() {
    if (await showModernConfirm('Hapus Semua?', 'Hapus SEMUA laporan berstatus Selesai? Tidak bisa dibatalkan.')) {

        fetch('/sinergi/index.php?page=admin-api-purge-reports', {
                method: 'POST'
            })
            .then(res => res.json())
            .then(resp => {
                if (resp.status === true) {
                    showModernAlert('Pembersihan Selesai', resp.message, 'success');
                    loadReports(1);
                } else {
                    showModernAlert('Gagal', resp.message, 'error');
                }
            })
            .catch(err => showModernAlert('Error', err.message, 'error'));
    }
}

function sendAction(actionType, data) {
    const formData = new FormData();
    formData.append('action', actionType);
    for (const key in data) formData.append(key, data[key]);

    fetch('/sinergi/index.php?page=admin-api-process', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(resp => {
            if (resp.status === 'success' || resp.status === true) {
                showModernAlert('Berhasil', 'Aksi berhasil dilakukan', 'success');
                loadReports(currentPage);
            } else {
                showModernAlert('Gagal', resp.message, 'error');
            }
        })
        .catch(err => showModernAlert('Error', err.message, 'error'));
}

// --- UI Helper Functions ---

function toggleMenu(btn) {
    document.querySelectorAll('.action-menu').forEach(el => {
        if (el !== btn.nextElementSibling) el.classList.add('hidden');
    });
    btn.nextElementSibling.classList.toggle('hidden');
    event.stopPropagation();
}

window.onclick = function(event) {
    if (!event.target.closest('.action-btn')) {
        document.querySelectorAll('.action-menu').forEach(el => el.classList.add('hidden'));
    }
};

document.getElementById('filter-status').addEventListener('change', () => loadReports(1));

document.getElementById('admin-search').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    const rows = document.querySelectorAll('#reports-tbody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});

loadReports(1);
</script>