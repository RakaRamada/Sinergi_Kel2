<?php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="flex-1 p-6 ml-0 lg:ml-20 bg-gray-50 min-h-screen">
    <div class="max-w-[1200px] mx-auto bg-white min-h-[600px] rounded-lg shadow-sm p-8 relative">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <!-- Title -->
            <div class="flex items-center gap-4">
                <div class="hidden md:block">
                   <!-- Icon Sinergi Kecil (opsional, sesuai gambar kiri atas) -->
                   <img src="/Sinergi/public/assets/icons/logo.svg" alt="Logo" class="w-8 h-8 opacity-80">
                </div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">Visualisasi Data Laporan</h1>
            </div>

            <!-- Controls (Search & Filter) -->
            <div class="flex items-center gap-3 w-full md:w-auto">
                
                <!-- Search Bar -->
                <div class="relative w-full md:w-64">
                    <input type="text" placeholder="User123" 
                           class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:border-gray-500 bg-gray-50/50">
                    <svg class="w-4 h-4 text-gray-400 absolute right-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Filter Dropdown -->
                <div class="relative">
                    <select id="dataFilter" onchange="updateChart()" 
                            class="appearance-none bg-black text-white pl-6 pr-10 py-2 rounded-full text-sm font-medium cursor-pointer focus:outline-none hover:bg-gray-800 transition-colors">
                        <option value="user">Filter: User</option>
                        <option value="post">Filter: Postingan</option>
                        <option value="community">Filter: Komunitas</option>
                    </select>
                    <!-- Chevron Icon Custom -->
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

            </div>
        </div>

        <!-- Chart Area -->
        <div class="relative w-full h-[400px] md:h-[500px] p-4">
             <!-- Canvas Chart -->
            <canvas id="mainChart"></canvas>
            
            <!-- Loading Indicator -->
            <div id="chartLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-80 z-10 hidden">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
            </div>
        </div>

        <!-- Floating Action Button (FAB) simulasi huruf 'A' di pojok kiri bawah sesuai gambar -->
        <div class="absolute bottom-8 left-8">
            <div class="w-10 h-10 bg-gray-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                A
            </div>
        </div>

    </div>
</main>

<script>
let myChart = null;

// Fungsi Utama: Fetch Data API & Render Chart
async function updateChart() {
    const filter = document.getElementById('dataFilter').value;
    const loading = document.getElementById('chartLoading');
    
    // Tampilkan loading
    loading.classList.remove('hidden');

    try {
        // Panggil API Baru
        const response = await fetch(`/Sinergi/index.php?page=admin-api-chart-data&filter=${filter}`);
        const result = await response.json();

        if (result.status === 'success') {
            renderChart(result.labels, result.data, result.label);
        } else {
            console.error("Gagal memuat data:", result.message);
        }
    } catch (error) {
        console.error("Error Fetching:", error);
    } finally {
        loading.classList.add('hidden');
    }
}

function renderChart(labels, dataPoints, datasetLabel) {
    const ctx = document.getElementById('mainChart').getContext('2d');

    // Hancurkan chart lama jika ada agar tidak menumpuk
    if (myChart) {
        myChart.destroy();
    }

    // Buat Gradient Fill (Abu-abu ke Transparan)
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(156, 163, 175, 0.5)'); // Gray-400
    gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');

    myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: datasetLabel,
                data: dataPoints,
                backgroundColor: gradient,
                borderColor: '#1f2937', // Gray-900 (Hampir hitam)
                borderWidth: 2,
                pointBackgroundColor: '#ffffff', // Titik Putih
                pointBorderColor: '#1f2937',   // Border Hitam
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0, // Garis lurus antar titik (sesuai gambar)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Sembunyikan legend agar bersih seperti gambar
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    displayColors: false,
                    padding: 10,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f3f4f6', // Grid sangat tipis
                        borderDash: [5, 5] // Grid putus-putus
                    },
                    ticks: {
                        color: '#9ca3af', // Warna teks sumbu Y
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: {
                        display: true,
                        color: '#f3f4f6',
                        borderDash: [5, 5]
                    },
                    ticks: {
                        color: '#6b7280', // Warna teks sumbu X
                        font: { size: 11 }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
        }
    });
}

// Load chart pertama kali saat halaman dibuka
document.addEventListener('DOMContentLoaded', updateChart);
</script>