(function ($) {
    const root = document.getElementById("managerBranchServicePage");

    if (!root || !window.DashboardEngine || !window.DashboardKpiAdapter) {
        return;
    }

    const Kpi = window.DashboardKpiAdapter;

    /**
     * Mo ta chuc nang:
     * Render danh sach KPI tong hop cua trang quan ly dich vu chi nhanh.
     *
     * Input:
     * - data: Mang KPI tra ve tu API management/kpi.
     *
     * Output:
     * - Cap nhat cac KPI card co data-kpi tuong ung.
     *
     * Ghi chu:
     * - Khong goi fetch va khong bind nut loc trong ham nay.
     */
    function renderKpi(data) {
        if (!Array.isArray(data)) {
            return;
        }

        data.forEach((item) => {
            if (item.key) {
                Kpi.renderKpiCard(item.key, item, {
                    root,
                    periodLabel: "",
                    getValue: (payload) => payload.value,
                    getTrend: (payload) => payload.trend,
                    getComparison: (payload) => payload.comparison,
                });
            }
        });
    }

    /**
     * Mo ta chuc nang:
     * Render KPI tien do doanh thu dich vu len card revenue-progress co san.
     *
     * Input:
     * - data.service_revenue.current
     * - data.service_revenue.target
     * - data.service_revenue.progress_percent
     * - data.service_revenue.growth_vs_previous_percent
     * - data.service_revenue.warning
     *
     * Output:
     * - Cap nhat gia tri card, trend so voi ky truoc va dong chi tiet canh bao.
     *
     * Ghi chu:
     * - Ham chi render du lieu do DashboardEngine truyen vao.
     */
    function renderServiceRevenueProgress(data) {
        const serviceRevenue = data?.service_revenue || {};
        const currentText = Kpi.formatCurrency(serviceRevenue.current);
        const targetText = Kpi.formatCurrency(serviceRevenue.target);
        const progressText = Kpi.formatPercent(serviceRevenue.progress_percent);
        const warningText = serviceRevenue.warning || "Chưa có dữ liệu cảnh báo";

        Kpi.renderKpiCard("revenue-progress", {
            value: progressText,
            detail: `Hiện tại: ${currentText} / Chỉ tiêu: ${targetText} - ${warningText}`,
            comparison: {
                change_percent: serviceRevenue.growth_vs_previous_percent,
                trend: serviceRevenue.trend,
            },
        }, {
            root,
            periodLabel: "so với kỳ trước",
            getValue: (payload) => payload.value,
            getDetail: (payload) => payload.detail,
            getComparison: (payload) => payload.comparison,
        });

        renderServiceRevenueProgressAlert(serviceRevenue, {
            currentText,
            targetText,
            progressText,
            warningText,
        });
    }

    /**
     * Mo ta chuc nang:
     * Render hop canh bao tien do doanh thu dich vu cua chi nhanh.
     *
     * Input:
     * - serviceRevenue: Du lieu KPI service_revenue tu Repository.
     * - display: Chuoi da format cho doanh thu, chi tieu va tien do.
     *
     * Output:
     * - Cap nhat title, message va mau canh bao cua alert box co san.
     *
     * Ghi chu:
     * - Dung textContent de tranh render HTML khong an toan.
     */
    function renderServiceRevenueProgressAlert(serviceRevenue, display) {
        const alertRoot = root.querySelector("[data-service-revenue-progress-alert]");

        if (!alertRoot) {
            return;
        }

        const box = alertRoot.querySelector(".alert-box");
        const title = alertRoot.querySelector("[data-alert-title]");
        const message = alertRoot.querySelector("[data-alert-message]");
        const alertType = serviceRevenue.alert_type === "danger" ? "danger" : "warning";

        if (box) {
            box.className = `alert-box alert-box--${alertType}`;
        }

        if (title) {
            title.textContent = display.warningText;
        }

        if (message) {
            message.textContent = `Tiến độ ${display.progressText}; doanh thu ${display.currentText} / chỉ tiêu ${display.targetText}.`;
        }
    }

    /**
     * Mo ta chuc nang:
     * Render KPI danh sach dich vu co doanh thu giam manh so voi ky truoc.
     *
     * Input:
     * - data.revenue_drop_alerts: Danh sach dich vu giam doanh thu tu 20% tro len.
     * - data.summary: So luong, nguong canh bao va thong bao tong hop.
     * - data.warning_level: Muc canh bao tong hop.
     *
     * Output:
     * - Cap nhat card revenue-drop-alerts voi so luong va danh sach dich vu can chu y.
     *
     * Ghi chu:
     * - Ham chi render data do DashboardEngine truyen vao, khong goi fetch rieng.
     */
    function renderServiceRevenueDropAlerts(data) {
        const alerts = Array.isArray(data?.revenue_drop_alerts)
            ? data.revenue_drop_alerts
            : [];
        const summary = data?.summary || {};
        const alertCount = Number(summary.alert_count) || alerts.length;
        const detail = alerts.length === 0
            ? "Không có dịch vụ giảm mạnh trong kỳ này"
            : alerts
                .map((service) => `${service.service_name || `DV-${service.service_id}`}: -${Kpi.formatPercent(service.revenue_drop_percent)}`)
                .join("; ");

        Kpi.renderKpiCard("revenue-drop-alerts", {
            value: `${alertCount} dịch vụ`,
            trend: alerts.length === 0
                ? "Không có cảnh báo"
                : "Cảnh báo giảm từ 20% trở lên",
            detail,
        }, {
            root,
            periodLabel: "",
            getValue: (payload) => payload.value,
            getTrend: (payload) => payload.trend,
            getDetail: (payload) => payload.detail,
        });
    }

    /**
     * Mo ta chuc nang:
     * Render KPI ty le upsell cua booking phong tai chi nhanh.
     *
     * Input:
     * - data.upsell_rate.total_room_bookings
     * - data.upsell_rate.upsell_bookings
     * - data.upsell_rate.upsell_rate_percent
     * - data.upsell_rate.warning
     *
     * Output:
     * - Cap nhat card upsell-rate voi ty le, danh gia va so booking chi tiet.
     *
     * Ghi chu:
     * - Render an toan khi API khong co booking phong hoac tra ve null.
     */
    function renderServiceUpsellRate(data) {
        const upsell = data?.upsell_rate || {};
        const totalRoomBookings = Number(upsell.total_room_bookings) || 0;
        const upsellBookings = Number(upsell.upsell_bookings) || 0;
        const warning = upsell.warning || "Chưa có dữ liệu booking phòng";

        Kpi.renderKpiCard("upsell-rate", {
            value: Kpi.formatPercent(upsell.upsell_rate_percent),
            trend: warning,
            detail: `${upsellBookings}/${totalRoomBookings} booking phòng có mua thêm dịch vụ`,
        }, {
            root,
            periodLabel: "",
            getValue: (payload) => payload.value,
            getTrend: (payload) => payload.trend,
            getDetail: (payload) => payload.detail,
        });
    }

    /**
     * Mo ta chuc nang:
     * Escape text truoc khi chen vao HTML cua grid dich vu.
     *
     * Input:
     * - value: Gia tri can hien thi.
     *
     * Output:
     * - Chuoi an toan hon khi render template HTML.
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
     * Render danh sach dich vu cau hinh tai chi nhanh len grid co san.
     *
     * Input:
     * - data: Mang dich vu tra ve tu API branches/{branchId}/services.
     *
     * Output:
     * - Cap nhat body cua grid dich vu chi nhanh.
     *
     * Ghi chu:
     * - Khong tu tao query string va khong goi fetch rieng.
     */
    function renderServices(data) {
        const body = root.querySelector(".branch-service-grid-body");

        if (!body) {
            return;
        }

        const services = Array.isArray(data) ? data : [];

        if (services.length === 0) {
            body.innerHTML = `
                <div class="branch-service-empty">
                    <span>Không có dữ liệu dịch vụ để hiển thị.</span>
                </div>
            `;
            return;
        }

        body.innerHTML = services.map((service) => {
            const isActive = Number(service.is_active) === 1;
            const isVisible = service.website_visible === true || Number(service.website_visible) === 1;
            const isLocked = service.is_emergency_locked === true || Number(service.is_emergency_locked) === 1;
            const duration = Number(service.duration_minutes) > 0
                ? `${Number(service.duration_minutes)} phút`
                : "Chưa cấu hình thời lượng";
            const hasGrowth = service.growth_percent !== null
                && typeof service.growth_percent !== "undefined"
                && Number.isFinite(Number(service.growth_percent));
            const growthPercent = hasGrowth ? Number(service.growth_percent) : null;
            const growthText = hasGrowth
                ? `${growthPercent > 0 ? "+" : ""}${Kpi.formatPercent(growthPercent)}`
                : "Chưa có dữ liệu kỳ trước";
            const warningLevel = growthPercent !== null && growthPercent <= -20
                ? "danger"
                : growthPercent !== null && growthPercent >= 20
                  ? "success"
                  : "neutral";

            return `
                <div class="${isActive ? "branch-service-grid-row" : "branch-service-grid-row branch-service-grid-row--disabled"}">
                    <div class="branch-service-name-cell">
                        <span class="branch-service-name">${escapeHtml(service.service_name)}</span>
                        <span class="branch-service-code">${escapeHtml(service.service_code || service.service_id)}</span>
                        <span class="branch-service-group">
                            Nhóm: ${escapeHtml(service.service_category_name || "Chưa phân loại")}
                            | ${escapeHtml(service.species || "ALL")}
                            | ${escapeHtml(duration)}
                        </span>
                        <span class="branch-service-metric">
                            Doanh thu kỳ này: ${escapeHtml(Kpi.formatCurrency(service.current_revenue))}
                            | Kỳ trước: ${escapeHtml(Kpi.formatCurrency(service.previous_revenue))}
                            | Tăng trưởng: ${escapeHtml(growthText)}
                        </span>
                        <span class="branch-service-revenue-warning branch-service-revenue-warning--${warningLevel}">
                            ${escapeHtml(service.revenue_warning_text || "ỔN ĐỊNH")}
                        </span>
                        ${isActive ? "" : `<span class="ceo-lock-badge">${escapeHtml(service.status_text || "Ngừng cung cấp")}</span>`}
                    </div>
                    <div class="text-center">
                        <label class="branch-service-switch" title="Chuc nang cap nhat chua ho tro">
                            <input type="checkbox" ${isVisible ? "checked" : ""} disabled>
                            <span></span>
                        </label>
                    </div>
                    <div class="text-center">
                        <button
                            type="button"
                            class="${isLocked ? "branch-service-pause-btn branch-service-pause-btn--active" : "branch-service-pause-btn"}"
                            disabled
                            title="Chuc nang cap nhat chua ho tro"
                        >
                            ${isLocked ? "Đang khóa" : "Khóa tạm"}
                        </button>
                    </div>
                    <div class="branch-service-price-group">
                        <span class="branch-service-base-price">Gốc: ${escapeHtml(Kpi.formatCurrency(service.original_price))}</span>
                        <div class="branch-service-local-price">
                            ${escapeHtml(Kpi.formatCurrency(service.override_price_assumption))}
                        </div>
                    </div>
                </div>
            `;
        }).join("");
    }

    $(document).ready(function () {
        DashboardEngine.run([
            { url: root.dataset.kpiUrl, onSuccess: renderKpi },
            {
                url: root.dataset.serviceRevenueProgressUrl,
                onSuccess: renderServiceRevenueProgress,
            },
            {
                url: root.dataset.serviceRevenueDropAlertsUrl,
                onSuccess: renderServiceRevenueDropAlerts,
            },
            {
                url: root.dataset.serviceUpsellRateUrl,
                onSuccess: renderServiceUpsellRate,
            },
            { url: root.dataset.servicesUrl, onSuccess: renderServices },
        ]);
    });
})(window.jQuery);
