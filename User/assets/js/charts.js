/**
 * Infinity Scrims — Chart.js helpers (theme-aware).
 * Reads live CSS variables so charts match whichever theme (light/dark)
 * is currently active, and re-render automatically when the user
 * flips Theme.toggle().
 */
function cssVar(name) {
  return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

function makeLineChart(canvasId, labels, datasets) {
  const canvas = document.getElementById(canvasId);
  const ctx = canvas.getContext("2d");

  const gridColor = cssVar("--border");
  const tickColor = cssVar("--text-muted");
  const tooltipBg = cssVar("--surface");
  const tooltipBorder = cssVar("--border");
  const tooltipTitle = cssVar("--text");
  const tooltipBody = cssVar("--text-muted");

  const usesDualAxis = datasets.some((ds) => ds.yAxisID === "y1");

  const chartDatasets = datasets.map((ds) => {
    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, ds.color + "59");
    gradient.addColorStop(1, ds.color + "00");
    return {
      label: ds.label,
      data: ds.data,
      borderColor: ds.color,
      backgroundColor: gradient,
      fill: !!ds.fill,
      tension: 0.35,
      cubicInterpolationMode: "monotone",
      borderWidth: 2,
      // Single data point would otherwise render as nothing — show a dot instead.
      pointRadius: ds.data.length === 1 ? 5 : 0,
      pointHoverRadius: 6,
      pointHoverBackgroundColor: ds.color,
      pointHoverBorderColor: tooltipBg,
      pointHoverBorderWidth: 2,
      yAxisID: ds.yAxisID || "y",
    };
  });

  const scales = {
    x: { grid: { color: gridColor }, ticks: { color: tickColor, font: { size: 11 } } },
    y: {
      position: "left",
      grid: { color: gridColor },
      ticks: { color: tickColor, font: { size: 11 } },
      beginAtZero: true,
    },
  };
  if (usesDualAxis) {
    scales.y1 = { position: "right", grid: { display: false }, ticks: { color: tickColor, font: { size: 11 } } };
  }

  const chart = new Chart(ctx, {
    type: "line",
    data: { labels, datasets: chartDatasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: "index", intersect: false },
      plugins: {
        legend: { display: datasets.length > 1, labels: { color: tickColor, font: { size: 11 }, usePointStyle: true, boxWidth: 8 } },
        tooltip: {
          backgroundColor: tooltipBg,
          borderColor: tooltipBorder,
          borderWidth: 1,
          titleColor: tooltipTitle,
          bodyColor: tooltipBody,
          padding: 10,
          cornerRadius: 8,
        },
      },
      scales,
    },
  });

  registerThemedChart(chart, canvasId);
  return chart;
}

function makeDonutChart(canvasId, labels, values, colors) {
  const canvas = document.getElementById(canvasId);
  const ctx = canvas.getContext("2d");
  const tooltipBg = cssVar("--surface");
  const tooltipBorder = cssVar("--border");
  const tooltipTitle = cssVar("--text");
  const tooltipBody = cssVar("--text-muted");

  const chart = new Chart(ctx, {
    type: "doughnut",
    data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 0 }] },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "68%",
      plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: tooltipBg, borderColor: tooltipBorder, borderWidth: 1, titleColor: tooltipTitle, bodyColor: tooltipBody },
      },
    },
  });

  registerThemedChart(chart, canvasId);
  return chart;
}

// ---------------------------------------------------------------
// Re-theme every registered chart when the user toggles light/dark,
// instead of leaving old colors baked in until a full page reload.
// ---------------------------------------------------------------
const _themedCharts = [];
function registerThemedChart(chart, canvasId) {
  _themedCharts.push({ chart, canvasId });
}

const _origThemeToggle = window.Theme && window.Theme.toggle;
if (window.Theme && typeof _origThemeToggle === "function") {
  window.Theme.toggle = function () {
    _origThemeToggle.call(window.Theme);
    requestAnimationFrame(() => {
      const gridColor = cssVar("--border");
      const tickColor = cssVar("--text-muted");
      const tooltipBg = cssVar("--surface");
      const tooltipBorder = cssVar("--border");
      const tooltipTitle = cssVar("--text");
      const tooltipBody = cssVar("--text-muted");
      _themedCharts.forEach(({ chart }) => {
        if (chart.config.type === "line") {
          if (chart.options.scales.x) { chart.options.scales.x.grid.color = gridColor; chart.options.scales.x.ticks.color = tickColor; }
          if (chart.options.scales.y) { chart.options.scales.y.grid.color = gridColor; chart.options.scales.y.ticks.color = tickColor; }
          if (chart.options.scales.y1) chart.options.scales.y1.ticks.color = tickColor;
          if (chart.options.plugins.legend.labels) chart.options.plugins.legend.labels.color = tickColor;
        }
        chart.options.plugins.tooltip.backgroundColor = tooltipBg;
        chart.options.plugins.tooltip.borderColor = tooltipBorder;
        chart.options.plugins.tooltip.titleColor = tooltipTitle;
        chart.options.plugins.tooltip.bodyColor = tooltipBody;
        chart.update();
      });
    });
  };
}
