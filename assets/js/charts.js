// ============================================================
// CHARTS – Using Chart.js
// assets/js/charts.js
// ============================================================
// Requires Chart.js loaded in page: <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

const CVSCharts = {
  colors: {
    primary:  '#6c63ff',
    accent:   '#06b6d4',
    success:  '#10b981',
    warning:  '#f59e0b',
    danger:   '#ef4444',
    purple:   '#8b5cf6',
    pink:     '#ec4899',
    orange:   '#f97316',
  },

  palette: function(n) {
    const cols = Object.values(this.colors);
    return Array.from({length: n}, (_, i) => cols[i % cols.length]);
  },

  defaultOptions: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        labels: { color: '#94a3b8', font: { family: 'Inter', size: 12 }, padding: 16 }
      },
      tooltip: {
        backgroundColor: 'rgba(15,15,26,0.95)',
        titleColor: '#f1f5f9',
        bodyColor: '#94a3b8',
        borderColor: 'rgba(255,255,255,0.1)',
        borderWidth: 1,
        padding: 12,
        cornerRadius: 10,
      }
    },
    scales: {
      x: {
        grid: { color: 'rgba(255,255,255,0.05)' },
        ticks: { color: '#64748b', font: { family: 'Inter', size: 11 } }
      },
      y: {
        grid: { color: 'rgba(255,255,255,0.05)' },
        ticks: { color: '#64748b', font: { family: 'Inter', size: 11 } }
      }
    }
  },

  // ── PIE CHART: Vote distribution
  createPieChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data,
          backgroundColor: this.palette(data.length).map(c => c + 'bb'),
          borderColor: this.palette(data.length),
          borderWidth: 2,
          hoverOffset: 8,
        }]
      },
      options: {
        ...this.defaultOptions,
        cutout: '65%',
        scales: {},
        plugins: {
          ...this.defaultOptions.plugins,
          legend: {
            position: 'bottom',
            labels: { color: '#94a3b8', font: { family: 'Inter', size: 12 }, padding: 16, usePointStyle: true }
          }
        }
      }
    });
  },

  // ── BAR CHART: Department turnout
  createBarChart(canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const formattedDatasets = datasets.map((ds, i) => ({
      label: ds.label,
      data: ds.data,
      backgroundColor: this.palette(datasets.length)[i] + '88',
      borderColor: this.palette(datasets.length)[i],
      borderWidth: 2,
      borderRadius: 6,
      borderSkipped: false,
    }));

    return new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets: formattedDatasets },
      options: {
        ...this.defaultOptions,
        plugins: { ...this.defaultOptions.plugins },
        scales: {
          ...this.defaultOptions.scales,
          y: { ...this.defaultOptions.scales.y, beginAtZero: true }
        }
      }
    });
  },

  // ── LINE CHART: Voting over time
  createLineChart(canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const formattedDatasets = datasets.map((ds, i) => ({
      label: ds.label,
      data: ds.data,
      borderColor: this.palette(datasets.length)[i],
      backgroundColor: this.palette(datasets.length)[i] + '22',
      borderWidth: 2.5,
      pointRadius: 4,
      pointBackgroundColor: this.palette(datasets.length)[i],
      tension: 0.4,
      fill: true,
    }));

    return new Chart(ctx, {
      type: 'line',
      data: { labels, datasets: formattedDatasets },
      options: {
        ...this.defaultOptions,
        plugins: { ...this.defaultOptions.plugins },
      }
    });
  },

  // ── HORIZONTAL BAR: Candidate comparison
  createHorizontalBar(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          data,
          backgroundColor: this.palette(data.length).map(c => c + '88'),
          borderColor: this.palette(data.length),
          borderWidth: 2,
          borderRadius: 6,
        }]
      },
      options: {
        ...this.defaultOptions,
        indexAxis: 'y',
        plugins: { ...this.defaultOptions.plugins, legend: { display: false } },
        scales: {
          x: { ...this.defaultOptions.scales.x, beginAtZero: true },
          y: { ...this.defaultOptions.scales.y }
        }
      }
    });
  },

  // ── Update chart data dynamically
  updateChart(chart, newLabels, newData, datasetIndex = 0) {
    if (!chart) return;
    chart.data.labels = newLabels;
    chart.data.datasets[datasetIndex].data = newData;
    chart.update('active');
  }
};

// ── AUTO-INIT charts from data attributes
document.addEventListener('DOMContentLoaded', () => {
  // Pie charts
  document.querySelectorAll('canvas[data-chart="pie"]').forEach(canvas => {
    const labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
    const data   = JSON.parse(canvas.getAttribute('data-values') || '[]');
    CVSCharts.createPieChart(canvas.id, labels, data);
  });

  // Bar charts
  document.querySelectorAll('canvas[data-chart="bar"]').forEach(canvas => {
    const labels   = JSON.parse(canvas.getAttribute('data-labels') || '[]');
    const datasets = JSON.parse(canvas.getAttribute('data-datasets') || '[]');
    CVSCharts.createBarChart(canvas.id, labels, datasets);
  });

  // Horizontal bar
  document.querySelectorAll('canvas[data-chart="hbar"]').forEach(canvas => {
    const labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
    const data   = JSON.parse(canvas.getAttribute('data-values') || '[]');
    CVSCharts.createHorizontalBar(canvas.id, labels, data);
  });
});
