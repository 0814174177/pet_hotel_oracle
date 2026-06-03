(function ($) {
    const root = document.getElementById("managerRevenueReportPage");

    if (!root || !window.DashboardEngine) {
        return;
    }

    function setText(selector, value) {
        const node = root.querySelector(selector);

        if (node && value !== undefined && value !== null && value !== "") {
            node.textContent = value;
        }
    }

    function toNumber(value) {
        const number = Number(value);

        return Number.isFinite(number) ? number : 0;
    }

    function formatNumber(value, options = {}) {
        if (value === undefined || value === null || value === "") {
            return "-";
        }

        return new Intl.NumberFormat("vi-VN", options).format(toNumber(value));
    }

    function formatMillion(value, options = {}) {
        return formatNumber(toNumber(value) / 1000000, {
            maximumFractionDigits: 1,
            ...options,
        });
    }

    function formatThousand(value, options = {}) {
        return `${formatNumber(toNumber(value) / 1000, {
            maximumFractionDigits: 1,
            ...options,
        })}k`;
    }

    function targetStateClass(warningLevel) {
        const level = String(warningLevel || "").toLowerCase();

        if (level === "success") {
            return "good";
        }

        if (level === "warning") {
            return "warning";
        }

        return "danger";
    }

    function updateClassByState(selector, baseClass, states, activeState) {
        const node = root.querySelector(selector);

        if (!node) {
            return;
        }

        states.forEach((state) => node.classList.remove(`${baseClass}--${state}`));
        node.classList.add(`${baseClass}--${activeState}`);
    }

    function formatChartDate(value) {
        if (!value) {
            return "";
        }

        const parts = String(value).split("-");

        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}`;
        }

        return String(value);
    }

    function chartRevenueValue(value) {
        return Number((toNumber(value) / 1000000).toFixed(2));
    }

    function serviceMixName(item) {
        return item?.revenue_group || item?.name || item?.label || "";
    }

    function serviceMixPercent(item) {
        return item?.percent_of_total ?? item?.value ?? 0;
    }

    function serviceMixColor(name, index) {
        const normalized = String(name || "").toLowerCase();
        const colors = ["#3b82f6", "#8b5cf6", "#f59e0b", "#10b981", "#64748b"];

        if (normalized.includes("chó") || normalized.includes("cho") || normalized.includes("dog")) {
            return "#3b82f6";
        }

        if (normalized.includes("mèo") || normalized.includes("meo") || normalized.includes("cat")) {
            return "#8b5cf6";
        }

        if (normalized.includes("spa") || normalized.includes("groom")) {
            return "#f59e0b";
        }

        if (normalized.includes("thú y") || normalized.includes("thu y") || normalized.includes("khám")) {
            return "#10b981";
        }

        return colors[index % colors.length];
    }

    function serviceMixIcon(name) {
        const normalized = String(name || "").toLowerCase();

        if (normalized.includes("chó") || normalized.includes("cho") || normalized.includes("dog")) {
            return "🐕";
        }

        if (normalized.includes("mèo") || normalized.includes("meo") || normalized.includes("cat")) {
            return "🐱";
        }

        if (normalized.includes("spa") || normalized.includes("groom")) {
            return "✂️";
        }

        if (normalized.includes("thú y") || normalized.includes("thu y") || normalized.includes("khám")) {
            return "💊";
        }

        return "•";
    }

    function renderLineChart(chartId, labels, datasets) {
        const canvas = document.getElementById(chartId);

        if (!canvas || !window.Chart) {
            return;
        }

        const chart = typeof window.Chart.getChart === "function"
            ? window.Chart.getChart(canvas)
            : null;

        if (!chart) {
            return;
        }

        chart.data.labels = labels;
        chart.data.datasets = datasets;
        chart.update();
    }

    function renderPieChart(chartId, labels, values, colors) {
        const canvas = document.getElementById(chartId);

        if (!canvas || !window.Chart) {
            return;
        }

        const chart = typeof window.Chart.getChart === "function"
            ? window.Chart.getChart(canvas)
            : null;

        if (!chart) {
            return;
        }

        chart.data.labels = labels;

        if (chart.data.datasets[0]) {
            chart.data.datasets[0].data = values;
            chart.data.datasets[0].backgroundColor = colors;
        }

        chart.update();
    }

    /**
     * Mo ta chuc nang:
     * Render KPI tien do muc tieu doanh thu thang len card co san.
     *
     * Input:
     * - data.current_revenue, data.target_month, data.remaining_to_target.
     * - data.progress_percent, data.required_revenue_per_day, data.days_left.
     * - data.target_warning va data.warning_level.
     *
     * Output:
     * - Cap nhat text, width progress bar va mau canh bao tren DOM.
     *
     * Ghi chu:
     * - Khong fetch rieng, du lieu duoc DashboardEngine truyen vao.
     */
    function renderTargetProgress(data) {
        if (!data) {
            return;
        }

        const progressPercent = data.progress_percent;
        const boundedProgress = Math.max(0, Math.min(toNumber(progressPercent), 100));
        const state = targetStateClass(data.warning_level);
        const progressFill = root.querySelector("[data-target-progress-fill]");

        setText("[data-target-current]", formatMillion(data.current_revenue));
        setText("[data-target-month]", formatMillion(data.target_month, { maximumFractionDigits: 0 }));
        setText("[data-target-progress]", `${formatNumber(progressPercent, { maximumFractionDigits: 2 })}%`);
        setText("[data-target-remaining]", `${formatMillion(Math.max(toNumber(data.remaining_to_target), 0))}tr`);
        setText("[data-target-daily-needed]", `${formatMillion(data.required_revenue_per_day)}tr`);
        setText("[data-target-days-left]", formatNumber(data.days_left, { maximumFractionDigits: 0 }));
        setText("[data-target-warning]", data.target_warning);

        if (progressFill) {
            progressFill.style.width = `${boundedProgress}%`;
        }

        updateClassByState(
            "[data-target-progress-badge]",
            "progress-badge",
            ["good", "warning", "danger"],
            state,
        );
        updateClassByState(
            "[data-target-progress-fill]",
            "progress-fill",
            ["good", "warning", "danger"],
            state,
        );
        updateClassByState(
            "[data-target-warning-badge]",
            "progress-badge",
            ["good", "warning", "danger"],
            state,
        );
    }

    /**
     * Mo ta chuc nang:
     * Render bieu do so sanh doanh thu theo ngay trong ky.
     *
     * Input:
     * - data[] gom revenue_date, current_revenue, previous_revenue,
     *   growth_percent tu API.
     *
     * Output:
     * - Cap nhat line chart co san tren trang Manager revenue report.
     *
     * Ghi chu:
     * - Khong fetch rieng, chi nhan data tu DashboardEngine.run.
     */
    function renderRevenueComparisonChart(data) {
        const rows = Array.isArray(data) ? data : [];
        const labels = rows.map((item) => formatChartDate(item.revenue_date));

        renderLineChart("managerRevenueComparisonChart", labels, [
            {
                label: "Kỳ này (tr)",
                data: rows.map((item) => chartRevenueValue(item.current_revenue)),
                borderColor: "#f59e0b",
                backgroundColor: "#f59e0b",
                borderWidth: 3,
                tension: 0,
                pointRadius: 4,
                pointHoverRadius: 8,
                fill: false,
            },
            {
                label: "Cùng kỳ trước (tr)",
                data: rows.map((item) => chartRevenueValue(item.previous_revenue)),
                borderColor: "#475569",
                backgroundColor: "#475569",
                borderWidth: 3,
                tension: 0,
                pointRadius: 4,
                pointHoverRadius: 8,
                fill: false,
            },
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Render Service Mix va co cau doanh thu theo nhom dich vu.
     *
     * Input:
     * - data[] hoac data.service_mix[] gom revenue_group, revenue, percent_of_total.
     *
     * Output:
     * - Cap nhat pie chart, legend/table va insight Hotel/Spa co san.
     *
     * Ghi chu:
     * - Khong fetch rieng, chi nhan data tu DashboardEngine.run.
     */
    function renderServiceMix(data) {
        const rows = Array.isArray(data)
            ? data
            : Array.isArray(data?.service_mix)
              ? data.service_mix
              : [];
        const labels = rows.map((item) => serviceMixName(item));
        const values = rows.map((item) => toNumber(serviceMixPercent(item)));
        const colors = rows.map((item, index) => serviceMixColor(serviceMixName(item), index));
        const hotelShare = rows
            .filter((item) => /hotel/i.test(serviceMixName(item)))
            .reduce((total, item) => total + toNumber(serviceMixPercent(item)), 0);
        const spa = rows.find((item) => /spa|groom/i.test(serviceMixName(item)));

        setText("[data-hotel-share]", `${formatNumber(hotelShare, { maximumFractionDigits: 2 })}%`);
        setText("[data-spa-share]", `${formatNumber(serviceMixPercent(spa), { maximumFractionDigits: 2 })}%`);
        renderPieChart("managerServiceMixChart", labels, values, colors);
        renderServiceMixLegend(rows, colors);
    }

    function renderServiceMixLegend(rows, colors) {
        const legend = root.querySelector("[data-service-mix-legend]");

        if (!legend) {
            return;
        }

        legend.innerHTML = "";

        rows.forEach((item, index) => {
            const name = serviceMixName(item);
            const color = colors[index] || serviceMixColor(name, index);
            const row = document.createElement("div");
            const label = document.createElement("div");
            const dot = document.createElement("span");
            const percent = document.createElement("strong");

            row.className = "service-legend-item";
            dot.style.background = color;
            label.appendChild(dot);
            label.appendChild(document.createTextNode(
                `${serviceMixIcon(name)} ${name} - ${formatMillion(item.revenue)}tr`,
            ));
            percent.style.color = color;
            percent.textContent = `${formatNumber(serviceMixPercent(item), { maximumFractionDigits: 2 })}%`;
            row.appendChild(label);
            row.appendChild(percent);
            legend.appendChild(row);
        });
    }

    function setAovValue(key, value) {
        setText(`[data-aov-value="${key}"]`, value);
    }

    /**
     * Mo ta chuc nang:
     * Render AOV, so don va gia tri don cao nhat len KPI card co san.
     *
     * Input:
     * - data.current_orders, data.current_aov, data.previous_aov.
     * - data.aov_growth_percent, data.max_order_value va data.trend.
     *
     * Output:
     * - Cap nhat 4 card AOV trong phan Service Mix & AOV.
     *
     * Ghi chu:
     * - Khong fetch rieng, chi nhan data tu DashboardEngine.run.
     */
    function renderAovSummary(data) {
        if (!data) {
            return;
        }

        const currentCard = root.querySelector('[data-aov-card="current_aov"]');
        const changeNode = currentCard?.querySelector("[data-aov-change]");
        const growthPercent = data.aov_growth_percent;
        const trend = String(data.trend || "").toLowerCase();
        const arrow = trend === "down" ? "▼" : trend === "up" ? "▲" : "=";

        setAovValue("current_aov", formatThousand(data.current_aov));
        setAovValue("previous_aov", formatThousand(data.previous_aov));
        setAovValue("max_order_value", formatThousand(data.max_order_value, { maximumFractionDigits: 0 }));
        setAovValue("current_orders", `${formatNumber(data.current_orders, { maximumFractionDigits: 0 })} đơn`);

        if (changeNode) {
            changeNode.textContent = growthPercent === null || typeof growthPercent === "undefined"
                ? "Chưa có dữ liệu kỳ trước"
                : `${arrow} ${formatNumber(Math.abs(toNumber(growthPercent)), { maximumFractionDigits: 2 })}% so tháng trước`;
        }
    }

    $(document).ready(function () {
        DashboardEngine.run([
            { url: root.dataset.targetProgressUrl, onSuccess: renderTargetProgress },
            { url: root.dataset.revenueComparisonUrl, onSuccess: renderRevenueComparisonChart },
            { url: root.dataset.serviceMixUrl, onSuccess: renderServiceMix },
            { url: root.dataset.aovUrl, onSuccess: renderAovSummary },
            { url: root.dataset.employeePerformanceUrl, onSuccess: function () {} },
            { url: root.dataset.customerRetentionUrl, onSuccess: function () {} },
        ]);
    });
})(window.jQuery);
