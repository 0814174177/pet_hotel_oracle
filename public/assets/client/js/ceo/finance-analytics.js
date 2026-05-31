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

        const tableBody = root.querySelector(".finance-table tbody");
        const rows = Array.isArray(data.gross_margin_analysis) ? data.gross_margin_analysis : [];

        if (tableBody && rows.length) {
            tableBody.innerHTML = rows.map((service) => `
                <tr>
                    <td>
                        <div class="finance-service-name">${service.service_name || ""}</div>
                        <div class="finance-service-meta">Tu API finance</div>
                    </td>
                    <td><span class="finance-money">${money(service.selling_price)}</span></td>
                    <td><span class="finance-money finance-money--muted">${money(service.cost_price)}</span></td>
                    <td class="text-center"><span class="finance-margin">${toNumber(service.margin_percent)}%</span></td>
                </tr>
            `).join("");
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

        DashboardEngine.run(financeApis);
    });
})(window.jQuery);
