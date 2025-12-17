<?php
// app/admin/views/blacklist.php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/bottom_nav.php';
?>

<main class="flex-1 p-4 sm:p-6 ml-0 lg:ml-20 bg-gray-50 min-h-screen pb-24 lg:pb-6 overflow-x-hidden">
    <div class="max-w-[1200px] mx-auto">

        <!-- Page Header -->
        <div class="flex flex-col gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Daftar Hitam (Blacklist)</h1>
                <p class="text-gray-500 text-sm">Kelola user yang dibanned dari sistem</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative group flex-1 sm:flex-none sm:w-64">
                    <input id="blacklist-search" type="text" placeholder="Cari nama/username..."
                        class="w-full py-2.5 px-4 pl-10 bg-white border border-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent text-sm shadow-sm transition-all" />
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 group-hover:text-gray-600 transition-colors"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div
                class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 flex items-center justify-between relative overflow-hidden">
                <div class="relative z-10">
                    <div class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">Total User Dibanned</div>
                    <div class="text-4xl font-extrabold text-gray-900" id="stat-banned">0</div>
                </div>
                <div class="p-4 bg-red-50 rounded-2xl border border-red-100">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                        </path>
                    </svg>
                </div>
                <!-- Decor -->
                <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full opacity-50 z-0"></div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[400px]">
            <div class="overflow-x-auto flex-1 custom-scrollbar">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50/50 border-b border-gray-100">
                        <tr class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <th class="py-5 px-6 w-16 text-center">No.</th>
                            <th class="py-5 px-6 w-64">User</th>
                            <th class="py-5 px-6">Email</th>
                            <th class="py-5 px-6">Tgl Bergabung</th>
                            <th class="py-5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="blacklist-tbody" class="divide-y divide-gray-50">
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<!-- Load Modern Modal Script -->
<script src="/sinergi/public/assets/js/admin-modal.js"></script>

<script>
function loadBannedUsers() {
    const tbody = document.getElementById('blacklist-tbody');
    tbody.innerHTML =
        `<tr><td colspan="5" class="py-12 text-center text-gray-400 animate-pulse font-medium">Memuat data user...</td></tr>`;

    fetch('/sinergi/index.php?page=admin-api-banned-users&t=' + Date.now())
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('stat-banned').innerText = data.total || 0;
                renderBannedUsers(data.data);
            } else {
                tbody.innerHTML =
                    `<tr><td colspan="5" class="py-8 text-center text-red-500 bg-red-50">Error: ${data.message}</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML =
                `<tr><td colspan="5" class="py-8 text-center text-red-500">Terjadi kesalahan sistem</td></tr>`;
        });
}

function renderBannedUsers(users) {
    const tbody = document.getElementById('blacklist-tbody');

    if (!users || users.length === 0) {
        tbody.innerHTML =
            `<tr><td colspan="5" class="p-0 border-none">
                <div class="flex flex-col items-center justify-center py-24 text-gray-400 italic">
                    <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Tidak ada user yang sedang dibanned.
                </div>
            </td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    users.forEach((user, index) => {
        const row = `
            <tr class="hover:bg-gray-50/80 transition-all group border-l-2 border-transparent hover:border-black">
                <td class="py-5 px-6 text-center text-gray-400 font-medium text-xs">${index + 1}</td>
                
                <td class="py-5 px-6 align-middle">
                    <div class="flex items-center gap-4">
                        <img src="${user.AVATAR_URL}" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm bg-gray-100">
                        <div>
                            <div class="font-bold text-gray-900 text-sm group-hover:text-black transition-colors">${user.NAMA_LENGKAP}</div>
                            <div class="text-xs text-gray-500 font-medium">@${user.USERNAME}</div>
                        </div>
                    </div>
                </td>
                
                <td class="py-5 px-6 align-middle text-gray-600 text-sm font-medium">${user.EMAIL}</td>
                
                <td class="py-5 px-6 align-middle text-gray-500 text-xs font-medium tracking-wide">
                    ${user.JOINED_DATE}
                </td>
                
                <td class="py-5 px-6 align-middle text-right">
                    <button onclick="unbanUser(${user.USER_ID}, '${user.USERNAME}')" 
                            class="group/btn relative inline-flex items-center gap-2 px-4 py-2 bg-white border border-green-200 text-green-600 hover:bg-green-50 rounded-xl transition-all shadow-sm hover:shadow-md active:scale-95 cursor-pointer font-bold text-xs"
                            title="Pulihkan Akun Ini">
                        <svg class="w-4 h-4 transition-transform group-hover/btn:-rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                        Restore Account
                    </button>
                </td>
            </tr>`;
        tbody.innerHTML += row;
    });
}

async function unbanUser(userId, username) {
    if (await showModernConfirm('Pulihkan Akun?',
            `User @${username} akan bisa login dan posting kembali. Lanjutkan?`)) {

        const formData = new FormData();
        formData.append('user_id', userId);

        fetch('/sinergi/index.php?page=admin-api-unban-user', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(resp => {
                if (resp.status === true) {
                    showModernAlert('Akun Pulih', `User @${username} berhasil dipulihkan.`, 'success');
                    loadBannedUsers();
                } else {
                    showModernAlert('Gagal', resp.message, 'error');
                }
            })
            .catch(err => showModernAlert('Error', err.message, 'error'));
    }
}

// Search functionality
document.getElementById('blacklist-search').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    const rows = document.querySelectorAll('#blacklist-tbody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});

// Load on page ready
loadBannedUsers();
</script>