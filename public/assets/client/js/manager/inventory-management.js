(function ($) {
    const root = document.getElementById("managerInventoryPage");

    if (!root || !window.DashboardEngine || !window.DashboardKpiAdapter) {
        return;
    }

    const Kpi = window.DashboardKpiAdapter;
    let materials = [];

    /**
     * Mo ta chuc nang:
     * Escape text truoc khi chen vao HTML cua bang vat tu.
     */
    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    /**
     * Mo ta chuc nang:
     * Chuan hoa gia tri so null/undefined ve so an toan de render.
     */
    function numberValue(value) {
        const number = Number(value);

        return Number.isFinite(number) ? number : 0;
    }

    /**
     * Mo ta chuc nang:
     * Dinh dang gia tri KPI theo loai du lieu Repository tra ve.
     */
    function formatKpiValue(item) {
        if (item?.value_type === "currency") {
            return Kpi.formatCurrency(item?.value);
        }

        return Kpi.formatNumber(item?.value);
    }

    /**
     * Mo ta chuc nang:
     * Render bon KPI ton kho chi nhanh bang DashboardKpiAdapter.
     */
    function renderKpi(data) {
        if (Array.isArray(data)) {
            data.forEach((item) => {
                if (!item?.key) {
                    return;
                }

                Kpi.renderKpiCard(item.key, item, {
                    root,
                    periodLabel: "",
                    getValue: formatKpiValue,
                    getTrend: (payload) => payload?.trend,
                    getComparison: (payload) => payload?.comparison,
                });
            });
        }
    }

    /**
     * Mo ta chuc nang:
     * Gioi han class trang thai vat tu ve cac gia tri UI ho tro.
     */
    function materialStatus(value) {
        return ["out", "low", "normal"].includes(value) ? value : "normal";
    }

    /**
     * Mo ta chuc nang:
     * Tinh phan tram thanh ton kho dua tren nguong nhap lai hien co trong schema.
     */
    function stockProgress(currentStock, reorderPoint) {
        const current = numberValue(currentStock);
        const threshold = numberValue(reorderPoint);

        if (threshold <= 0) {
            return current > 0 ? 100 : 0;
        }

        return Math.min(Math.max((current / threshold) * 100, 0), 100);
    }

    /**
     * Mo ta chuc nang:
     * Render cac dong vat tu da duoc loc vao bang quan tri co san.
     */
    function renderMaterialRows(rows) {
        const tbody = root.querySelector(".inventory-table tbody");

        if (!tbody) {
            return;
        }

        if (!Array.isArray(rows) || !rows.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="inventory-empty">
                        Không tìm thấy vật tư phù hợp với bộ lọc hiện tại.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = rows.map((item) => {
            const status = materialStatus(item?.status);
            const currentStock = numberValue(item?.current_stock);
            const reorderPoint = numberValue(item?.reorder_point);
            const progress = stockProgress(currentStock, reorderPoint);

            return `
            <tr>
                <td>
                    <div class="inventory-primary-text">${escapeHtml(item?.material_name)}</div>
                    <div class="inventory-secondary-text">${escapeHtml(item?.material_code)}</div>
                    <div class="inventory-category-text">Nhóm: ${escapeHtml(item?.material_group || "-")}</div>
                </td>
                <td>
                    <span class="inventory-status inventory-status--${status}">
                        ${escapeHtml(item?.status_label || "HOẠT ĐỘNG")}
                    </span>
                </td>
                <td>
                    <div class="inventory-progress-wrap">
                        <div class="inventory-progress-track">
                            <div
                                class="inventory-progress-fill inventory-progress-fill--${status}"
                                style="width: ${progress}%;"
                            ></div>
                        </div>
                        <span class="inventory-progress-label">${Kpi.formatNumber(currentStock)}</span>
                    </div>
                    <div class="inventory-threshold-text">
                        Ngưỡng nhập lại: ${Kpi.formatNumber(reorderPoint)}
                    </div>
                </td>
                <td>${escapeHtml(item?.unit || "-")}</td>
                <td><div class="inventory-meta-date">${escapeHtml(item?.last_updated)}</div></td>
                <td><div class="inventory-action-group"><button type="button" class="inventory-action-btn">Xem</button></div></td>
            </tr>
        `;
        }).join("");
    }

    /**
     * Mo ta chuc nang:
     * Tao option nhom vat tu tu du lieu API thay vi hard-code trong Blade.
     */
    function renderMaterialGroupOptions(rows) {
        const groupSelect = root.querySelector(".js-inventory-group-filter");

        if (!groupSelect) {
            return;
        }

        const currentValue = groupSelect.value;
        const groups = [...new Set(
            rows
                .map((item) => String(item?.material_group || "").trim())
                .filter(Boolean),
        )].sort((left, right) => left.localeCompare(right, "vi"));

        groupSelect.innerHTML = [
            '<option value="">-- Tất cả nhóm --</option>',
            ...groups.map((group) => `<option value="${escapeHtml(group)}">${escapeHtml(group)}</option>`),
        ].join("");

        if (groups.includes(currentValue)) {
            groupSelect.value = currentValue;
        }
    }

    /**
     * Mo ta chuc nang:
     * Loc cuc bo danh sach da tai theo ma, ten hoac nhom vat tu.
     *
     * Ghi chu:
     * - Khong goi fetch va khong bind lai .js-apply-filter.
     */
    function applyMaterialFilters() {
        const search = String(
            root.querySelector(".js-inventory-search-filter")?.value || "",
        ).trim().toLowerCase();
        const group = String(
            root.querySelector(".js-inventory-group-filter")?.value || "",
        );

        renderMaterialRows(materials.filter((item) => {
            const searchableText = `${item?.material_code || ""} ${item?.material_name || ""}`
                .toLowerCase();
            const matchesSearch = search === "" || searchableText.includes(search);
            const matchesGroup = group === "" || item?.material_group === group;

            return matchesSearch && matchesGroup;
        }));
    }

    /**
     * Mo ta chuc nang:
     * Render danh sach vat tu API va cap nhat bo loc cuc bo co san tren UI.
     */
    function renderMaterials(data) {
        materials = Array.isArray(data) ? data : [];
        renderMaterialGroupOptions(materials);
        applyMaterialFilters();
    }

    /**
     * Mo ta chuc nang:
     * Gan su kien cho bo loc cuc bo cua bang vat tu.
     *
     * Ghi chu:
     * - DashboardEngine tiep tuc tu quan ly .js-apply-filter va API GET.
     */
    function bindMaterialFilters() {
        root.querySelector(".js-inventory-search-filter")
            ?.addEventListener("input", applyMaterialFilters);
        root.querySelector(".js-inventory-group-filter")
            ?.addEventListener("change", applyMaterialFilters);
    }

    $(document).ready(function () {
        bindMaterialFilters();

        DashboardEngine.run([
            { url: root.dataset.kpiUrl, onSuccess: renderKpi },
            { url: root.dataset.materialsUrl, onSuccess: renderMaterials },
        ]);
    });
})(window.jQuery);
