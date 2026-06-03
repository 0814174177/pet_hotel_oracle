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

        if (item?.value_type === "percent") {
            return Kpi.formatPercent(item?.value);
        }

        return Kpi.formatNumber(item?.value);
    }

    /**
     * Mo ta chuc nang:
     * Tao danh sach card KPI tu payload tong quan ton kho.
     *
     * Input:
     * - data.cards neu Repository da tra ve san.
     * - Hoac cac key flat: total_materials, out_of_stock_count,
     *   low_stock_count, inventory_value, margin_percent_assumption.
     * - data.material_type_comparison neu can hien thi so sanh card tong vat tu.
     *
     * Output:
     * - Mang card KPI co key, value, value_type, trend va comparison.
     */
    function inventoryKpiCards(data) {
        if (Array.isArray(data)) {
            return data;
        }

        if (Array.isArray(data?.cards)) {
            return data.cards;
        }

        return [
            {
                key: "total-materials",
                value: numberValue(data?.total_materials),
                trend: "Vat tu dang hoat dong",
                comparison: data?.material_type_comparison
                    ? {
                        change_percent: data.material_type_comparison.change_percent,
                        diff_amount: data.material_type_comparison.delta_materials,
                        trend: data.material_type_comparison.trend,
                    }
                    : null,
            },
            {
                key: "out-of-stock-materials",
                value: numberValue(data?.out_of_stock_count),
                trend: "Can nhap bo sung ngay",
                comparison: null,
            },
            {
                key: "low-stock-materials",
                value: numberValue(data?.low_stock_count),
                trend: "Bang hoac duoi nguong nhap lai",
                comparison: null,
            },
            {
                key: "inventory-capital-value",
                value: numberValue(data?.inventory_value),
                value_type: "currency",
                trend: "Gia tri ton theo don gia vat tu",
                comparison: null,
            },
            {
                key: "margin-assumption",
                value: numberValue(data?.margin_percent_assumption),
                value_type: "percent",
                trend: "Gia dinh tam thoi",
                comparison: null,
            },
        ];
    }

    /**
     * Mo ta chuc nang:
     * Chuan hoa muc canh bao ton kho ve cac class UI ho tro.
     *
     * Input:
     * - value: warning_level tu API.
     *
     * Output:
     * - danger, warning hoac safe.
     */
    function inventoryWarningLevel(value) {
        const normalized = String(value || "").toLowerCase();

        return ["danger", "warning", "safe"].includes(normalized) ? normalized : "safe";
    }

    /**
     * Mo ta chuc nang:
     * Render canh bao tong quan ton kho cua chi nhanh Manager.
     *
     * Input:
     * - data.inventory_warning.
     * - data.warning_level.
     * - data.out_of_stock_count va data.low_stock_count.
     *
     * Output:
     * - Cap nhat panel canh bao ton kho co san tren UI.
     *
     * Ghi chu:
     * - Khong goi fetch va khong bind lai .js-apply-filter.
     */
    function renderInventoryWarning(data) {
        const panel = root.querySelector("[data-inventory-warning-panel]");

        if (!panel) {
            return;
        }

        const level = inventoryWarningLevel(data?.warning_level);
        const title = root.querySelector("[data-inventory-warning-title]");
        const message = root.querySelector("[data-inventory-warning-message]");
        const outOfStockCount = numberValue(data?.out_of_stock_count);
        const lowStockCount = numberValue(data?.low_stock_count);
        const marginPercent = Kpi.formatPercent(data?.margin_percent_assumption);

        panel.dataset.warningLevel = level;
        panel.classList.remove(
            "inventory-warning-panel--safe",
            "inventory-warning-panel--warning",
            "inventory-warning-panel--danger",
        );
        panel.classList.add(`inventory-warning-panel--${level}`);

        if (title) {
            title.textContent = data?.inventory_warning || "AN TOAN";
        }

        if (message) {
            message.textContent = `${outOfStockCount} vat tu het hang, ${lowStockCount} vat tu sap het. Margin tam tinh: ${marginPercent}.`;
        }
    }

    /**
     * Mo ta chuc nang:
     * Render KPI tong quan ton kho cua chi nhanh Manager.
     *
     * Input:
     * - data.total_materials.
     * - data.out_of_stock_count.
     * - data.low_stock_count.
     * - data.inventory_value.
     * - data.margin_percent_assumption.
     * - data.material_type_comparison.
     * - data.inventory_warning va data.warning_level.
     *
     * Output:
     * - Cap nhat cac KPI card va canh bao ton kho tren UI co san.
     *
     * Ghi chu:
     * - Khong goi fetch trong ham nay.
     */
    function renderKpi(data) {
        inventoryKpiCards(data || {}).forEach((item) => {
            if (!item?.key) {
                return;
            }

            Kpi.renderKpiCard(item.key, item, {
                root,
                getPeriodLabel: (payload) => payload?.comparison ? "so voi ky truoc" : "",
                getValue: formatKpiValue,
                getTrend: (payload) => payload?.comparison ? null : payload?.trend,
                getComparison: (payload) => payload?.comparison,
            });
        });

        renderInventoryWarning(data || {});
    }

    /**
     * Mo ta chuc nang:
     * Render danh sach vat tu sap het vao panel canh bao co san.
     *
     * Input:
     * - Mang vat tu tu API low-stock.
     *
     * Output:
     * - Cap nhat list canh bao, co trang thai rong khi khong co du lieu.
     *
     * Ghi chu:
     * - Khong goi fetch rieng, chi nhan data tu DashboardEngine.run.
     */
    function renderLowStockMaterials(data) {
        const list = root.querySelector("[data-low-stock-list]");

        if (!list) {
            return;
        }

        const rows = Array.isArray(data) ? data : [];

        if (!rows.length) {
            list.innerHTML = `
                <div class="inventory-alert-empty">
                    Khong co vat tu sap het trong chi nhanh hien tai.
                </div>
            `;
            return;
        }

        list.innerHTML = rows.map((item) => {
            const productName = item?.product_name || "";
            const productCategory = item?.product_category_name || "-";
            const currentStock = numberValue(item?.quantity_in_stock);
            const reorderPoint = numberValue(item?.reorder_point);
            const warningText = item?.warning_text || "SẮP HẾT";

            return `
                <div class="inventory-alert-item inventory-alert-item--warning">
                    <div>
                        <div class="inventory-primary-text">${escapeHtml(productName)}</div>
                        <div class="inventory-category-text">Nhóm: ${escapeHtml(productCategory)}</div>
                        <div class="inventory-threshold-text">
                            Cập nhật: ${escapeHtml(item?.last_updated || "-")}
                        </div>
                    </div>
                    <div class="inventory-alert-meta">
                        <span class="inventory-status inventory-status--warning">
                            ${escapeHtml(warningText)}
                        </span>
                        <span class="inventory-threshold-text">
                            Tồn/ngưỡng: ${Kpi.formatNumber(currentStock)}/${Kpi.formatNumber(reorderPoint)}
                        </span>
                        <span class="inventory-threshold-text">
                            Đơn vị: ${escapeHtml(item?.unit || "-")}
                        </span>
                    </div>
                </div>
            `;
        }).join("");
    }

    /**
     * Mo ta chuc nang:
     * Render von ton kho theo nhom vat tu thanh list chart don gian.
     *
     * Input:
     * - Mang nhom vat tu tu API inventory-value-by-category.
     *
     * Output:
     * - Cap nhat chart/list va summary tong von ton kho.
     *
     * Ghi chu:
     * - Khong goi fetch rieng, chi nhan data tu DashboardEngine.run.
     */
    function renderInventoryValueByCategory(data) {
        const list = root.querySelector("[data-inventory-value-by-category-list]");
        const summary = root.querySelector("[data-inventory-value-by-category-summary]");

        if (!list) {
            return;
        }

        const rows = Array.isArray(data) ? data : [];
        const totalValue = rows.reduce((sum, item) => sum + numberValue(item?.inventory_value), 0);

        if (!rows.length) {
            if (summary) {
                summary.textContent = `0 nhom - ${Kpi.formatCurrency(0)}`;
            }

            list.innerHTML = `
                <div class="inventory-empty">
                    Chua co du lieu von ton kho theo nhom vat tu.
                </div>
            `;
            return;
        }

        if (summary) {
            summary.textContent = `${Kpi.formatNumber(rows.length)} nhom - ${Kpi.formatCurrency(totalValue)}`;
        }

        list.innerHTML = rows.map((item) => {
            const inventoryValue = numberValue(item?.inventory_value);
            const sharePercent = totalValue > 0 ? (inventoryValue / totalValue) * 100 : 0;
            const barWidth = Math.max(Math.min(sharePercent, 100), inventoryValue > 0 ? 2 : 0);

            return `
                <div class="inventory-category-value-row">
                    <div>
                        <div class="inventory-primary-text">
                            ${escapeHtml(item?.product_category_name || "-")}
                        </div>
                        <div class="inventory-secondary-text">
                            ${Kpi.formatNumber(numberValue(item?.material_count))} vat tu - ${Kpi.formatNumber(numberValue(item?.total_quantity))} ton kho
                        </div>
                    </div>
                    <div class="inventory-category-value-chart">
                        <div class="inventory-category-value-track">
                            <div
                                class="inventory-category-value-fill"
                                style="width: ${barWidth.toFixed(2)}%;"
                            ></div>
                        </div>
                        <span>${Kpi.formatPercent(sharePercent)}</span>
                    </div>
                    <div class="inventory-category-value-amount">
                        ${Kpi.formatCurrency(inventoryValue)}
                    </div>
                </div>
            `;
        }).join("");
    }

    /**
     * Mo ta chuc nang:
     * Gioi han class trang thai vat tu ve cac gia tri UI ho tro.
     */
    function materialStatus(value) {
        const normalized = String(value || "").toLowerCase();
        const map = {
            danger: "danger",
            warning: "warning",
            safe: "safe",
            success: "safe",
            out: "danger",
            low: "warning",
            normal: "safe",
        };

        return map[normalized] || "safe";
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
            const status = materialStatus(item?.status_level || item?.status || item?.stock_status);
            const productName = item?.product_name || item?.material_name || "";
            const productCategory = item?.product_category_name || item?.material_group || "-";
            const currentStock = numberValue(item?.quantity_in_stock ?? item?.current_stock);
            const reorderPoint = numberValue(item?.reorder_point);
            const stockVsThreshold = item?.stock_vs_threshold || `${currentStock}/${reorderPoint}`;
            const stockStatus = item?.stock_status || item?.status_label || "HOẠT ĐỘNG";
            const warningText = item?.warning_text || "";
            const progress = stockProgress(currentStock, reorderPoint);

            return `
            <tr>
                <td>
                    <div class="inventory-primary-text">${escapeHtml(productName)}</div>
                    <div class="inventory-secondary-text">${escapeHtml(item?.material_code)}</div>
                    <div class="inventory-category-text">Nhóm: ${escapeHtml(productCategory)}</div>
                </td>
                <td>
                    <span class="inventory-status inventory-status--${status}">
                        ${escapeHtml(stockStatus)}
                    </span>
                    ${warningText ? `<div class="inventory-threshold-text">${escapeHtml(warningText)}</div>` : ""}
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
                        Tồn/ngưỡng: ${escapeHtml(stockVsThreshold)}
                    </div>
                </td>
                <td>
                    <div class="inventory-primary-text">${escapeHtml(item?.unit || "-")}</div>
                    <div class="inventory-secondary-text">${Kpi.formatCurrency(item?.item_price)}</div>
                </td>
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
                .map((item) => String(item?.product_category_name || item?.material_group || "").trim())
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
            const searchableText = `${item?.material_code || ""} ${item?.product_name || item?.material_name || ""}`
                .toLowerCase();
            const matchesSearch = search === "" || searchableText.includes(search);
            const matchesGroup = group === "" || (item?.product_category_name || item?.material_group) === group;

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
            { url: root.dataset.lowStockUrl, onSuccess: renderLowStockMaterials },
            { url: root.dataset.inventoryValueByCategoryUrl, onSuccess: renderInventoryValueByCategory },
            { url: root.dataset.materialsUrl, onSuccess: renderMaterials },
        ]);
    });
})(window.jQuery);
