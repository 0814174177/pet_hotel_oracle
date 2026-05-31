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

    function setKpi(index, value) {
        const node = root.querySelectorAll(".finance-kpi-grid--three:first-of-type .kpi-card__value")[index];

        if (node) {
            node.textContent = value;
        }
    }

    function renderChart(id, config) {
        const canvas = document.getElementById(id);

        if (!canvas || !window.Chart) {
            return;
        }

        window.Chart.getChart(canvas)?.destroy();
        new window.Chart(canvas, config);
    }

    function renderFinance(data) {
        setKpi(0, money(data.kpi_cards?.total_revenue));
        setKpi(1, money(data.kpi_cards?.total_inventory_cost));
        setKpi(2, money(data.kpi_cards?.net_profit));

        if (data.revenue_cost_chart) {
            renderChart("financeRevenueCostChart", {
                type: "line",
                data: {
                    labels: data.revenue_cost_chart.labels || [],
                    datasets: [
                        {
                            label: "Doanh thu",
                            data: data.revenue_cost_chart.revenue || [],
                            borderColor: "#0ea5e9",
                            backgroundColor: "#0ea5e9",
                            tension: 0.25,
                        },
                        {
                            label: "Chi phi nhap kho",
                            data: data.revenue_cost_chart.inventory_cost || [],
                            borderColor: "#ef4444",
                            backgroundColor: "#ef4444",
                            tension: 0.25,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                },
            });
        }

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
        DashboardEngine.run([
            {
                url: root.dataset.financeUrl,
                onSuccess: renderFinance,
            },
        ]);
    });
})(window.jQuery);
