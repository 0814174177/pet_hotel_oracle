(function ($) {
    const root = document.getElementById("ceoServicePage");

    if (!root || !window.DashboardEngine || !window.DashboardKpiAdapter) {
        return;
    }

    const Kpi = window.DashboardKpiAdapter;

    function number(value) {
        return new Intl.NumberFormat("vi-VN").format(Number(value) || 0);
    }

    function money(value) {
        return `${number(value)}d`;
    }

    /**
     * Mo ta chuc nang:
     * Escape text truoc khi chen vao HTML cua bang dich vu.
     *
     * Input:
     * - value: gia tri can hien thi.
     *
     * Output:
     * - Chuoi an toan hon khi render bang template HTML.
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
     * Cap nhat mot KPI card tren trang quan tri dich vu CEO.
     *
     * Input:
     * - key: gia tri data-kpi-key cua card can cap nhat.
     * - value: gia tri chinh cua card.
     * - trend: mo ta phu hien thi ben duoi gia tri.
     *
     * Output:
     * - DOM cua KPI card duoc cap nhat neu selector ton tai.
     */
    function setCard(key, value, trend) {
        Kpi.renderKpiCard(key, { value, trend }, {
            root,
            periodLabel: "",
            getValue: (payload) => payload.value,
            getTrend: (payload) => payload.trend,
        });
    }

    /**
     * Mo ta chuc nang:
     * Render KPI dich vu toan chuoi len cac KPI card da co san.
     *
     * Input:
     * - data.top_revenue_service
     * - data.top_revenue_amount
     * - data.top_revenue_percent
     * - data.active_service_count
     * - data.no_revenue_service_count
     *
     * Output:
     * - Cap nhat dich vu doanh thu cao nhat, so dich vu dang hoat dong
     *   va so dich vu khong phat sinh doanh thu trong ky loc.
     */
    function renderSummary(data) {
        setCard(
            "top-revenue-service",
            data?.top_revenue_service || "Chua co du lieu",
            `${money(data?.top_revenue_amount)} - ${number(data?.top_revenue_percent)}% doanh thu dich vu`,
        );
        setCard(
            "active-service-count",
            `${number(data?.active_service_count)} dich vu`,
            "Toan chuoi",
        );
        setCard(
            "no-revenue-service-count",
            `${number(data?.no_revenue_service_count)} dich vu`,
            "Khong co doanh thu da thanh toan trong ky loc",
        );
    }

    /**
     * Mo ta chuc nang:
     * Render danh sach dich vu quan tri toan chuoi len table da co san.
     *
     * Input:
     * - data[] gom service_id, service_name, service_category_name, service_status,
     *   base_price, estimated_material_cost, margin_percent, service_count_in_period,
     *   covered_branch_count, total_active_branch va coverage_percent.
     *
     * Output:
     * - Cap nhat tbody bang dich vu theo du lieu API catalog.
     */
    function renderCatalog(data) {
        const tbody = root.querySelector("[data-service-table-body]");

        if (!tbody || !Array.isArray(data)) {
            return;
        }

        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="service-empty">
                        Khong tim thay du lieu dich vu phu hop voi bo loc.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = data.map((service) => {
            const status = String(service.service_status || "");
            const active = status.charAt(0).toUpperCase() === "H";
            const margin = Number(service.margin_percent) || 0;
            const coverage = Math.min(100, Math.max(0, Number(service.coverage_percent) || 0));

            return `
                <tr>
                    <td>
                        <div class="service-name">${escapeHtml(service.service_name)}</div>
                        <div class="service-code">SV-${escapeHtml(service.service_id)}</div>
                        <div class="service-code">${escapeHtml(service.service_category_name || "Chua phan loai")}</div>
                    </td>
                    <td><span class="service-status ${active ? "service-status--active" : "service-status--paused"}">${escapeHtml(status)}</span></td>
                    <td>
                        <div class="service-price-info">
                            <span class="service-sale-price">${money(service.base_price)}</span>
                            <span>/</span>
                            <span class="service-cost-price">${money(service.estimated_material_cost)}</span>
                            <span>/</span>
                            <span class="${margin >= 50 ? "service-margin-high" : "service-margin-low"}">${number(margin)}%</span>
                        </div>
                    </td>
                    <td class="text-center">${number(service.service_count_in_period)} luot</td>
                    <td>
                        <div class="service-coverage">
                            <span>${number(service.covered_branch_count)}/${number(service.total_active_branch)} chi nhanh (${number(service.coverage_percent)}%)</span>
                            <div class="service-progress">
                                <div style="width: ${coverage}%;"></div>
                            </div>
                        </div>
                    </td>
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

    /**
     * Mo ta chuc nang:
     * Render dich vu ganh doanh thu toan chuoi len table da co san.
     *
     * Input:
     * - data gom rank_no, service_id, service_name, service_revenue,
     *   total_service_revenue, revenue_percent va display_text.
     *
     * Output:
     * - Cap nhat tbody bang dich vu co doanh thu cao nhat trong ky loc.
     */
    function renderHighestRevenueService(data) {
        const tbody = root.querySelector("[data-highest-revenue-service-table-body]");

        if (!tbody) {
            return;
        }

        if (!data) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="service-empty">
                        Khong co doanh thu dich vu trong ky loc.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = `
            <tr>
                <td>#${number(data.rank_no)}</td>
                <td>
                    <div class="service-name">${escapeHtml(data.service_name)}</div>
                    <div class="service-code">SV-${escapeHtml(data.service_id)}</div>
                </td>
                <td>${money(data.service_revenue)}</td>
                <td>${money(data.total_service_revenue)}</td>
                <td>${number(data.revenue_percent)}%</td>
                <td>${escapeHtml(data.display_text)}</td>
            </tr>
        `;
    }

    /**
     * Mo ta chuc nang:
     * Render bang doanh thu tung dich vu toan chuoi theo xep hang.
     *
     * Input:
     * - data[] gom rank_no, service_id, service_name, service_revenue,
     *   total_service_revenue, revenue_percent, order_count va service_usage_count.
     *
     * Output:
     * - Cap nhat tbody bang doanh thu tung dich vu theo du lieu API revenue-analysis.
     */
    function renderServiceRevenue(data) {
        const tbody = root.querySelector("[data-service-revenue-table-body]");

        if (!tbody || !Array.isArray(data)) {
            return;
        }

        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="service-empty">
                        Khong co doanh thu dich vu trong ky loc.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = data.map((service) => `
            <tr>
                <td>#${number(service.rank_no)}</td>
                <td>
                    <div class="service-name">${escapeHtml(service.service_name)}</div>
                    <div class="service-code">SV-${escapeHtml(service.service_id)}</div>
                </td>
                <td>${money(service.service_revenue)}</td>
                <td>${number(service.revenue_percent)}%</td>
                <td class="text-center">${number(service.order_count)}</td>
                <td class="text-center">${number(service.service_usage_count)}</td>
            </tr>
        `).join("");
    }

    /**
     * Mo ta chuc nang:
     * Render dich vu sieu loi nhuan toan chuoi len table da co san.
     *
     * Input:
     * - data gom service_id, service_name, total_service_revenue, total_material_cost,
     *   total_labor_cost, estimated_profit, estimated_margin_percent,
     *   missing_employee_count, cost_warning va display_text.
     *
     * Output:
     * - Cap nhat tbody bang dich vu co margin tam tinh cao nhat trong ky loc.
     */
    function renderMostProfitableService(data) {
        const tbody = root.querySelector("[data-most-profitable-service-table-body]");

        if (!tbody) {
            return;
        }

        if (!data) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="service-empty">
                        Khong co dich vu hoan tat va co doanh thu trong ky loc.
                    </td>
                </tr>
            `;
            return;
        }

        const warning = data.cost_warning
            ? `${data.cost_warning}`
            : "Khong co canh bao";

        tbody.innerHTML = `
            <tr>
                <td>
                    <div class="service-name">${escapeHtml(data.service_name)}</div>
                    <div class="service-code">SV-${escapeHtml(data.service_id)}</div>
                    <div class="service-code">${escapeHtml(data.display_text)}</div>
                </td>
                <td>${money(data.total_service_revenue)}</td>
                <td>${money(data.total_material_cost)}</td>
                <td>${money(data.total_labor_cost)}</td>
                <td>${money(data.estimated_profit)}</td>
                <td>${number(data.estimated_margin_percent)}%</td>
                <td>
                    ${escapeHtml(warning)}
                    ${Number(data.missing_employee_count) > 0 ? ` (${number(data.missing_employee_count)} luot)` : ""}
                </td>
            </tr>
        `;
    }

    /**
     * Mo ta chuc nang:
     * Render danh sach dich vu khong phat sinh doanh thu da thanh toan trong ky.
     *
     * Input:
     * - data[] gom service_id, service_name, service_category_name, base_price,
     *   is_active, booking_count_in_period va no_revenue_reason.
     *
     * Output:
     * - Cap nhat tbody bang dich vu can xem xet tam an/ngung hop tac.
     */
    function renderNoActivityServices(data) {
        const tbody = root.querySelector("[data-no-activity-service-table-body]");

        if (!tbody || !Array.isArray(data)) {
            return;
        }

        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="service-empty">
                        Tat ca dich vu deu co doanh thu da thanh toan trong ky loc.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = data.map((service) => {
            const active = Number(service.is_active) === 1;

            return `
                <tr>
                    <td>
                        <div class="service-name">${escapeHtml(service.service_name)}</div>
                        <div class="service-code">SV-${escapeHtml(service.service_id)}</div>
                        <div class="service-code">${escapeHtml(service.service_category_name || "Chua phan loai")}</div>
                    </td>
                    <td><span class="service-status ${active ? "service-status--active" : "service-status--paused"}">${active ? "Hoat dong" : "Da an/Ngung"}</span></td>
                    <td>${money(service.base_price)}</td>
                    <td class="text-center">${number(service.booking_count_in_period)}</td>
                    <td>${escapeHtml(service.no_revenue_reason)}</td>
                    <td>
                        <div class="service-action-group">
                            <button type="button" class="service-action-btn">Xem</button>
                            <button type="button" class="service-action-btn service-action-btn--danger">Xem xet tam an</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join("");
    }

    $(document).ready(function () {
        DashboardEngine.run([
            { url: root.dataset.summaryUrl, onSuccess: renderSummary },
            { url: root.dataset.catalogUrl, onSuccess: renderCatalog },
            { url: root.dataset.highestRevenueUrl, onSuccess: renderHighestRevenueService },
            { url: root.dataset.revenueAnalysisUrl, onSuccess: renderServiceRevenue },
            { url: root.dataset.noActivityUrl, onSuccess: renderNoActivityServices },
            { url: root.dataset.mostProfitableUrl, onSuccess: renderMostProfitableService },
        ]);
    });
})(window.jQuery);
