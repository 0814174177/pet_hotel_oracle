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
            new window.Chart(canvas, {
                type: "line",
                data: {
                    labels,
                    datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: "top" },
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true },
                    },
                },
            });

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
            new window.Chart(canvas, {
                type: "doughnut",
                data: {
                    labels,
                    datasets: [
                        {
                            data: values,
                            backgroundColor: colors,
                            borderColor: "#ffffff",
                            borderWidth: 3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                },
            });

            return;
        }

        chart.data.labels = labels;

        if (chart.data.datasets[0]) {
            chart.data.datasets[0].data = values;
            chart.data.datasets[0].backgroundColor = colors;
        }

        chart.update();
    }

    function renderBarChart(chartId, labels, datasets) {
        const canvas = document.getElementById(chartId);

        if (!canvas || !window.Chart) {
            return;
        }

        const chart = typeof window.Chart.getChart === "function"
            ? window.Chart.getChart(canvas)
            : null;

        if (!chart) {
            new window.Chart(canvas, {
                type: "bar",
                data: {
                    labels,
                    datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true },
                    },
                },
            });

            return;
        }

        chart.data.labels = labels;
        chart.data.datasets = datasets;
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

        if (rows.length === 0) {
            const empty = document.createElement("div");

            empty.className = "service-legend-empty";
            empty.textContent = "Chua co du lieu co cau dich vu trong ky nay.";
            legend.appendChild(empty);

            return;
        }

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

    function employeeRows(data) {
        if (Array.isArray(data)) {
            return data;
        }

        if (Array.isArray(data?.items)) {
            return data.items;
        }

        if (Array.isArray(data?.employees)) {
            return data.employees;
        }

        return [];
    }

    function employeeRoleClass(role) {
        const normalized = String(role || "").toLowerCase();

        if (normalized.includes("chính") || normalized.includes("chinh") || normalized.includes("main")) {
            return "role-badge role-badge--main";
        }

        if (normalized.includes("phụ") || normalized.includes("phu") || normalized.includes("assistant")) {
            return "role-badge role-badge--assistant";
        }

        if (normalized.includes("lễ") || normalized.includes("le") || normalized.includes("reception")) {
            return "role-badge role-badge--reception";
        }

        return "role-badge";
    }

    function upsellClass(value) {
        const percent = toNumber(value);

        if (percent >= 70) {
            return "upsell-fill upsell-fill--good";
        }

        if (percent >= 50) {
            return "upsell-fill upsell-fill--warning";
        }

        return "upsell-fill upsell-fill--danger";
    }

    function appendText(parent, tagName, text, className = "") {
        const node = document.createElement(tagName);

        if (className) {
            node.className = className;
        }

        node.textContent = text ?? "";
        parent.appendChild(node);

        return node;
    }

    function renderEmployeePerformance(data) {
        const rows = employeeRows(data);
        const body = root.querySelector("[data-employee-performance-body]");
        const warning = root.querySelector("[data-employee-performance-warning]");
        const warningText = root.querySelector("[data-employee-performance-warning-text]");
        const maxRevenue = Math.max(...rows.map((item) => toNumber(item.revenue ?? item.total_revenue)), 0);
        const hasWarning = rows.some((item) => Boolean(item.alert ?? item.has_alert ?? item.warning));

        if (warning) {
            warning.hidden = !hasWarning;
        }

        if (warningText && hasWarning) {
            warningText.textContent = "Có dấu hiệu bất thường trong hiệu suất nhân sự.";
        }

        if (!body) {
            return;
        }

        body.innerHTML = "";

        if (rows.length === 0) {
            const row = document.createElement("div");
            const info = document.createElement("div");

            row.className = "staff-row";
            info.className = "staff-info";
            appendText(info, "div", "--", "staff-rank");

            const detail = document.createElement("div");
            appendText(detail, "div", "Chưa có dữ liệu hiệu suất nhân sự trong kỳ này.", "staff-name");
            appendText(detail, "div", "--", "role-badge");
            info.appendChild(detail);
            row.appendChild(info);
            appendText(row, "div", "--", "staff-revenue");
            appendText(row, "div", "--", "upsell-value");
            body.appendChild(row);

            return;
        }

        rows.forEach((item, index) => {
            const revenue = toNumber(item.revenue ?? item.total_revenue);
            const upsellRate = toNumber(item.upsell_rate ?? item.upsellRate);
            const revenuePercent = maxRevenue > 0 ? Math.min((revenue / maxRevenue) * 100, 100) : 0;
            const isAlert = Boolean(item.alert ?? item.has_alert ?? item.warning);
            const row = document.createElement("div");
            const info = document.createElement("div");
            const rank = document.createElement("div");
            const detail = document.createElement("div");
            const revenueCell = document.createElement("div");
            const revenueTrack = document.createElement("div");
            const revenueFill = document.createElement("div");
            const upsellCell = document.createElement("div");
            const upsellTrack = document.createElement("div");
            const upsellFill = document.createElement("div");

            row.className = isAlert
                ? "staff-row staff-row--alert"
                : index === 0
                  ? "staff-row staff-row--top"
                  : "staff-row";
            info.className = "staff-info";
            rank.className = index === 0 ? "staff-rank staff-rank--top" : "staff-rank";
            rank.textContent = String(index + 1);

            if (isAlert) {
                appendText(rank, "span", "", "staff-alert-dot");
            }

            appendText(
                detail,
                "div",
                item.employee_name || item.full_name || item.name || "Nhân sự chưa xác định",
                "staff-name",
            );
            appendText(detail, "div", item.role || item.position || "--", employeeRoleClass(item.role || item.position));
            info.appendChild(rank);
            info.appendChild(detail);

            appendText(revenueCell, "div", `${formatMillion(revenue)}tr`, "staff-revenue");
            revenueTrack.className = "staff-revenue-track";
            revenueFill.style.width = `${revenuePercent}%`;
            revenueTrack.appendChild(revenueFill);
            revenueCell.appendChild(revenueTrack);

            appendText(upsellCell, "div", `${formatNumber(upsellRate, { maximumFractionDigits: 2 })}%`, "upsell-value");
            upsellTrack.className = "upsell-track";
            upsellFill.className = upsellClass(upsellRate);
            upsellFill.style.width = `${Math.max(0, Math.min(upsellRate, 100))}%`;
            upsellTrack.appendChild(upsellFill);
            upsellCell.appendChild(upsellTrack);

            row.appendChild(info);
            row.appendChild(revenueCell);
            row.appendChild(upsellCell);
            body.appendChild(row);
        });
    }

    function retentionRows(data) {
        if (Array.isArray(data)) {
            return data;
        }

        if (Array.isArray(data?.items)) {
            return data.items;
        }

        if (Array.isArray(data?.retention)) {
            return data.retention;
        }

        return [];
    }

    function renderCustomerRetention(data) {
        const rows = retentionRows(data);
        const rateNode = root.querySelector("[data-retention-rate]");
        const trendNode = root.querySelector("[data-retention-trend]");
        const latestRow = rows[rows.length - 1] || {};
        const latestRate = data?.retention_rate ?? data?.returning_rate ?? latestRow.rate ?? latestRow.retention_rate;
        const trendValue = data?.retention_trend ?? data?.trend_percent ?? data?.growth_percent;
        const labels = rows.map((item) => item.month || item.period || item.label || formatChartDate(item.date));
        const values = rows.map((item) => toNumber(item.rate ?? item.retention_rate ?? item.value));

        if (rateNode) {
            rateNode.classList.remove("customer-rate--positive", "customer-rate--negative");
            rateNode.textContent = latestRate === undefined || latestRate === null
                ? "--"
                : `${formatNumber(latestRate, { maximumFractionDigits: 2 })}%`;
        }

        if (trendNode) {
            const trendNumber = Number(trendValue);

            trendNode.classList.remove("customer-trend--positive", "customer-trend--negative");

            if (Number.isFinite(trendNumber)) {
                trendNode.classList.add(trendNumber >= 0 ? "customer-trend--positive" : "customer-trend--negative");
                trendNode.textContent = `${trendNumber >= 0 ? "▲" : "▼"} ${formatNumber(Math.abs(trendNumber), { maximumFractionDigits: 2 })}%`;
            } else {
                trendNode.textContent = "Chưa có dữ liệu";
            }
        }

        setText(
            "[data-new-customers]",
            data?.new_customers === undefined ? "--" : formatNumber(data.new_customers, { maximumFractionDigits: 0 }),
        );
        setText(
            "[data-loyal-customers]",
            data?.loyal_customers === undefined ? "--" : formatNumber(data.loyal_customers, { maximumFractionDigits: 0 }),
        );

        renderBarChart("managerCustomerRetentionChart", labels, [
            {
                label: "Ty le quay lai",
                data: values,
                backgroundColor: "#3B82F6",
                borderColor: "#3B82F6",
                borderRadius: 4,
            },
        ]);
    }

    $(document).ready(function () {
        DashboardEngine.run([
            { url: root.dataset.targetProgressUrl, onSuccess: renderTargetProgress },
            { url: root.dataset.revenueComparisonUrl, onSuccess: renderRevenueComparisonChart },
            { url: root.dataset.serviceMixUrl, onSuccess: renderServiceMix },
            { url: root.dataset.aovUrl, onSuccess: renderAovSummary },
            { url: root.dataset.employeePerformanceUrl, onSuccess: renderEmployeePerformance },
            { url: root.dataset.customerRetentionUrl, onSuccess: renderCustomerRetention },
        ]);
    });
})(window.jQuery);
