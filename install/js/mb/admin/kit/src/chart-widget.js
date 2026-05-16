const charts = new WeakMap();

function toNumber(value, fallback) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
}

function resolveChartCtor() {
  if (typeof window === "undefined") {
    return null;
  }

  return window.Chart || null;
}

function initNode(node) {
  if (!(node instanceof HTMLCanvasElement)) {
    return;
  }

  const ChartCtor = resolveChartCtor();
  if (!ChartCtor) {
    return;
  }

  const rawConfig = node.getAttribute("data-adminkit-chart");
  if (!rawConfig) {
    return;
  }

  let config;
  try {
    config = JSON.parse(rawConfig);
  } catch {
    return;
  }

  const heightFromAttr = toNumber(node.getAttribute("data-adminkit-chart-height"), 300);
  const wrapper = node.closest(".adminkit-chart-widget__canvas");
  if (wrapper instanceof HTMLElement) {
    wrapper.style.setProperty("--adminkit-chart-height", `${heightFromAttr}px`);
  }

  const existing = charts.get(node);
  if (existing && typeof existing.destroy === "function") {
    existing.destroy();
  }

  charts.set(node, new ChartCtor(node, config));
}

function init(root = document) {
  if (!root) {
    return;
  }

  root.querySelectorAll("canvas[data-adminkit-chart]").forEach((node) => {
    initNode(node);
  });
}

export { init };
