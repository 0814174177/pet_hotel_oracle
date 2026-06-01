(function ($) {
    const root = document.getElementById("ceoFinancePage");

    if (!root || !window.DashboardEngine) {
        return;
    }

    function toNumber(value) {
        const number = Number(value);
        return Number.isFinite(number) ? number : 0;
    }

    function money(value) {
        return `${new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 0 }).format(toNumber(value))} d`;
    }

    function percent(value) {
        return `${new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 2 }).format(Math.abs(toNumber(value)))}%`;
    }

    function signedPercent(value) {
        if (value === null || typeof value === "undefined") {
            return "--";
        }

        return `${new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 2 }).format(toNumber(value))}%`;
    }

    /**
     * Escape text before inserting API values into table markup.
     *
     * Input:
     * - Any value returned by the finance API.
     *
     * Output:
     * - HTML-safe string.
     */
    function escapeHtml(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function setKpi(index, value, changePercent, options = {}) {
        const card = root.querySelectorAll(".finance-kpi-grid--three:first-of-type .kpi-card-wrapper")[index];

        if (!card) {
            return;
        }

        const node = card.querySelector(".kpi-card__value");

        if (node) {
            node.textContent = value;
        }

        const trendNode = card.querySelector(".kpi-card__trend");
        const arrowNode = card.querySelector(".kpi-card__arrow");
        const trendValueNode = card.querySelector(".kpi-card__trend-value");

        if (!trendNode || !arrowNode || !trendValueNode || typeof changePercent === "undefined") {
            return;
        }

        if (changePercent === null) {
            trendNode.className = "kpi-card__trend kpi-card__trend--neutral";
            arrowNode.textContent = "=";
            trendValueNode.textContent = "--";
            return;
        }

        const numericChange = toNumber(changePercent);
        const trend = options.trend || (numericChange > 0 ? "up" : numericChange < 0 ? "down" : "neutral");
        const isIncrease = trend === "up";
        const isDecrease = trend === "down";
        const positiveWhenIncrease = options.positiveWhenIncrease !== false;
        const isPositive = positiveWhenIncrease ? !isDecrease : !isIncrease;

        trendNode.className = `kpi-card__trend ${
            trend === "neutral"
                ? "kpi-card__trend--neutral"
                : isPositive
                  ? "kpi-card__trend--positive"
                  : "kpi-card__trend--negative"
        }`;
        arrowNode.textContent = isIncrease ? "\u25B2" : isDecrease ? "\u25BC" : "=";
        trendValueNode.textContent = percent(numericChange);
    }

    function renderChart(id, config) {
        const canvas = document.getElementById(id);

        if (!canvas || !window.Chart) {
            return;
        }

        window.Chart.getChart(canvas)?.destroy();
        new window.Chart(canvas, config);
    }

    function renderEstimatedTotalCost(data) {
        setKpi(1, money(data?.current_value), data?.growth_percent, {
            trend: data?.trend,
            positiveWhenIncrease: false,
        });
    }

    function renderEstimatedProfit(data) {
        setKpi(2, money(data?.current_value), data?.growth_percent, {
            trend: data?.trend,
        });
    }

    function renderEstimatedMargin(data) {
        setKpi(3, signedPercent(data?.current_value), data?.growth_percent, {
            trend: data?.trend,
        });
    }

    function renderTrendChart(chartId, data) {
        if (!data) {
            return;
        }

        const styleMap = {
            revenue: { color: "#0ea5e9" },
            estimated_material_cost: { color: "#f97316", borderDash: [6, 4] },
            estimated_salary_cost: { color: "#8b5cf6", borderDash: [6, 4] },
            estimated_total_cost: { color: "#ef4444" },
            estimated_profit: { color: "#16a34a" },
        };
        const fallbackDatasets = [
            { key: "revenue", label: "Doanh thu", data: data.revenue || [] },
            { key: "estimated_total_cost", label: "Tổng chi phí ước tính", data: data.estimated_total_cost || data.inventory_cost || [] },
            { key: "estimated_profit", label: "Lợi nhuận ước tính", data: data.estimated_profit || [] },
        ];
        const sourceDatasets = Array.isArray(data.datasets) && data.datasets.length
            ? data.datasets
            : fallbackDatasets;

        renderChart(chartId, {
            type: "line",
            data: {
                labels: data.labels || [],
                datasets: sourceDatasets.map((dataset) => {
                    const style = styleMap[dataset.key] || { color: "#64748b" };

                    return {
                        label: dataset.label || dataset.key,
                        data: dataset.data || [],
                        borderColor: style.color,
                        backgroundColor: style.color,
                        borderDash: style.borderDash || [],
                        tension: 0.25,
                    };
                }),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: false } },
            },
        });
    }

    function renderFinanceTrend(data) {
        renderTrendChart("financeRevenueCostChart", data);
    }

    function renderFinanceMonthlyTrend(data) {
        renderTrendChart("financeMonthlyTrendChart", data);
    }

    /**
     * Render estimated salary and service material cost structure.
     *
     * Input:
     * - API rows with cost_group, cost_amount, and cost_percent.
     *
     * Output:
     * - Updates the #financeCostStructureChart doughnut chart.
     */
    function renderCostStructure(data) {
        const rows = Array.isArray(data) ? data : [];

        renderChart("financeCostStructureChart", {
            type: "doughnut",
            data: {
                labels: rows.map(
                    (row) => `${row.cost_group} (${percent(row.cost_percent)})`,
                ),
                datasets: [
                    {
                        label: "Chi phí ước tính",
                        data: rows.map((row) => toNumber(row.cost_amount)),
                        backgroundColor: ["#8b5cf6", "#f97316"],
                        borderColor: "#ffffff",
                        borderWidth: 3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "62%",
                plugins: {
                    legend: { position: "bottom" },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                return `${context.label}: ${money(context.parsed)}`;
                            },
                        },
                    },
                },
            },
        });
    }

    /**
     * Render ranked estimated profit rows for active branches.
     *
     * Input:
     * - API rows with branch revenue, estimated costs, profit, and margin.
     *
     * Output:
     * - Updates the #financeBranchEstimatedProfitTableBody table body.
     */
    function renderBranchEstimatedProfit(data) {
        const tableBody = document.getElementById(
            "financeBranchEstimatedProfitTableBody",
        );
        const rows = Array.isArray(data) ? data : [];

        if (!tableBody) {
            return;
        }

        tableBody.innerHTML = rows.length
            ? rows
                  .map((branch) => {
                      const margin = branch.estimated_branch_margin_percent;
                      const hasMargin =
                          margin !== null && typeof margin !== "undefined";
                      const numericMargin = toNumber(margin);
                      const marginClass = !hasMargin
                          ? ""
                          : numericMargin < 0
                            ? "finance-margin--bad"
                            : numericMargin <= 30
                              ? "finance-margin--ok"
                              : "finance-margin--good";
                      const profitClass =
                          toNumber(branch.estimated_branch_profit) < 0
                              ? " finance-money--negative"
                              : "";

                      return `
                <tr>
                    <td>${escapeHtml(branch.rank_no)}</td>
                    <td>
                        <div class="finance-service-name">${escapeHtml(branch.branch_name)}</div>
                        <div class="finance-service-meta">${escapeHtml(branch.branch_code)}</div>
                    </td>
                    <td><span class="finance-money">${escapeHtml(money(branch.branch_revenue))}</span></td>
                    <td><span class="finance-money finance-money--muted">${escapeHtml(money(branch.branch_salary_cost))}</span></td>
                    <td><span class="finance-money finance-money--muted">${escapeHtml(money(branch.branch_material_cost))}</span></td>
                    <td><span class="finance-money">${escapeHtml(money(branch.estimated_branch_cost))}</span></td>
                    <td><span class="finance-money${profitClass}">${escapeHtml(money(branch.estimated_branch_profit))}</span></td>
                    <td class="text-center">
                        <span class="finance-margin ${marginClass}">
                            ${escapeHtml(hasMargin ? signedPercent(margin) : "--")}
                        </span>
                    </td>
                </tr>
            `;
                  })
                  .join("")
            : '<tr><td class="finance-table__placeholder" colspan="8">Chưa có dữ liệu chi nhánh trong kỳ báo cáo.</td></tr>';
    }

    /**
     * Render ranked estimated profit rows for paid services.
     *
     * Input:
     * - API rows with service usage, revenue, estimated costs, profit, and margin.
     *
     * Output:
     * - Updates the #financeServiceEstimatedProfitTableBody table body.
     */
    function renderServiceEstimatedProfit(data) {
        const tableBody = document.getElementById(
            "financeServiceEstimatedProfitTableBody",
        );
        const rows = Array.isArray(data) ? data : [];

        if (!tableBody) {
            return;
        }

        tableBody.innerHTML = rows.length
            ? rows
                  .map((service) => {
                      const margin = service.estimated_service_margin_percent;
                      const hasMargin =
                          margin !== null && typeof margin !== "undefined";
                      const numericMargin = toNumber(margin);
                      const marginClass = !hasMargin
                          ? ""
                          : numericMargin < 0
                            ? "finance-margin--bad"
                            : numericMargin <= 30
                              ? "finance-margin--ok"
                              : "finance-margin--good";
                      const profitClass =
                          toNumber(service.estimated_service_profit) < 0
                              ? " finance-money--negative"
                              : "";

                      return `
                <tr>
                    <td>${escapeHtml(service.profit_rank)}</td>
                    <td>
                        <div class="finance-service-name">${escapeHtml(service.service_name)}</div>
                        <div class="finance-service-meta">DV-${escapeHtml(service.service_id)}</div>
                    </td>
                    <td>${escapeHtml(service.usage_count)}</td>
                    <td><span class="finance-money">${escapeHtml(money(service.total_service_revenue))}</span></td>
                    <td><span class="finance-money finance-money--muted">${escapeHtml(money(service.total_material_cost))}</span></td>
                    <td><span class="finance-money finance-money--muted">${escapeHtml(money(service.total_labor_cost))}</span></td>
                    <td><span class="finance-money">${escapeHtml(money(service.estimated_service_cost))}</span></td>
                    <td><span class="finance-money${profitClass}">${escapeHtml(money(service.estimated_service_profit))}</span></td>
                    <td class="text-center">
                        <span class="finance-margin ${marginClass}">
                            ${escapeHtml(hasMargin ? signedPercent(margin) : "--")}
                        </span>
                    </td>
                </tr>
            `;
                  })
                  .join("")
            : '<tr><td class="finance-table__placeholder" colspan="9">Chưa có dữ liệu dịch vụ đã thanh toán trong kỳ báo cáo.</td></tr>';
    }

    /**
     * Render the five paid services with the lowest estimated margin.
     *
     * Input:
     * - API rows ordered by estimated_service_margin_percent ascending.
     *
     * Output:
     * - Updates the #financeLowestMarginServicesTableBody table body.
     */
    function renderLowestMarginServices(data) {
        const tableBody = document.getElementById(
            "financeLowestMarginServicesTableBody",
        );
        const rows = Array.isArray(data) ? data : [];

        if (!tableBody) {
            return;
        }

        tableBody.innerHTML = rows.length
            ? rows
                  .map((service, index) => {
                      const margin = service.estimated_service_margin_percent;
                      const hasMargin =
                          margin !== null && typeof margin !== "undefined";
                      const numericMargin = toNumber(margin);
                      const marginClass = !hasMargin
                          ? ""
                          : numericMargin < 0
                            ? "finance-margin--bad"
                            : numericMargin <= 30
                              ? "finance-margin--ok"
                              : "finance-margin--good";
                      const profitClass =
                          toNumber(service.estimated_service_profit) < 0
                              ? " finance-money--negative"
                              : "";

                      return `
                <tr>
                    <td>${escapeHtml(index + 1)}</td>
                    <td>
                        <div class="finance-service-name">${escapeHtml(service.service_name)}</div>
                        <div class="finance-service-meta">DV-${escapeHtml(service.service_id)}</div>
                    </td>
                    <td>${escapeHtml(service.usage_count)}</td>
                    <td><span class="finance-money">${escapeHtml(money(service.total_service_revenue))}</span></td>
                    <td><span class="finance-money finance-money--muted">${escapeHtml(money(service.total_material_cost))}</span></td>
                    <td><span class="finance-money finance-money--muted">${escapeHtml(money(service.total_labor_cost))}</span></td>
                    <td><span class="finance-money">${escapeHtml(money(service.estimated_service_cost))}</span></td>
                    <td><span class="finance-money${profitClass}">${escapeHtml(money(service.estimated_service_profit))}</span></td>
                    <td class="text-center">
                        <span class="finance-margin ${marginClass}">
                            ${escapeHtml(hasMargin ? signedPercent(margin) : "--")}
                        </span>
                    </td>
                </tr>
            `;
                  })
                  .join("")
            : '<tr><td class="finance-table__placeholder" colspan="9">Chưa có dịch vụ có doanh thu trong kỳ báo cáo.</td></tr>';
    }

    /**
     * Render alerts for active branches with negative estimated profit.
     *
     * Input:
     * - API alert rows with branch revenue, estimated cost, and negative profit.
     *
     * Output:
     * - Updates the alert count and #financeNegativeBranchProfitAlerts list.
     */
    function renderNegativeBranchProfitAlerts(data) {
        const list = document.getElementById(
            "financeNegativeBranchProfitAlerts",
        );
        const countNode = document.getElementById(
            "financeNegativeBranchProfitAlertCount",
        );
        const rows = Array.isArray(data) ? data : [];

        if (countNode) {
            countNode.textContent = `${rows.length} cảnh báo chưa xử lý`;
        }

        if (!list) {
            return;
        }

        list.innerHTML = rows.length
            ? rows
                  .map(
                      (alert) => `
                <div class="finance-loss-alert">
                    <div class="finance-loss-alert__icon" aria-hidden="true">!</div>
                    <div class="finance-loss-alert__content">
                        <h4>[${escapeHtml(alert.branch_name)}] ${escapeHtml(alert.title)}</h4>
                        <p>${escapeHtml(alert.warning_text)}</p>
                        <div>
                            Doanh thu: ${escapeHtml(money(alert.branch_revenue))}
                            | Chi phí ước tính: ${escapeHtml(money(alert.estimated_branch_cost))}
                            | Cập nhật: ${escapeHtml(alert.created_at)}
                        </div>
                    </div>
                </div>
            `,
                  )
                  .join("")
            : `
                <div class="finance-loss-alert finance-loss-alert--warning">
                    <div class="finance-loss-alert__content">
                        <p>Không có chi nhánh lợi nhuận âm trong kỳ báo cáo.</p>
                    </div>
                </div>
            `;
    }

    /**
     * Render alerts for paid services below the configured estimated margin.
     *
     * Input:
     * - API alert rows with margin threshold, revenue, cost, and profit.
     *
     * Output:
     * - Updates the alert count and #financeLowServiceMarginAlerts list.
     */
    function renderLowServiceMarginAlerts(data) {
        const list = document.getElementById("financeLowServiceMarginAlerts");
        const countNode = document.getElementById(
            "financeLowServiceMarginAlertCount",
        );
        const rows = Array.isArray(data) ? data : [];

        if (countNode) {
            countNode.textContent = `${rows.length} cảnh báo chưa xử lý`;
        }

        if (!list) {
            return;
        }

        list.innerHTML = rows.length
            ? rows
                  .map(
                      (alert) => `
                <div class="finance-loss-alert ${alert.alert_level === "MEDIUM" ? "finance-loss-alert--warning" : ""}">
                    <div class="finance-loss-alert__icon" aria-hidden="true">!</div>
                    <div class="finance-loss-alert__content">
                        <h4>[${escapeHtml(alert.alert_level)}] ${escapeHtml(alert.title)}</h4>
                        <p>${escapeHtml(alert.warning_text)}</p>
                        <div>
                            Doanh thu: ${escapeHtml(money(alert.total_service_revenue))}
                            | Chi phí ước tính: ${escapeHtml(money(alert.estimated_service_cost))}
                            | Lợi nhuận: ${escapeHtml(money(alert.estimated_service_profit))}
                            | Ngưỡng: ${escapeHtml(signedPercent(alert.compare_value))}
                        </div>
                    </div>
                </div>
            `,
                  )
                  .join("")
            : `
                <div class="finance-loss-alert finance-loss-alert--warning">
                    <div class="finance-loss-alert__content">
                        <p>Không có dịch vụ dưới ngưỡng margin cảnh báo trong kỳ báo cáo.</p>
                    </div>
                </div>
            `;
    }

    /**
     * Render an alert when estimated total cost grows above its threshold.
     *
     * Input:
     * - API alert rows with current cost, previous cost, growth, and threshold.
     *
     * Output:
     * - Updates the alert count and #financeCostGrowthAlerts list.
     */
    function renderCostGrowthAlerts(data) {
        const list = document.getElementById("financeCostGrowthAlerts");
        const countNode = document.getElementById("financeCostGrowthAlertCount");
        const rows = Array.isArray(data) ? data : [];

        if (countNode) {
            countNode.textContent = `${rows.length} cảnh báo chưa xử lý`;
        }

        if (!list) {
            return;
        }

        list.innerHTML = rows.length
            ? rows
                  .map(
                      (alert) => `
                <div class="finance-loss-alert ${alert.alert_level === "MEDIUM" ? "finance-loss-alert--warning" : ""}">
                    <div class="finance-loss-alert__icon" aria-hidden="true">!</div>
                    <div class="finance-loss-alert__content">
                        <h4>[${escapeHtml(alert.alert_level)}] ${escapeHtml(alert.title)}</h4>
                        <p>${escapeHtml(alert.warning_text)}</p>
                        <div>
                            Chi phí hiện tại: ${escapeHtml(money(alert.current_estimated_total_cost))}
                            | Kỳ trước: ${escapeHtml(money(alert.previous_estimated_total_cost))}
                            | Ngưỡng: ${escapeHtml(signedPercent(alert.compare_value))}
                        </div>
                    </div>
                </div>
            `,
                  )
                  .join("")
            : `
                <div class="finance-loss-alert finance-loss-alert--warning">
                    <div class="finance-loss-alert__content">
                        <p>Chi phí chưa vượt ngưỡng tăng trưởng cảnh báo trong kỳ báo cáo.</p>
                    </div>
                </div>
            `;
    }

    function renderFinance(data) {
        setKpi(0, money(data.kpi_cards?.total_revenue), data.kpi_cards?.revenue_growth_percent);
        setKpi(1, money(data.kpi_cards?.estimated_total_cost ?? data.kpi_cards?.total_inventory_cost), data.kpi_cards?.cost_growth_percent, {
            trend: data.kpi_cards?.cost_trend,
            positiveWhenIncrease: false,
        });
        setKpi(2, money(data.kpi_cards?.estimated_profit ?? data.kpi_cards?.net_profit), data.kpi_cards?.profit_growth_percent, {
            trend: data.kpi_cards?.profit_trend,
        });
        setKpi(3, signedPercent(data.kpi_cards?.estimated_margin_percent), data.kpi_cards?.margin_growth_percent, {
            trend: data.kpi_cards?.margin_trend,
        });

        renderFinanceTrend(data.finance_trend_chart || data.revenue_cost_chart);

        if (data.revenue_mix_chart) {
            const groomingTotal = (data.revenue_mix_chart.grooming_revenue || []).reduce((sum, value) => sum + toNumber(value), 0);
            const hotelTotal = (data.revenue_mix_chart.hotel_revenue || []).reduce((sum, value) => sum + toNumber(value), 0);

            renderChart("financeRevenueMixChart", {
                type: "doughnut",
                data: {
                    labels: ["Grooming & Spa", "Hotel"],
                    datasets: [{
                        data: [groomingTotal, hotelTotal],
                        backgroundColor: ["#0ea5e9", "#8b5cf6"],
                    }],
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: "62%" },
            });
        }

    }

    $(document).ready(function () {
        const financeApis = [
            {
                url: root.dataset.financeUrl,
                onBefore: function () {
                    setKpi(1, "Dang tai...");
                },
                onSuccess: renderFinance,
                onError: function () {
                    setKpi(1, "Khong tai duoc");
                },
            },
        ];

        if (root.dataset.estimatedCostUrl) {
            financeApis.push({
                url: root.dataset.estimatedCostUrl,
                onBefore: function () {
                    setKpi(1, "Dang tai...");
                },
                onSuccess: renderEstimatedTotalCost,
                onError: function () {
                    setKpi(1, "Khong tai duoc");
                },
            });
        }

        if (root.dataset.estimatedProfitUrl) {
            financeApis.push({
                url: root.dataset.estimatedProfitUrl,
                onBefore: function () {
                    setKpi(2, "Dang tai...");
                },
                onSuccess: renderEstimatedProfit,
                onError: function () {
                    setKpi(2, "Khong tai duoc");
                },
            });
        }

        if (root.dataset.estimatedMarginUrl) {
            financeApis.push({
                url: root.dataset.estimatedMarginUrl,
                onBefore: function () {
                    setKpi(3, "Dang tai...");
                },
                onSuccess: renderEstimatedMargin,
                onError: function () {
                    setKpi(3, "Khong tai duoc");
                },
            });
        }

        if (root.dataset.financeTrendUrl) {
            financeApis.push({
                url: root.dataset.financeTrendUrl,
                onSuccess: renderFinanceTrend,
            });
        }

        if (root.dataset.financeMonthlyTrendUrl) {
            financeApis.push({
                url: root.dataset.financeMonthlyTrendUrl,
                onSuccess: renderFinanceMonthlyTrend,
            });
        }

        if (root.dataset.costStructureUrl) {
            financeApis.push({
                url: root.dataset.costStructureUrl,
                onSuccess: renderCostStructure,
                onError: function () {
                    renderCostStructure([]);
                },
            });
        }

        if (root.dataset.branchEstimatedProfitUrl) {
            financeApis.push({
                url: root.dataset.branchEstimatedProfitUrl,
                onSuccess: renderBranchEstimatedProfit,
                onError: function () {
                    renderBranchEstimatedProfit([]);
                },
            });
        }

        if (root.dataset.serviceEstimatedProfitUrl) {
            financeApis.push({
                url: root.dataset.serviceEstimatedProfitUrl,
                onSuccess: renderServiceEstimatedProfit,
                onError: function () {
                    renderServiceEstimatedProfit([]);
                },
            });
        }

        if (root.dataset.lowestMarginServicesUrl) {
            financeApis.push({
                url: root.dataset.lowestMarginServicesUrl,
                onSuccess: renderLowestMarginServices,
                onError: function () {
                    renderLowestMarginServices([]);
                },
            });
        }

        if (root.dataset.negativeBranchProfitAlertsUrl) {
            financeApis.push({
                url: root.dataset.negativeBranchProfitAlertsUrl,
                onSuccess: renderNegativeBranchProfitAlerts,
                onError: function () {
                    renderNegativeBranchProfitAlerts([]);
                },
            });
        }

        if (root.dataset.lowServiceMarginAlertsUrl) {
            financeApis.push({
                url: root.dataset.lowServiceMarginAlertsUrl,
                onSuccess: renderLowServiceMarginAlerts,
                onError: function () {
                    renderLowServiceMarginAlerts([]);
                },
            });
        }

        if (root.dataset.costGrowthAlertsUrl) {
            financeApis.push({
                url: root.dataset.costGrowthAlertsUrl,
                onSuccess: renderCostGrowthAlerts,
                onError: function () {
                    renderCostGrowthAlerts([]);
                },
            });
        }

        DashboardEngine.run(financeApis);
    });
})(window.jQuery);
