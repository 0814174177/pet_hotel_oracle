(function ($) {
    const root = document.getElementById("ceoServicePage");

    if (!root || !window.DashboardEngine) {
        return;
    }

    function number(value) {
        return new Intl.NumberFormat("vi-VN").format(Number(value) || 0);
    }

    function money(value) {
        return `${number(value)}d`;
    }

    function setCard(index, value, trend) {
        const cards = root.querySelectorAll(".ceo-service-stats .kpi-card-wrapper");
        const card = cards[index];

        if (!card) {
            return;
        }

        const valueNode = card.querySelector(".kpi-card__value");
        const trendNode = card.querySelector(".kpi-card__trend-value");

        if (valueNode) valueNode.textContent = value;
        if (trendNode && trend) trendNode.textContent = trend;
    }

    function renderSummary(data) {
        const top = data.top_revenue_service?.current;
        setCard(0, top?.service_name || "Chua co du lieu", top ? `${top.revenue_share}% doanh thu dich vu` : "");
        setCard(2, `${data.no_activity_service_count?.current ?? 0} dich vu`, "Trong ky loc");
    }

    function renderHighest(data) {
        if (data) {
            setCard(0, data.service_name || "Chua co du lieu", `${money(data.revenue)} doanh thu`);
        }
    }

    function renderLowest(data) {
        if (data) {
            setCard(2, data.service_name || "Chua co du lieu", `${money(data.revenue)} doanh thu`);
        }
    }

    function renderCatalog(data) {
        const tbody = root.querySelector(".ceo-service-table tbody");

        if (!tbody || !Array.isArray(data)) {
            return;
        }

        tbody.innerHTML = data.map((service) => {
            const active = Number(service.is_active) === 1;

            return `
                <tr>
                    <td>
                        <div class="service-name">${service.service_name || ""}</div>
                        <div class="service-code">SV-${service.service_id}</div>
                    </td>
                    <td><span class="service-status ${active ? "service-status--active" : "service-status--paused"}">${active ? "Hoat dong" : "Da an"}</span></td>
                    <td>
                        <div class="service-price-info">
                            <span class="service-sale-price">${money(service.base_price)}</span>
                            <span>/</span>
                            <span class="service-cost-price">-</span>
                            <span>/</span>
                            <span class="service-margin-high">-</span>
                        </div>
                    </td>
                    <td class="text-center">-</td>
                    <td><div class="service-coverage"><span>-</span><div class="service-progress"><div style="width: 0%;"></div></div></div></td>
                    <td>
                        <div class="service-action-group">
                            <button type="button" class="service-action-btn">Xem</button>
                            <button type="button" class="service-action-btn">Sua</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join("");
    }

    $(document).ready(function () {
        DashboardEngine.run([
            { url: root.dataset.summaryUrl, onSuccess: renderSummary },
            { url: root.dataset.highestRevenueUrl, onSuccess: renderHighest },
            { url: root.dataset.lowestRevenueUrl, onSuccess: renderLowest },
            { url: root.dataset.catalogUrl, onSuccess: renderCatalog },
        ]);
    });
})(window.jQuery);
