(function () {
    const DEFAULT_COLORS = ["#3B82F6", "#10B981", "#F59E0B", "#EF4444", "#8B5CF6"];
    const chartInstances = new Map();

    const formatters = {
        raw: (value) => value,
        number: (value) => new Intl.NumberFormat("vi-VN").format(value),
        currency: (value) => `${new Intl.NumberFormat("vi-VN").format(value)}\u0111`,
        compact: (value) =>
            new Intl.NumberFormat("vi-VN", {
                notation: "compact",
                maximumFractionDigits: 1,
            }).format(value),
        million: (value) => `${new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 1 }).format(value / 1000000)}M`,
        percent: (value) => `${value}%`,
    };

    function toNumber(value) {
        const number = Number(value);
        return Number.isFinite(number) ? number : 0;
    }

    function getValue(row, key) {
        if (!row || key === undefined || key === null) {
            return "";
        }

        return row[key];
    }

    function getFormatter(name) {
        return typeof formatters[name] === "function" ? formatters[name] : formatters.raw;
    }

    function baseOptions(formatValue) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: "index",
            },
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        color: "#374151",
                        boxWidth: 12,
                        boxHeight: 12,
                        usePointStyle: true,
                        font: {
                            size: 12,
                            weight: "600",
                        },
                    },
                },
                tooltip: {
                    backgroundColor: "#111827",
                    titleColor: "#ffffff",
                    bodyColor: "#ffffff",
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: (context) => {
                            const label = context.dataset.label || context.label || "";
                            const value = context.parsed && typeof context.parsed.y !== "undefined"
                                ? context.parsed.y
                                : context.parsed;

                            return `${label}: ${formatValue(value)}`;
                        },
                    },
                },
            },
        };
    }

    function gridOptions(formatValue, stacked) {
        return {
            ...baseOptions(formatValue),
            scales: {
                x: {
                    stacked,
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: "#6B7280",
                        font: {
                            size: 12,
                        },
                    },
                },
                y: {
                    stacked,
                    beginAtZero: true,
                    grid: {
                        color: "#E5E7EB",
                        drawBorder: false,
                    },
                    ticks: {
                        color: "#6B7280",
                        font: {
                            size: 12,
                        },
                        callback: (value) => formatValue(value),
                    },
                },
            },
        };
    }

    function buildBarConfig(config) {
        const data = Array.isArray(config.data) ? config.data : [];
        const series = Array.isArray(config.series) ? config.series : [];
        const formatValue = getFormatter(config.formatter);
        const stacked = Boolean(config.stacked);

        return {
            type: "bar",
            data: {
                labels: data.map((row) => getValue(row, config.xAxisKey || "name")),
                datasets: series.map((item, index) => {
                    const color = item.color || DEFAULT_COLORS[index % DEFAULT_COLORS.length];

                    return {
                        label: item.name || item.key,
                        data: data.map((row) => toNumber(getValue(row, item.key))),
                        backgroundColor: color,
                        borderColor: color,
                        borderRadius: 4,
                        barThickness: 40,
                        stack: stacked ? "stack-0" : undefined,
                    };
                }),
            },
            options: gridOptions(formatValue, stacked),
        };
    }

    function buildLineConfig(config) {
        const data = Array.isArray(config.data) ? config.data : [];
        const series = Array.isArray(config.series) ? config.series : [];
        const formatValue = getFormatter(config.formatter);

        return {
            type: "line",
            data: {
                labels: data.map((row) => getValue(row, config.xAxisKey || "name")),
                datasets: series.map((item, index) => {
                    const color = item.color || DEFAULT_COLORS[index % DEFAULT_COLORS.length];

                    return {
                        label: item.name || item.key,
                        data: data.map((row) => toNumber(getValue(row, item.key))),
                        borderColor: color,
                        backgroundColor: color,
                        borderWidth: 3,
                        tension: 0,
                        pointRadius: 4,
                        pointHoverRadius: 8,
                        fill: false,
                    };
                }),
            },
            options: gridOptions(formatValue, false),
        };
    }

    function buildPieConfig(config) {
        const data = Array.isArray(config.data) ? config.data : [];
        const colors = Array.isArray(config.colors) && config.colors.length ? config.colors : DEFAULT_COLORS;
        const formatValue = getFormatter(config.formatter);
        const options = baseOptions(formatValue);

        options.cutout = "62%";
        options.plugins.legend.position = "bottom";

        return {
            type: "doughnut",
            data: {
                labels: data.map((row) => getValue(row, config.nameKey || "name")),
                datasets: [
                    {
                        data: data.map((row) => toNumber(getValue(row, config.dataKey || "value"))),
                        backgroundColor: data.map((_, index) => colors[index % colors.length]),
                        borderColor: "#ffffff",
                        borderWidth: 3,
                        spacing: 4,
                    },
                ],
            },
            options,
        };
    }

    function buildChartConfig(config) {
        if (config.type === "line") {
            return buildLineConfig(config);
        }

        if (config.type === "pie") {
            return buildPieConfig(config);
        }

        return buildBarConfig(config);
    }

    function getConfigForCanvas(canvas) {
        const configNode = document.querySelector(`script[data-chart-config-for="${canvas.id}"]`);

        if (!configNode) {
            return null;
        }

        try {
            return JSON.parse(configNode.textContent || "{}");
        } catch (error) {
            console.error("Invalid chart config", error);
            return null;
        }
    }

    function initCharts() {
        if (!window.Chart) {
            return;
        }

        document.querySelectorAll("canvas[data-generic-chart]").forEach((canvas) => {
            const config = getConfigForCanvas(canvas);

            if (!config) {
                return;
            }

            if (chartInstances.has(canvas.id)) {
                chartInstances.get(canvas.id).destroy();
            }

            chartInstances.set(canvas.id, new window.Chart(canvas, buildChartConfig(config)));
        });
    }

    window.PetHotelCharts = {
        init: initCharts,
        formatters,
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initCharts);
    } else {
        initCharts();
    }
})();
