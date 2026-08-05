/**
 * Laporan Penjualan — Modular JavaScript
 *
 * Fitur:
 * - Chart.js: Trend Penjualan Bulanan (line chart)
 * - Chart.js: Status Pembayaran (doughnut chart)
 * - Auto-submit filter (bulan, tahun, status)
 * - Format Rupiah pada tooltip chart
 */

// ============================================================
// INISIALISASI
// ============================================================

/**
 * Inisialisasi halaman Laporan Penjualan setelah DOM selesai dimuat.
 *
 * Alur:
 * 1. Filter periode (bulan/tahun) dan status diterapkan di sisi server
 *    (SalesReportService::buildFilteredQuery) dan hasilnya di-pass ke skrip
 *    ini lewat window globals; perubahan filter disubmit otomatis via form
 *    (bulan/tahun/status) termasuk pencarian dengan debounce di Blade.
 * 2. Bila window.monthlyTrendData tersedia → render line chart trend
 *    penjualan & profit bulanan.
 * 3. Bila window.statusDistributionData tersedia → render doughnut chart
 *    proporsi pembayaran Lunas vs Belum Lunas.
 *
 * @returns {void}
 */
document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // GRAFIK PENJUALAN BULANAN
    //
    // Line chart yang menampilkan trend penjualan dan profit
    // per bulan selama tahun yang dipilih.
    // ============================================================

    /**
     * Merender line chart trend penjualan & profit bulanan.
     *
     * Data dari window.monthlyTrendData (backend: SalesReportService::
     * getMonthlyTrend): 12 titik bulan dengan nilai selling & profit; bulan
     * tanpa data diisi 0 oleh server. Tooltip sumbu Y diformat ringkas (Rp …jt).
     *
     * @returns {void}
     */
    const monthlySalesCanvas = document.getElementById('monthlySalesChart');
    if (monthlySalesCanvas && window.monthlyTrendData) {
        const ctx = monthlySalesCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: window.monthlyTrendData.map(item => item.month_name),
                datasets: [
                    {
                        label: 'Penjualan (Rp)',
                        data: window.monthlyTrendData.map(item => item.selling),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    },
                    {
                        label: 'Profit (Rp)',
                        data: window.monthlyTrendData.map(item => item.profit),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 12 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                            }
                        }
                    }
                }
            }
        });
    }

    // ============================================================
    // GRAFIK DISTRIBUSI STATUS
    //
    // Doughnut chart yang menampilkan proporsi
    // pembayaran Lunas vs Belum Lunas.
    // ============================================================

    /**
     * Merender doughnut chart distribusi status pembayaran.
     *
     * Data dari window.statusDistributionData (backend:
     * SalesReportService::getStatusDistribution): status (Lunas/Belum Lunas)
     * beserta jumlahnya. Warna: hijau (#10b981) untuk Lunas, merah (#ef4444)
     * untuk Belum Lunas.
     *
     * @returns {void}
     */
    const statusDistributionCanvas = document.getElementById('statusDistributionChart');
    if (statusDistributionCanvas && window.statusDistributionData) {
        const statusLabels = window.statusDistributionData.map(item => item.status);
        const statusCounts = window.statusDistributionData.map(item => item.count);

        const ctx = statusDistributionCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderColor: '#fff',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    }
                }
            }
        });
    }
});
