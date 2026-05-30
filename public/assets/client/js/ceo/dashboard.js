(function () {
    const root = document.getElementById("ceoDashboard");

    if (!root) {
        return;
    }

    const api = window.CeoDashboardApi.create(root);
    const chartInstances = new Map();
    const periodLabels = {
        day: "ngày",
        month: "tháng",
        year: "năm",
    };

    function toNumber(value) {
        const number = Number(value);

        return Number.isFinite(number) ? number : 0;
    }

    function formatNumber(value, options = {}) {
        return new Intl.NumberFormat("vi-VN", options).format(toNumber(value));
    }

    function formatCurrency(value) {
        return `${formatNumber(value, { maximumFractionDigits: 0 })} ₫`;
    }

    function formatPercent(value) {
        return `${formatNumber(value, { maximumFractionDigits: 2 })}%`;
    }

    function formatDate(value) {
        if (!value) {
            return "";
        }

        const [year, month, day] = String(value).split("-");

        return day && month && year ? `${day}/${month}` : String(value);
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function statusNode() {
        return document.getElementById("dashboardStatus");
    }

    function setStatus(message, type = "loading") {
        const node = statusNode();

        if (!node) {
            return;
        }

        node.textContent = message;
        node.className = `dashboard-status dashboard-status--${type}`;
    }

    function setLoading(isLoading) {
        root.classList.toggle("dashboard-overview-page--loading", isLoading);
        root.querySelectorAll(".global-control-panel__period-btn, .global-control-panel__refresh-btn")
            .forEach((button) => {
                button.disabled = isLoading;
            });
    }

    function updateLastRefresh() {
        const node = root.querySelector(".global-control-panel__last-update span:last-child");

        if (!node) {
            return;
        }

        const time = new Intl.DateTimeFormat("vi-VN", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        }).format(new Date());

        node.textContent = `${time} - Cập nhật thành công`;
    }

    function renderKpi(id, value, changePercent, options = {}) {
        const card = document.getElementById(id);

        if (!card) {
            return;
        }

        const valueNode = card.querySelector("[data-kpi-value]");
        const trendNode = card.querySelector("[data-kpi-trend]");
        const arrowNode = card.querySelector("[data-kpi-arrow]");
        const trendValueNode = card.querySelector("[data-kpi-trend-value]");

        valueNode.textContent = options.formatValue ? options.formatValue(value) : formatNumber(value);

        if (changePercent === null || typeof changePercent === "undefined") {
            trendNode.className = "kpi-card__trend kpi-card__trend--neutral";
            arrowNode.textContent = "=";
            trendValueNode.textContent = "Chưa có dữ liệu kỳ trước";

            return;
        }

        const numericChange = toNumber(changePercent);
        const isIncrease = numericChange > 0;
        const isDecrease = numericChange < 0;
        const isPositive = options.positiveWhenIncrease === false ? !isIncrease : !isDecrease;

        trendNode.className = `kpi-card__trend ${
            numericChange === 0
                ? "kpi-card__trend--neutral"
                : (isPositive ? "kpi-card__trend--positive" : "kpi-card__trend--negative")
        }`;
        arrowNode.textContent = isIncrease ? "▲" : (isDecrease ? "▼" : "=");
        trendValueNode.textContent = formatPercent(Math.abs(numericChange));
    }

    function renderCurrentOccupancy(data) {
        const detailNode = document.querySelector("#occupancyRateKpi [data-kpi-detail]");

        if (!detailNode) {
            return;
        }

        detailNode.textContent = `${formatNumber(data.occupied_room)} phòng đang sử dụng / ${formatNumber(data.usable_room)} phòng khả dụng`;
    }

    function renderOccupancyRate(data) {
        renderKpi("occupancyRateKpi", data.current?.occupancy_rate, data.comparison?.change_percent, {
            formatValue: formatPercent,
        });
    }

    function renderRevpar(data) {
        renderKpi("revparKpi", data.current?.revpar, data.comparison?.change_percent, {
            formatValue: formatCurrency,
        });

        const detailNode = document.querySelector("#revparKpi [data-kpi-detail]");

        if (detailNode) {
            detailNode.textContent = `Doanh thu phòng: ${formatCurrency(data.current?.total_room_revenue)}`;
        }
    }

    function renderTotalRevenue(data) {
        renderKpi("totalRevenueKpi", data.current_total_revenue, data.revenue_growth_percent, {
            formatValue: formatCurrency,
        });
    }

    function renderEstimatedCogs(data) {
        renderKpi("estimatedCogsKpi", data.current?.estimated_cogs, data.comparison?.growth_percent, {
            formatValue: formatCurrency,
            positiveWhenIncrease: false,
        });
    }

    function baseChartOptions(formatValue) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: "index",
            },
            plugins: {
                legend: {
                    position: "bottom",
                    labels: {
                        color: "#374151",
                        boxWidth: 12,
                        usePointStyle: true,
                    },
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const label = context.dataset.label || context.label || "";
                            const value = typeof context.parsed?.y !== "undefined"
                                ? context.parsed.y
                                : context.parsed;

                            return `${label}: ${formatValue(value)}`;
                        },
                    },
                },
            },
        };
    }

    function gridChartOptions(formatValue) {
        return {
            ...baseChartOptions(formatValue),
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: formatValue,
                    },
                },
            },
        };
    }

    function renderChart(id, config) {
        const canvas = document.getElementById(id);

        if (!canvas || !window.Chart) {
            return;
        }

        chartInstances.get(id)?.destroy();
        chartInstances.set(id, new window.Chart(canvas, config));
    }

    function renderCustomerTrend(data) {
        renderChart("customerTrendChart", {
            type: "line",
            data: {
                labels: data.map((row) => formatDate(row.report_date)),
                datasets: [
                    {
                        label: "Khách hàng",
                        data: data.map((row) => toNumber(row.customer_count)),
                        borderColor: "#2563eb",
                        backgroundColor: "#2563eb",
                        tension: 0.25,
                    },
                    {
                        label: "Lượt booking",
                        data: data.map((row) => toNumber(row.booking_count)),
                        borderColor: "#0f766e",
                        backgroundColor: "#0f766e",
                        tension: 0.25,
                    },
                ],
            },
            options: gridChartOptions((value) => formatNumber(value)),
        });
    }

    function renderRevenueMix(data) {
        renderChart("revenueMixChart", {
            type: "doughnut",
            data: {
                labels: data.map((row) => row.revenue_group),
                datasets: [
                    {
                        label: "Doanh thu",
                        data: data.map((row) => toNumber(row.revenue_amount)),
                        backgroundColor: ["#2563eb", "#10b981"],
                        borderColor: "#ffffff",
                        borderWidth: 3,
                    },
                ],
            },
            options: {
                ...baseChartOptions(formatCurrency),
                cutout: "62%",
            },
        });
    }

    function renderRevenueCogsTrend(data) {
        renderChart("revenueCogsTrendChart", {
            type: "line",
            data: {
                labels: data.map((row) => formatDate(row.report_date)),
                datasets: [
                    {
                        label: "Doanh thu",
                        data: data.map((row) => toNumber(row.revenue)),
                        borderColor: "#2563eb",
                        backgroundColor: "#2563eb",
                        tension: 0.25,
                    },
                    {
                        label: "COGS ước tính",
                        data: data.map((row) => toNumber(row.estimated_cogs)),
                        borderColor: "#dc2626",
                        backgroundColor: "#dc2626",
                        tension: 0.25,
                    },
                ],
            },
            options: gridChartOptions(formatCurrency),
        });
    }

    function renderBranchRevenue(data) {
        renderChart("branchRevenueChart", {
            type: "bar",
            data: {
                labels: data.map((row) => row.branch_name),
                datasets: [
                    {
                        label: "Doanh thu (triệu VNĐ)",
                        data: data.map((row) => toNumber(row.revenue_million_vnd)),
                        backgroundColor: "#2563eb",
                        borderRadius: 4,
                    },
                ],
            },
            options: gridChartOptions((value) => formatNumber(value, { maximumFractionDigits: 2 })),
        });
    }

    function renderBranchRanking(data) {
        const list = document.getElementById("branchRankingList");

        if (!list) {
            return;
        }

        list.innerHTML = data.length
            ? data.map((branch) => `
                <li>
                    <span>${escapeHtml(branch.rank_no)}. ${escapeHtml(branch.branch_name)}</span>
                    <strong>${escapeHtml(formatNumber(branch.revenue_million_vnd, { maximumFractionDigits: 2 }))}M</strong>
                </li>
            `).join("")
            : '<li class="dashboard-board-card__placeholder">Chưa có dữ liệu doanh thu.</li>';
    }

    function renderTopUsedServices(data) {
        const list = document.getElementById("topUsedServicesList");

        if (!list) {
            return;
        }

        list.innerHTML = data.length
            ? data.map((service) => `
                <li>
                    <span>${escapeHtml(service.rank_no)}. ${escapeHtml(service.service_name)}</span>
                    <strong>${escapeHtml(formatNumber(service.usage_count))} lượt</strong>
                </li>
            `).join("")
            : '<li class="dashboard-board-card__placeholder">Chưa có dịch vụ phát sinh.</li>';
    }

    function riskAlertMarkup(alert) {
        const alertClass = alert.alert_level === "HIGH" ? "danger" : "warning";

        return `
            <div class="alert-box-wrapper">
                <div class="alert-box alert-box--${alertClass}">
                    <h4 class="alert-box__header">
                        <span class="alert-box__icon" aria-hidden="true">!</span>
                        ${escapeHtml(alert.title)}
                    </h4>
                    <p class="alert-box__message">${escapeHtml(alert.warning_text)}</p>
                    <p class="dashboard-alert-meta">
                        ${escapeHtml(alert.alert_level)} · ${escapeHtml(alert.created_at || "Không có thời gian")}
                    </p>
                </div>
            </div>
        `;
    }

    function renderRiskAlerts(data) {
        const list = document.getElementById("dashboardRiskAlerts");
        const operationalAlert = document.getElementById("dashboardOperationalAlert");
        const operational = data.find((alert) => [
            "CANCEL_RATE_HIGH",
            "HIGH_VALUE_CANCELLED_BOOKING",
        ].includes(alert.alert_type)) || data[0];

        if (list) {
            list.innerHTML = data.length
                ? data.map(riskAlertMarkup).join("")
                : '<div class="dashboard-alert-empty">Không có cảnh báo rủi ro trong kỳ báo cáo.</div>';
        }

        if (!operationalAlert) {
            return;
        }

        const box = operationalAlert.querySelector(".alert-box");
        const title = operationalAlert.querySelector("[data-alert-title]");
        const message = operationalAlert.querySelector("[data-alert-message]");

        if (!operational) {
            box.className = "alert-box alert-box--warning";
            title.textContent = "Không có cảnh báo vận hành";
            message.textContent = "Các chỉ số vận hành trong kỳ chưa vượt ngưỡng cảnh báo.";

            return;
        }

        box.className = `alert-box alert-box--${operational.alert_level === "HIGH" ? "danger" : "warning"}`;
        title.textContent = operational.title;
        message.textContent = operational.warning_text;
    }

    function activatePeriodButton(filterType) {
        root.querySelectorAll(".global-control-panel__period-btn").forEach((button) => {
            const buttonPeriod = Object.entries(periodLabels)
                .find(([, label]) => label === button.textContent.trim().toLowerCase())?.[0];

            button.classList.toggle("global-control-panel__period-btn--active", buttonPeriod === filterType);
        });
    }

    function bindControls() {
        root.querySelectorAll(".global-control-panel__period-btn").forEach((button) => {
            button.addEventListener("click", () => {
                const filterType = Object.entries(periodLabels)
                    .find(([, label]) => label === button.textContent.trim().toLowerCase())?.[0];

                if (!filterType) {
                    return;
                }

                api.setFilterType(filterType);
                activatePeriodButton(filterType);
                loadDashboard();
            });
        });

        root.querySelector(".global-control-panel__refresh-btn")?.addEventListener("click", loadDashboard);
        root.querySelector(".global-control-panel__export-btn")?.addEventListener("click", () => window.print());
    }

    async function loadDashboard() {
        setLoading(true);
        setStatus("Đang tải dữ liệu báo cáo...");

        const { data, errors } = await api.getAll();

        if (data.currentOccupancy) renderCurrentOccupancy(data.currentOccupancy);
        if (data.occupancyRate) renderOccupancyRate(data.occupancyRate);
        if (data.revpar) renderRevpar(data.revpar);
        if (data.customerTrend) renderCustomerTrend(data.customerTrend);
        if (data.totalRevenue) renderTotalRevenue(data.totalRevenue);
        if (data.estimatedCogs) renderEstimatedCogs(data.estimatedCogs);
        if (data.revenueMix) renderRevenueMix(data.revenueMix);
        if (data.revenueCogsTrend) renderRevenueCogsTrend(data.revenueCogsTrend);
        if (data.branchRevenue) renderBranchRevenue(data.branchRevenue);
        if (data.branchRanking) renderBranchRanking(data.branchRanking);
        if (data.topUsedServices) renderTopUsedServices(data.topUsedServices);
        if (data.riskAlerts) renderRiskAlerts(data.riskAlerts);

        setLoading(false);

        if (errors.length) {
            setStatus(`Một số dữ liệu chưa tải được: ${errors[0]}`, "error");
        } else {
            setStatus("Dữ liệu dashboard đã được cập nhật.", "success");
            updateLastRefresh();
        }
    }

    bindControls();
    activatePeriodButton(api.getFilterType());
    loadDashboard();
})();
