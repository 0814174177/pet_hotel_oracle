(function ($) {
    const root = document.getElementById("managerInventoryPage");

    if (!root || !window.DashboardEngine) {
        return;
    }

    function setCard(index, value) {
        const node = root.querySelectorAll(".inventory-kpi-row .kpi-card__value")[index];

        if (node && value !== undefined && value !== null && value !== "") {
            node.textContent = value;
        }
    }

    function renderKpi(data) {
        if (Array.isArray(data)) {
            data.forEach((item, index) => setCard(index, item.value));
        }
    }

    function renderMaterials(data) {
        const tbody = root.querySelector(".inventory-table tbody");

        if (!tbody || !Array.isArray(data) || !data.length) {
            return;
        }

        tbody.innerHTML = data.map((item) => `
            <tr>
                <td>
                    <div class="inventory-primary-text">${item.name || item.product_name || ""}</div>
                    <div class="inventory-secondary-text">${item.code || item.product_id || ""}</div>
                    <div class="inventory-category-text">Nhom: ${item.group || item.category || "-"}</div>
                </td>
                <td><span class="inventory-status inventory-status--normal">${item.status || "Hoat dong"}</span></td>
                <td>${item.current_stock || item.quantity_in_stock || "-"}</td>
                <td>${item.unit || "-"}</td>
                <td><div class="inventory-meta-date">${item.updated_at || ""}</div></td>
                <td><div class="inventory-action-group"><button type="button" class="inventory-action-btn">Xem</button></div></td>
            </tr>
        `).join("");
    }

    $(document).ready(function () {
        DashboardEngine.run([
            { url: root.dataset.kpiUrl, onSuccess: renderKpi },
            { url: root.dataset.materialsUrl, onSuccess: renderMaterials },
        ]);
    });
})(window.jQuery);
