/**
 * Laporan Pengeluaran — Modular JavaScript
 *
 * Fitur:
 * - Search debounce (mengurangi request server)
 * - Chart.js: Trend Pemasukan & Pengeluaran Bulanan (line chart)
 * - Chart.js: Total Pengeluaran Per Kategori (horizontal bar chart)
 * - Chart.js: Perbandingan Pemasukan vs Pengeluaran (doughnut chart)
 * - Format Rupiah pada tooltip chart
 *
 * Data chart di-pass dari Blade view melalui window globals:
 * - window.monthlyTrendData
 * - window.categoryDistributionData
 * - window.summaryData
 */

// ============================================================
// INITIALIZATION
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // SEARCH DEBOUNCE
    //
    // Mencegah form submit setiap keystroke pada input pencarian.
    // Form akan submit setelah user berhenti mengetik 500ms.
    // ============================================================

    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function () {
            this.form.requestSubmit();
        }, 500));
    }

    // ============================================================
    // MONTHLY TREND CHART
    //
    // Line chart yang menampilkan trend pemasukan dan pengeluaran
    // per bulan selama tahun yang dipilih.
    // ============================================================

    const monthlyTrendCanvas = document.getElementById('monthlyTrendChart');
    if (monthlyTrendCanvas && window.monthlyTrendData) {
        const ctx = monthlyTrendCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: window.monthlyTrendData.map(item => item.month_name),
                datasets: [
                    {
                        label: 'Pemasukan (Rp)',
                        data: window.monthlyTrendData.map(item => item.income),
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#22c55e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    },
                    {
                        label: 'Pengeluaran (Rp)',
                        data: window.monthlyTrendData.map(item => item.expense),
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#ef4444',
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
    // CATEGORY EXPENSE CHART
    //
    // Horizontal bar chart yang menampilkan total pengeluaran
    // per kategori. Warna diambil dari categoryColors map.
    // ============================================================

    const categoryExpenseCanvas = document.getElementById('categoryExpenseChart');
    if (categoryExpenseCanvas && window.categoryDistributionData) {
        const categoryColors = {
            'UANG MASUK PENJUALAN': '#22c55e',
            'UPAH KERJA / KASBON': '#ff6b6b',
            'ATK / OPERASIONAL & ALAT': '#3b82f6',
            'PENGELUARAN MATERIAL': '#8b5cf6',
            'PENGELUARAN PEMBELIAN COIL': '#f59e0b',
            'TRANSPORT': '#14b8a6',
            'TOKEN LISTRIK': '#06b6d4',
            'LAIN - LAIN': '#ec4899',
        };

        const categoryNames = window.categoryDistributionData.map(item => item.category_name);
        const categoryExpenses = window.categoryDistributionData.map(item => item.expense);
        const categoryChartColors = categoryNames.map(name => categoryColors[name] || '#9ca3af');

        const ctx = categoryExpenseCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categoryNames,
                datasets: [{
                    label: 'Total Pengeluaran (Rp)',
                    data: categoryExpenses,
                    backgroundColor: categoryChartColors,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    }
                },
                scales: {
                    x: {
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
    // INCOME VS EXPENSE CHART
    //
    // Doughnut chart yang menampilkan perbandingan total
    // pemasukan vs total pengeluaran.
    // ============================================================

    const incomeVsExpenseCanvas = document.getElementById('incomeVsExpenseChart');
    if (incomeVsExpenseCanvas && window.summaryData) {
        const ctx = incomeVsExpenseCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Total Pemasukan', 'Total Pengeluaran'],
                datasets: [{
                    data: [
                        window.summaryData.total_income,
                        window.summaryData.total_expense
                    ],
                    backgroundColor: ['#22c55e', '#ef4444'],
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
