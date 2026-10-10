/**
 * Inisialisasi Grafik Interaktif Chart.js
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 */

let circulationChartInstance = null;
let categoryChartInstance = null;

function initDashboardCharts(circulationData, categoryData) {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const textColor = isDark ? '#94a3b8' : '#64748b';
  const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';

  // 1. Grafik Peminjaman & Pengembalian 12 Bulan Terakhir
  const ctxCirculation = document.getElementById('circulationChart');
  if (ctxCirculation && circulationData) {
    if (circulationChartInstance) circulationChartInstance.destroy();

    circulationChartInstance = new Chart(ctxCirculation, {
      type: 'line',
      data: {
        labels: circulationData.labels,
        datasets: [
          {
            label: 'Peminjaman',
            data: circulationData.peminjaman,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.12)',
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#2563eb'
          },
          {
            label: 'Pengembalian',
            data: circulationData.pengembalian,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.08)',
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#10b981'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: { color: textColor, font: { family: 'inherit', size: 12 } }
          },
          tooltip: {
            padding: 10,
            cornerRadius: 8
          }
        },
        scales: {
          x: {
            grid: { color: gridColor },
            ticks: { color: textColor, font: { size: 11 } }
          },
          y: {
            beginAtZero: true,
            grid: { color: gridColor },
            ticks: { color: textColor, stepSize: 1, font: { size: 11 } }
          }
        }
      }
    });
  }

  // 2. Grafik Donat Kategori Buku
  const ctxCategory = document.getElementById('categoryChart');
  if (ctxCategory && categoryData) {
    if (categoryChartInstance) categoryChartInstance.destroy();

    categoryChartInstance = new Chart(ctxCategory, {
      type: 'doughnut',
      data: {
        labels: categoryData.labels,
        datasets: [{
          data: categoryData.data,
          backgroundColor: [
            '#2563eb', '#10b981', '#f59e0b', '#8b5cf6',
            '#06b6d4', '#ec4899', '#f97316', '#64748b'
          ],
          borderWidth: 2,
          borderColor: isDark ? '#131d35' : '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: textColor,
              boxWidth: 12,
              padding: 12,
              font: { size: 11 }
            }
          }
        }
      }
    });
  }
}

// Re-render chart saat tema gelap/terang diubah
window.addEventListener('themeChanged', () => {
  if (window.DASHBOARD_CHART_DATA) {
    initDashboardCharts(window.DASHBOARD_CHART_DATA.circulation, window.DASHBOARD_CHART_DATA.category);
  }
});
