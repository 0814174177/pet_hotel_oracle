(function ($) {
    const root = document.getElementById("managerBranchServicePage");

    if (!root || !window.DashboardEngine) {
        return;
    }

    function renderKpi(data) {
        if (!Array.isArray(data)) {
            return;
        }

        const nodes = root.querySelectorAll(".branch-service-stats .kpi-card__value");
        data.forEach((item, index) => {
            if (nodes[index] && item.value !== undefined) {
                nodes[index].textContent = item.value;
            }
        });
    }

    function renderServices(data) {
        const body = root.querySelector(".branch-service-grid-body");

        if (!body || !Array.isArray(data) || !data.length) {
            return;
        }

        body.innerHTML = data.map((service) => `
            <div class="branch-service-grid-row">
                <div class="branch-service-name-cell">
                    <span class="branch-service-name">${service.service_name || service.name || ""}</span>
                    <span class="branch-service-code">${service.service_code || service.service_id || ""}</span>
                    <span class="branch-service-group">Nhom: ${service.group || "-"}</span>
                </div>
                <div class="text-center">
                    <label class="branch-service-switch">
                        <input type="checkbox" ${service.is_visible ? "checked" : ""}>
                        <span></span>
                    </label>
                </div>
                <div class="text-center">
                    <button type="button" class="branch-service-pause-btn">Khoa tam</button>
                </div>
                <div class="branch-service-price-group">
                    <span class="branch-service-base-price">Goc: ${service.base_price || "-"}</span>
                    <div class="branch-service-local-price">${service.local_price || service.base_price || "-"} <span class="branch-service-edit-icon">*</span></div>
                </div>
            </div>
        `).join("");
    }

    $(document).ready(function () {
        DashboardEngine.run([
            { url: root.dataset.kpiUrl, onSuccess: renderKpi },
            { url: root.dataset.servicesUrl, onSuccess: renderServices },
        ]);
    });
})(window.jQuery);
