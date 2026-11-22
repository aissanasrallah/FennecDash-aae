/*!
 * fennec-charts.js — ApexCharts shared helper
 * Provides dark mode reactivity + Chart.js-to-ApexCharts data transform
 * Author: Fennec Vision (https://www.fennec-vision.com)
 */

/**
 * Transform Chart.js data format to ApexCharts series format.
 * Chart.js: {labels: [...], datasets: [{data: [...], backgroundColor: [...], label: '...'}]}
 * ApexCharts (bar/line): {series: [{name: '...', data: [...]}], xaxis: {categories: [...]}}
 * ApexCharts (donut/pie):  {series: [...], labels: [...], colors: [...]}
 */
function fennecTransformChartJsData(chartJsData, chartType) {
  if (!chartJsData) return { series: [], labels: [] };

  if (chartType === 'donut' || chartType === 'pie') {
    return {
      series: chartJsData.datasets && chartJsData.datasets[0] ? chartJsData.datasets[0].data : [],
      labels: chartJsData.labels || [],
      colors: chartJsData.datasets && chartJsData.datasets[0] ? chartJsData.datasets[0].backgroundColor : undefined
    };
  }

  // bar, line, area
  var series = [];
  if (chartJsData.datasets) {
    for (var i = 0; i < chartJsData.datasets.length; i++) {
      series.push({
        name: chartJsData.datasets[i].label || 'Series ' + (i + 1),
        data: chartJsData.datasets[i].data || []
      });
    }
  }
  return {
    series: series,
    categories: chartJsData.labels || []
  };
}

/**
 * Get current active dark mode state from html[data-bs-theme]
 */
function fennecIsDarkMode() {
  return document.documentElement.getAttribute('data-bs-theme') === 'dark';
}

/**
 * Create an ApexCharts instance with dark mode baked in.
 * Extends user-provided config with theme.mode based on data-bs-theme.
 * Stores reference on element for later destroy/update.
 */
function fennecChart(element, config) {
  if (!element) return null;

  // Merge dark mode
  if (!config.theme) config.theme = {};
  config.theme.mode = fennecIsDarkMode() ? 'dark' : 'light';

  // Merge chart defaults
  if (!config.chart) config.chart = {};
  config.chart.type = config.chart.type || 'bar';

  var chart = new ApexCharts(element, config);
  chart.render();

  // Store reference for destroy on element
  element._apexChart = chart;

  return chart;
}

/**
 * Watch data-bs-theme changes and update all rendered ApexCharts instances.
 * Called once on DOMContentLoaded.
 */
function fennecWatchChartTheme() {
  var observer = new MutationObserver(function() {
    var isDark = fennecIsDarkMode();
    ApexCharts.exec && ApexCharts.exec('theme', isDark ? 'dark' : 'light');
    // Also update each known instance
    document.querySelectorAll('[id$="-chart"], [id$="Chart"], [id$="_chart"]').forEach(function(el) {
      if (el._apexChart) {
        el._apexChart.updateOptions({ theme: { mode: isDark ? 'dark' : 'light' } });
      }
    });
  });
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
}

document.addEventListener('DOMContentLoaded', fennecWatchChartTheme);
