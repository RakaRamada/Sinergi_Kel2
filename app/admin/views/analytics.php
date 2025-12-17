<?php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/bottom_nav.php';

// Logic PHP Sederhana untuk Dropdown Tahun
// Mulai dari 2025 sampai tahun sekarang
$startYear = 2025;
$currentYear = date('Y');
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* CSS HACK: Memaksa panah bawaan browser hilang total */
.clean-select {
    -webkit-appearance: none;
    /* Chrome, Safari, Edge */
    -moz-appearance: none;
    /* Firefox */
    appearance: none;
    background-image: none;
    background-color: transparent;
}

/* Khusus untuk Internet Explorer / Edge lama */
.clean-select::-ms-expand {
    display: none;
}

/* Animasi Dropdown Muncul */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.animate-fade-in-down {
    animation: fadeInDown 0.2s ease-out forwards;
}

/* Scrollbar Gelap Keren */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #1A1A1A;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<main class="flex-1 p-4 sm:p-6 ml-0 lg:ml-20 bg-gray-50 min-h-screen flex flex-col pb-24 lg:pb-6 overflow-x-hidden">

    <div class="w-full flex-1 bg-white rounded-[20px] shadow-sm p-4 sm:p-8 relative flex flex-col">

        <div class="flex flex-col md:flex-row justify-between items-center mb-6 shrink-0 gap-4">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight self-start md:self-center">
                Tren Pertumbuhan
            </h1>

            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto z-30">

                <div class="relative w-full md:w-48 group">
                    <input type="hidden" id="dataFilter" value="user">

                    <button onclick="toggleDropdown('dropdown-data')" id="btn-data-label"
                        class="w-full bg-[#1A1A1A] text-white pl-5 pr-4 py-2.5 rounded-full text-sm font-medium border border-gray-700 hover:border-gray-500 hover:bg-black transition-all shadow-md flex justify-between items-center cursor-pointer">
                        <span>User Baru</span> <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            id="arrow-data" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div id="dropdown-data"
                        class="hidden absolute top-full mt-2 w-full bg-[#1A1A1A] border border-gray-800 rounded-2xl shadow-xl overflow-hidden z-40 transform origin-top animate-fade-in-down">
                        <div class="py-1">
                            <div onclick="selectOption('data', 'user', 'User Baru')"
                                class="px-5 py-3 text-sm text-gray-300 hover:bg-gray-800 hover:text-white cursor-pointer transition-colors flex items-center gap-2">
                                User Baru
                            </div>
                            <div onclick="selectOption('data', 'post', 'Postingan')"
                                class="px-5 py-3 text-sm text-gray-300 hover:bg-gray-800 hover:text-white cursor-pointer transition-colors flex items-center gap-2">
                                Postingan
                            </div>
                            <div onclick="selectOption('data', 'community', 'Groups')"
                                class="px-5 py-3 text-sm text-gray-300 hover:bg-gray-800 hover:text-white cursor-pointer transition-colors flex items-center gap-2">
                                Groups
                            </div>
                            <div onclick="selectOption('data', 'reports', 'Laporan Masuk')"
                                class="px-5 py-3 text-sm text-gray-300 hover:bg-gray-800 hover:text-white cursor-pointer transition-colors flex items-center gap-2">
                                Laporan Masuk
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative w-full md:w-32 group">
                    <input type="hidden" id="yearFilter" value="<?= $currentYear ?>">

                    <button onclick="toggleDropdown('dropdown-year')" id="btn-year-label"
                        class="w-full bg-[#1A1A1A] text-white pl-5 pr-4 py-2.5 rounded-full text-sm font-medium border border-gray-700 hover:border-gray-500 hover:bg-black transition-all shadow-md flex justify-between items-center cursor-pointer">
                        <span><?= $currentYear ?></span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" id="arrow-year" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div id="dropdown-year"
                        class="hidden absolute top-full mt-2 w-full bg-[#1A1A1A] border border-gray-800 rounded-2xl shadow-xl overflow-hidden z-40 transform origin-top animate-fade-in-down max-h-60 overflow-y-auto custom-scrollbar">
                        <div class="py-1">
                            <?php 
                for ($y = $startYear; $y <= $currentYear; $y++) {
                    echo "<div onclick=\"selectOption('year', '$y', '$y')\" class=\"px-5 py-3 text-sm text-gray-300 hover:bg-gray-800 hover:text-white cursor-pointer transition-colors\">$y</div>";
                }
                ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="relative w-full flex-1 min-h-0">
            <canvas id="mainChart"></canvas>

            <div id="chartLoading"
                class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90 z-10 hidden">
                <div class="flex flex-col items-center gap-3">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-black"></div>
                    <span class="text-sm text-gray-500 font-medium">Memuat Data...</span>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
let myChart = null;

// --- FUNGSI UPDATE CHART ---
async function updateChart() {
    const filter = document.getElementById('dataFilter').value;
    const year = document.getElementById('yearFilter').value; // Ambil tahun
    const loading = document.getElementById('chartLoading');

    loading.classList.remove('hidden');
    try {
        // Panggil API dengan parameter filter & year
        const url = `/sinergi/index.php?page=admin-api-chart-data&filter=${filter}&year=${year}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.status === 'success') {
            renderChart(result.labels, result.data, result.label);
        } else {
            console.error("API Error:", result.message);
        }
    } catch (error) {
        console.error("Fetch Error:", error);
    } finally {
        loading.classList.add('hidden');
    }
}

// --- FUNGSI RENDER CHART ---
function renderChart(labels, dataPoints, datasetLabel) {
    const ctx = document.getElementById('mainChart').getContext('2d');

    if (myChart) {
        myChart.destroy();
    }

    const gradient = ctx.createLinearGradient(0, 0, 0, 800);
    gradient.addColorStop(0, 'rgba(0, 0, 0, 0.2)');
    gradient.addColorStop(1, 'rgba(0, 0, 0, 0.0)');

    myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: datasetLabel,
                data: dataPoints,
                backgroundColor: gradient,
                borderColor: '#111827',
                borderWidth: 2,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#111827',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#000',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' Data';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    border: {
                        display: false
                    },
                    grid: {
                        color: '#E5E7EB',
                        borderDash: [5, 5],
                        drawBorder: false
                    },
                    ticks: {
                        color: '#6B7280',
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        display: true,
                        color: '#E5E7EB',
                        borderDash: [5, 5],
                        drawBorder: false
                    },
                    ticks: {
                        color: '#6B7280',
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            },
        }
    });
}

function toggleDropdown(id) {
    // Tutup dropdown lain dulu biar gak numpuk
    const allDropdowns = ['dropdown-data', 'dropdown-year'];
    allDropdowns.forEach(d => {
        if (d !== id) document.getElementById(d).classList.add('hidden');
    });

    const el = document.getElementById(id);
    const arrow = document.getElementById(id === 'dropdown-data' ? 'arrow-data' : 'arrow-year');

    el.classList.toggle('hidden');

    // Putar panah kalau aktif
    if (!el.classList.contains('hidden')) {
        arrow.style.transform = 'rotate(180deg)';
    } else {
        arrow.style.transform = 'rotate(0deg)';
    }
}

// Logika Saat Opsi Dipilih
function selectOption(type, value, label) {
    // 1. Update Input Hidden (agar Chart API bisa baca)
    const inputId = type === 'data' ? 'dataFilter' : 'yearFilter';
    document.getElementById(inputId).value = value;

    // 2. Update Label Tombol
    const btnLabelId = type === 'data' ? 'btn-data-label' : 'btn-year-label';
    document.querySelector(`#${btnLabelId} span`).innerText = label;

    // 3. Tutup Dropdown
    toggleDropdown(type === 'data' ? 'dropdown-data' : 'dropdown-year');

    // 4. Panggil Update Chart (Fungsi Chart Kamu)
    if (typeof updateChart === "function") {
        updateChart();
    }
}

// Tutup dropdown kalau klik di luar area
window.addEventListener('click', function(e) {
    if (!e.target.closest('.group')) {
        document.getElementById('dropdown-data').classList.add('hidden');
        document.getElementById('dropdown-year').classList.add('hidden');
        document.getElementById('arrow-data').style.transform = 'rotate(0deg)';
        document.getElementById('arrow-year').style.transform = 'rotate(0deg)';
    }
});

document.addEventListener('DOMContentLoaded', updateChart);
</script>