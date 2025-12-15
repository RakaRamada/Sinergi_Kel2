<?php
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="flex-1 p-6 ml-0 lg:ml-20 bg-gray-50 min-h-screen flex flex-col">

    <div class="w-full flex-1 bg-white rounded-[20px] shadow-sm p-8 relative flex flex-col">

        <div class="flex flex-col md:flex-row justify-between items-center mb-6 shrink-0">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight self-start md:self-center">
                Visualisasi Data Laporan
            </h1>

            <div class="relative mt-4 md:mt-0 w-full md:w-auto z-20">
                <select id="dataFilter" onchange="updateChart()"
                    class="appearance-none bg-[#1A1A1A] text-white pl-6 pr-12 py-2.5 rounded-full text-sm font-medium cursor-pointer focus:outline-none hover:bg-black transition-colors shadow-md w-full md:w-48">
                    <option value="user">User</option>
                    <option value="post">Postingan</option>
                    <option value="community">Groups</option>
                </select>

                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
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
    const loading = document.getElementById('chartLoading');

    loading.classList.remove('hidden');

    try {
        // Panggil API
        const url = `/Sinergi/index.php?page=admin-api-chart-data&filter=${filter}`;
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

    // Gradient Fill (Lebih tinggi karena chart lebih besar)
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
                tension: 0.4 // Garis lurus
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // PENTING: Agar chart mengikuti ukuran container flex
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
                mode: 'index',
            },
        }
    });
}

document.addEventListener('DOMContentLoaded', updateChart);
</script>