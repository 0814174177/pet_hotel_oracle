(function () {
    "use strict";

    const root = document.getElementById("ceoPromotionPage");

    if (!root) {
        return;
    }

    let coupons = readInitialCoupons();
    let pendingToggleId = null;

    const selectors = {
        statTotal: "[data-stat-total]",
        statActive: "[data-stat-active]",
        statUsed: "[data-stat-used]",
        statExpiring: "[data-stat-expiring]",
        searchInput: "[data-search-input]",
        filterType: "[data-filter-type]",
        filterStatus: "[data-filter-status]",
        addPromotion: "[data-add-promotion]",
        tableBody: "[data-coupon-table-body]",
        tableCount: "[data-table-count]",
        emptyState: "[data-empty-state]",
        modalOverlay: "[data-modal-overlay]",
        modalTitle: "[data-modal-title]",
        modalSub: "[data-modal-sub]",
        fieldCode: "[data-field-code]",
        fieldType: "[data-field-type]",
        fieldNotes: "[data-field-notes]",
        fieldValue: "[data-field-value]",
        fieldMaxDiscount: "[data-field-max-discount]",
        fieldMinOrder: "[data-field-min-order]",
        fieldMaxUses: "[data-field-max-uses]",
        fieldFrom: "[data-field-from]",
        fieldExpired: "[data-field-expired]",
        fieldActive: "[data-field-active]",
        fieldUnit: "[data-field-unit]",
        valueNote: "[data-value-note]",
        maxDiscountGroup: "[data-max-discount-group]",
        typeOption: "[data-type-option]",
        previewCode: "[data-preview-code]",
        submitPromotion: "[data-submit-promotion]",
        closeModal: "[data-close-modal]",
        confirmOverlay: "[data-confirm-overlay]",
        cancelToggle: "[data-cancel-toggle]",
        confirmToggle: "[data-confirm-toggle]",
    };

    function $(selector) {
        return root.querySelector(selector);
    }

    function all(selector) {
        return Array.from(root.querySelectorAll(selector));
    }

    function nullableNumber(value) {
        if (value === null || value === undefined || value === "") {
            return null;
        }

        const parsed = Number(value);

        return Number.isNaN(parsed) ? null : parsed;
    }

    function normalizeCoupon(coupon) {
        return {
            id: Number(coupon.id) || 0,
            code: String(coupon.code || "").trim(),
            empId: coupon.empId === null || coupon.empId === undefined ? null : Number(coupon.empId),
            type: String(coupon.type || "FIXED").toUpperCase(),
            value: nullableNumber(coupon.value) || 0,
            maxDiscount: nullableNumber(coupon.maxDiscount),
            minOrder: nullableNumber(coupon.minOrder),
            maxUses: nullableNumber(coupon.maxUses),
            usedCount: nullableNumber(coupon.usedCount) || 0,
            from: coupon.from || null,
            expired: coupon.expired || null,
            active: Boolean(coupon.active),
            notes: coupon.notes || "",
        };
    }

    function readInitialCoupons() {
        const dataNode = root.querySelector("#ceoPromotionCouponsData");

        if (!dataNode) {
            return [];
        }

        try {
            const parsed = JSON.parse(dataNode.textContent || "[]");

            return Array.isArray(parsed)
                ? parsed.map(normalizeCoupon).filter((coupon) => coupon.code)
                : [];
        } catch (error) {
            console.warn("Không đọc được dữ liệu khuyến mãi từ server.", error);
            return [];
        }
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDate(date) {
        if (!date) {
            return "—";
        }

        const [year, month, day] = String(date).split("-");

        return day && month && year ? `${day}/${month}/${year}` : date;
    }

    function isExpired(date) {
        return Boolean(date) && new Date(date) < new Date();
    }

    function isExpiringSoon(date) {
        if (!date) {
            return false;
        }

        const diff = (new Date(date) - new Date()) / 86400000;

        return diff >= 0 && diff <= 30;
    }

    function number(value) {
        return new Intl.NumberFormat("vi-VN").format(Number(value) || 0);
    }

    function formatValue(coupon) {
        if (coupon.type === "PERCENT") {
            return `${coupon.value}%`;
        }

        return `${number(coupon.value)}đ`;
    }

    function formatMaxDiscount(coupon) {
        if (coupon.type !== "PERCENT" || !coupon.maxDiscount) {
            return "—";
        }

        return `${number(coupon.maxDiscount)}đ`;
    }

    function usagePercent(coupon) {
        if (!coupon.maxUses) {
            return 0;
        }

        return Math.round((coupon.usedCount / coupon.maxUses) * 100);
    }

    function currentType() {
        return $(selectors.typeOption + ".active")?.dataset.typeOption || "PERCENT";
    }

    function setText(selector, text) {
        const node = $(selector);

        if (node) {
            node.textContent = text;
        }
    }

    function updateStats() {
        setText(selectors.statTotal, coupons.length);
        setText(selectors.statActive, coupons.filter((coupon) => coupon.active && !isExpired(coupon.expired)).length);
        setText(selectors.statUsed, coupons.reduce((sum, coupon) => sum + coupon.usedCount, 0));
        setText(
            selectors.statExpiring,
            coupons.filter((coupon) => coupon.active && isExpiringSoon(coupon.expired)).length,
        );
    }

    function renderRow(coupon) {
        const percent = usagePercent(coupon);
        const fillClass = percent >= 90
            ? "ceo-promotion-progress-fill--danger"
            : percent >= 70
              ? "ceo-promotion-progress-fill--warn"
              : "";
        const inactive = !coupon.active || isExpired(coupon.expired);
        const expiringSoon = isExpiringSoon(coupon.expired);
        const expiredLabel = isExpired(coupon.expired) ? " ⚠️" : expiringSoon ? " 🔔" : "";
        const expiredBadge = inactive
            ? '<div><span class="ceo-promotion-badge-expired">⏰ Đã hết hạn</span></div>'
            : "";
        const progress = coupon.maxUses
            ? `<div class="ceo-promotion-progress-bar"><div class="ceo-promotion-progress-fill ${fillClass}" style="width:${percent}%"></div></div>`
            : "";
        const toggle = inactive
            ? `<label class="ceo-promotion-toggle ceo-promotion-toggle--disabled" title="Đã kết thúc - không thể bật lại">
                    <input type="checkbox" disabled>
                    <span class="ceo-promotion-slider"></span>
               </label>`
            : `<label class="ceo-promotion-toggle" title="Đang hoạt động - nhấn để kết thúc tạm trên UI">
                    <input type="checkbox" checked data-toggle-coupon="${coupon.id}">
                    <span class="ceo-promotion-slider"></span>
               </label>`;

        return `
            <tr class="${inactive ? "is-inactive" : ""}">
                <td>
                    <span class="ceo-promotion-coupon-code">${escapeHtml(coupon.code)}</span>
                    ${expiredBadge}
                </td>
                <td>
                    <span class="ceo-promotion-badge ${coupon.type === "PERCENT" ? "ceo-promotion-badge--percent" : "ceo-promotion-badge--fixed"}">
                        ${coupon.type === "PERCENT" ? "% Phần trăm" : "💵 Cố định"}
                    </span>
                </td>
                <td><strong>${formatValue(coupon)}</strong></td>
                <td class="ceo-promotion-date-text">${formatMaxDiscount(coupon)}</td>
                <td class="ceo-promotion-date-text">${coupon.minOrder ? `${number(coupon.minOrder)}đ` : "—"}</td>
                <td>
                    <div class="ceo-promotion-uses-text">${number(coupon.usedCount)} / ${coupon.maxUses || "∞"}</div>
                    ${progress}
                </td>
                <td class="ceo-promotion-date-text">${formatDate(coupon.from)}</td>
                <td class="ceo-promotion-date-text ${isExpired(coupon.expired) ? "ceo-promotion-date-text--expired" : ""}">
                    ${formatDate(coupon.expired)}${expiredLabel}
                </td>
                <td class="ceo-promotion-col-active">${toggle}</td>
            </tr>
        `;
    }

    function renderTable(list) {
        const tableBody = $(selectors.tableBody);
        const emptyState = $(selectors.emptyState);
        const tableCount = $(selectors.tableCount);

        if (!tableBody || !emptyState || !tableCount) {
            return;
        }

        tableCount.textContent = `(${list.length} mã)`;

        if (!list.length) {
            tableBody.innerHTML = "";
            emptyState.classList.remove("ceo-promotion-hidden");
            return;
        }

        emptyState.classList.add("ceo-promotion-hidden");

        const active = list.filter((coupon) => coupon.active && !isExpired(coupon.expired));
        const inactive = list.filter((coupon) => !coupon.active || isExpired(coupon.expired));
        let rows = active.map(renderRow);

        if (inactive.length > 0) {
            rows.push('<tr><td colspan="9"><div class="ceo-promotion-divider"></div></td></tr>');
            rows = rows.concat(inactive.map(renderRow));
        }

        tableBody.innerHTML = rows.join("");
    }

    function applyFilters() {
        const query = ($(selectors.searchInput)?.value || "").toLowerCase();
        const type = $(selectors.filterType)?.value || "";
        const status = $(selectors.filterStatus)?.value || "";
        const filtered = coupons.filter((coupon) => {
            const matchesQuery = !query ||
                coupon.code.toLowerCase().includes(query) ||
                String(coupon.notes || "").toLowerCase().includes(query);
            const matchesType = !type || coupon.type === type;
            const matchesStatus = status === "" ||
                (status === "1" ? coupon.active && !isExpired(coupon.expired) : isExpired(coupon.expired) || !coupon.active);

            return matchesQuery && matchesType && matchesStatus;
        });

        renderTable(filtered);
    }

    function updatePreview() {
        const code = ($(selectors.fieldCode)?.value || "")
            .toUpperCase()
            .replace(/\s/g, "_") || "NHAP_MA_COUPON";
        setText(selectors.previewCode, code);
    }

    function selectType(type) {
        all(selectors.typeOption).forEach((option) => {
            option.classList.toggle("active", option.dataset.typeOption === type);
        });

        if ($(selectors.fieldType)) {
            $(selectors.fieldType).value = type;
        }

        setText(selectors.fieldUnit, type === "PERCENT" ? "%" : "VNĐ");
        setText(
            selectors.valueNote,
            type === "PERCENT" ? "Nhập 1-100 cho phần trăm" : "Nhập số tiền giảm (VNĐ)",
        );

        const maxDiscountGroup = $(selectors.maxDiscountGroup);

        if (maxDiscountGroup) {
            maxDiscountGroup.classList.toggle("ceo-promotion-hidden", type === "FIXED");
        }

        if (type === "FIXED" && $(selectors.fieldMaxDiscount)) {
            $(selectors.fieldMaxDiscount).value = "";
        }
    }

    function setDefaultDates() {
        const today = new Date().toISOString().split("T")[0];
        const nextMonth = new Date();
        nextMonth.setMonth(nextMonth.getMonth() + 1);

        if ($(selectors.fieldFrom)) {
            $(selectors.fieldFrom).value = today;
        }

        if ($(selectors.fieldExpired)) {
            $(selectors.fieldExpired).value = nextMonth.toISOString().split("T")[0];
        }
    }

    function resetForm() {
        const fields = [
            selectors.fieldCode,
            selectors.fieldNotes,
            selectors.fieldValue,
            selectors.fieldMaxDiscount,
            selectors.fieldMinOrder,
            selectors.fieldMaxUses,
        ];

        fields.forEach((selector) => {
            const field = $(selector);

            if (field) {
                field.value = "";
            }
        });

        if ($(selectors.fieldActive)) {
            $(selectors.fieldActive).checked = true;
        }

        selectType("PERCENT");
        setDefaultDates();
        updatePreview();
    }

    function openModal() {
        const overlay = $(selectors.modalOverlay);

        if (overlay) {
            overlay.classList.add("show");
            overlay.setAttribute("aria-hidden", "false");
        }
    }

    function closeModal() {
        const overlay = $(selectors.modalOverlay);

        if (overlay) {
            overlay.classList.remove("show");
            overlay.setAttribute("aria-hidden", "true");
        }
    }

    function openAddModal() {
        resetForm();
        setText(selectors.modalTitle, "Thêm khuyến mãi mới");
        setText(selectors.modalSub, "Tạo mã coupon mới trong hệ thống");
        setText(selectors.submitPromotion, "✓ Tạo khuyến mãi");
        openModal();
    }

    function showConfirm(id) {
        const overlay = $(selectors.confirmOverlay);

        pendingToggleId = id;

        if (overlay) {
            overlay.classList.add("show");
            overlay.setAttribute("aria-hidden", "false");
        }
    }

    function hideConfirm() {
        const overlay = $(selectors.confirmOverlay);

        pendingToggleId = null;

        if (overlay) {
            overlay.classList.remove("show");
            overlay.setAttribute("aria-hidden", "true");
        }
    }

    function confirmToggle() {
        if (pendingToggleId !== null) {
            const coupon = coupons.find((item) => item.id === pendingToggleId);

            if (coupon) {
                coupon.active = false;
                updateStats();
                applyFilters();
            }
        }

        hideConfirm();
    }

    function bindEvents() {
        $(selectors.searchInput)?.addEventListener("input", applyFilters);
        $(selectors.filterType)?.addEventListener("change", applyFilters);
        $(selectors.filterStatus)?.addEventListener("change", applyFilters);
        $(selectors.addPromotion)?.addEventListener("click", openAddModal);
        $(selectors.fieldCode)?.addEventListener("input", updatePreview);
        $(selectors.cancelToggle)?.addEventListener("click", hideConfirm);
        $(selectors.confirmToggle)?.addEventListener("click", confirmToggle);

        all(selectors.closeModal).forEach((button) => {
            button.addEventListener("click", closeModal);
        });

        all(selectors.typeOption).forEach((button) => {
            button.addEventListener("click", () => selectType(button.dataset.typeOption));
        });

        $(selectors.modalOverlay)?.addEventListener("click", (event) => {
            if (event.target === $(selectors.modalOverlay)) {
                closeModal();
            }
        });

        $(selectors.tableBody)?.addEventListener("change", (event) => {
            const toggle = event.target.closest("[data-toggle-coupon]");

            if (!toggle) {
                return;
            }

            toggle.checked = true;
            showConfirm(parseInt(toggle.dataset.toggleCoupon, 10));
        });
    }

    bindEvents();
    selectType(currentType());

    if (root.dataset.promotionHasErrors === "1") {
        updatePreview();
        openModal();
    }

    updateStats();
    applyFilters();
})();
