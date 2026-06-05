(function () {
    "use strict";

    const root = document.getElementById("managerPromotionPage");

    if (!root) {
        return;
    }

    const selectors = {
        statTotal: "[data-stat-total]",
        statActive: "[data-stat-active]",
        statExpired: "[data-stat-expired]",
        statEnded: "[data-stat-ended]",
        searchInput: "[data-search-input]",
        filterType: "[data-filter-type]",
        filterStatus: "[data-filter-status]",
        refreshFilters: "[data-refresh-filters]",
        tableBody: "[data-coupon-table-body]",
        tableCount: "[data-table-count]",
        emptyState: "[data-empty-state]",
    };

    const coupons = readCoupons();

    function $(selector) {
        return root.querySelector(selector);
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

    function readCoupons() {
        const dataNode = root.querySelector("#managerPromotionCouponsData");

        if (!dataNode) {
            return [];
        }

        try {
            const parsed = JSON.parse(dataNode.textContent || "[]");

            return Array.isArray(parsed)
                ? parsed.map(normalizeCoupon).filter((coupon) => coupon.code)
                : [];
        } catch (error) {
            console.warn("Không đọc được dữ liệu khuyến mãi từ máy chủ.", error);
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

    function normalizeSearch(value) {
        return String(value ?? "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/đ/g, "d")
            .replace(/Đ/g, "D")
            .toLowerCase()
            .replace(/\s+/g, " ")
            .trim();
    }

    function parseDateEndOfDay(dateString) {
        if (!dateString) {
            return null;
        }

        const parts = String(dateString).split("-").map(Number);

        if (parts.length === 3 && parts.every((part) => Number.isFinite(part))) {
            const [year, month, day] = parts;

            return new Date(year, month - 1, day, 23, 59, 59, 999);
        }

        const date = new Date(dateString);

        if (Number.isNaN(date.getTime())) {
            return null;
        }

        date.setHours(23, 59, 59, 999);

        return date;
    }

    function isExpired(dateString) {
        const endOfDay = parseDateEndOfDay(dateString);

        return Boolean(endOfDay) && endOfDay < new Date();
    }

    function couponStatus(coupon) {
        if (!coupon.active) {
            return "ended";
        }

        if (isExpired(coupon.expired)) {
            return "expired";
        }

        return "active";
    }

    function couponStatusLabel(coupon) {
        const status = couponStatus(coupon);

        if (status === "ended") {
            return "Đã kết thúc";
        }

        if (status === "expired") {
            return "Hết hạn";
        }

        return "Đang hoạt động";
    }

    function typeLabel(type) {
        return type === "PERCENT" ? "Phần trăm" : "Cố định";
    }

    function number(value) {
        return new Intl.NumberFormat("vi-VN").format(Number(value) || 0);
    }

    function money(value) {
        const amount = nullableNumber(value);

        if (amount === null) {
            return "-";
        }

        return `${number(amount)} đ`;
    }

    function formatValue(coupon) {
        if (coupon.type === "PERCENT") {
            return `${number(coupon.value)}%`;
        }

        return money(coupon.value);
    }

    function formatMaxDiscount(coupon) {
        if (coupon.type !== "PERCENT" || !coupon.maxDiscount) {
            return "-";
        }

        return money(coupon.maxDiscount);
    }

    function formatDate(date) {
        if (!date) {
            return "-";
        }

        const [year, month, day] = String(date).split("-");

        return day && month && year ? `${day}/${month}/${year}` : date;
    }

    function couponSearchText(coupon) {
        return normalizeSearch([
            coupon.code,
            coupon.notes,
            typeLabel(coupon.type),
            couponStatusLabel(coupon),
            formatValue(coupon),
            formatMaxDiscount(coupon),
            coupon.minOrder ? money(coupon.minOrder) : "",
            coupon.maxUses ? `${number(coupon.usedCount)} ${number(coupon.maxUses)}` : number(coupon.usedCount),
            formatDate(coupon.from),
            formatDate(coupon.expired),
        ].join(" "));
    }

    function usagePercent(coupon) {
        if (!coupon.maxUses) {
            return 0;
        }

        return Math.min(Math.round((coupon.usedCount / coupon.maxUses) * 100), 100);
    }

    function setText(selector, text) {
        const node = $(selector);

        if (node) {
            node.textContent = text;
        }
    }

    function updateStats() {
        setText(selectors.statTotal, coupons.length);
        setText(selectors.statActive, coupons.filter((coupon) => couponStatus(coupon) === "active").length);
        setText(selectors.statExpired, coupons.filter((coupon) => couponStatus(coupon) === "expired").length);
        setText(selectors.statEnded, coupons.filter((coupon) => couponStatus(coupon) === "ended").length);
    }

    function renderRow(coupon) {
        const status = couponStatus(coupon);
        const percent = usagePercent(coupon);
        const progressClass = percent >= 100 ? " manager-promotion-progress-fill--danger" : "";
        const usage = coupon.maxUses
            ? `${number(coupon.usedCount)} / ${number(coupon.maxUses)}`
            : `${number(coupon.usedCount)} / Không giới hạn`;

        return `
            <tr>
                <td><span class="manager-promotion-code">${escapeHtml(coupon.code)}</span></td>
                <td>
                    <span class="manager-promotion-type-badge ${coupon.type === "PERCENT" ? "manager-promotion-type-badge--percent" : "manager-promotion-type-badge--fixed"}">
                        ${escapeHtml(typeLabel(coupon.type))}
                    </span>
                </td>
                <td><strong>${formatValue(coupon)}</strong></td>
                <td class="manager-promotion-muted">${formatMaxDiscount(coupon)}</td>
                <td class="manager-promotion-muted">${coupon.minOrder ? money(coupon.minOrder) : "-"}</td>
                <td>
                    <div class="manager-promotion-usage">${usage}</div>
                    <div class="manager-promotion-progress">
                        <div class="manager-promotion-progress-fill${progressClass}" style="width: ${percent}%"></div>
                    </div>
                </td>
                <td class="manager-promotion-muted">${formatDate(coupon.from)}</td>
                <td class="manager-promotion-muted">${formatDate(coupon.expired)}</td>
                <td>
                    <span class="manager-promotion-status-badge manager-promotion-status-badge--${status}">
                        ${escapeHtml(couponStatusLabel(coupon))}
                    </span>
                </td>
                <td><div class="manager-promotion-note">${escapeHtml(coupon.notes || "-")}</div></td>
            </tr>
        `;
    }

    function renderTable(list) {
        const tableBody = $(selectors.tableBody);
        const tableCount = $(selectors.tableCount);
        const emptyState = $(selectors.emptyState);

        if (!tableBody || !tableCount || !emptyState) {
            return;
        }

        tableCount.textContent = `${list.length} kết quả`;

        if (!list.length) {
            tableBody.innerHTML = "";
            emptyState.classList.remove("manager-promotion-hidden");
            return;
        }

        emptyState.classList.add("manager-promotion-hidden");
        tableBody.innerHTML = list.map(renderRow).join("");
    }

    function applyFilters() {
        const query = normalizeSearch($(selectors.searchInput)?.value);
        const type = $(selectors.filterType)?.value || "";
        const status = $(selectors.filterStatus)?.value || "";

        const filtered = coupons.filter((coupon) => {
            const matchesQuery = !query || couponSearchText(coupon).includes(query);
            const matchesType = !type || coupon.type === type;
            const matchesStatus = !status || couponStatus(coupon) === status;

            return matchesQuery && matchesType && matchesStatus;
        });

        renderTable(filtered);
    }

    function resetFilters() {
        if ($(selectors.searchInput)) {
            $(selectors.searchInput).value = "";
        }

        if ($(selectors.filterType)) {
            $(selectors.filterType).value = "";
        }

        if ($(selectors.filterStatus)) {
            $(selectors.filterStatus).value = "";
        }

        applyFilters();
    }

    function bindEvents() {
        $(selectors.searchInput)?.addEventListener("input", applyFilters);
        $(selectors.filterType)?.addEventListener("change", applyFilters);
        $(selectors.filterStatus)?.addEventListener("change", applyFilters);
        $(selectors.refreshFilters)?.addEventListener("click", resetFilters);
    }

    bindEvents();
    updateStats();
    applyFilters();
})();
