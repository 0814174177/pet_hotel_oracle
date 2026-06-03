(function ($) {
    const root = document.getElementById("ceoVendorPage");

    if (!root || !window.DashboardEngine) {
        return;
    }

    function money(value) {
        return `${new Intl.NumberFormat("vi-VN").format(Number(value) || 0)} d`;
    }

    /**
     * Mo ta chuc nang:
     * Format phan tram tang truong cong no an toan.
     *
     * Input:
     * - value: gia tri phan tram co the null/undefined.
     *
     * Output:
     * - Chuoi phan tram co dau + neu tang, hoac N/A khi khong co du lieu.
     */
    function signedPercent(value) {
        if (value == null || Number.isNaN(Number(value))) {
            return "N/A";
        }

        const number = Number(value);
        const sign = number > 0 ? "+" : "";

        return `${sign}${decimal(number)}%`;
    }

    /**
     * Mo ta chuc nang:
     * Escape text truoc khi render HTML cho bang doi tac.
     *
     * Input:
     * - value: gia tri text co the null/undefined.
     *
     * Output:
     * - Chuoi an toan de chen vao template HTML.
     */
    function escapeHtml(value) {
        const node = document.createElement("div");
        node.textContent = value == null ? "" : String(value);

        return node.innerHTML;
    }

    /**
     * Mo ta chuc nang:
     * Format so thap phan an toan cho card OTD.
     *
     * Input:
     * - value: gia tri so co the null/undefined.
     * - maximumFractionDigits: so chu so thap phan toi da.
     *
     * Output:
     * - Chuoi so da format theo vi-VN.
     */
    function decimal(value, maximumFractionDigits = 1) {
        const number = Number(value);

        return new Intl.NumberFormat("vi-VN", {
            maximumFractionDigits,
        }).format(Number.isFinite(number) ? number : 0);
    }

    /**
     * Mo ta chuc nang:
     * Chon class mau cho gia tri gauge OTD.
     *
     * Input:
     * - status: trang thai good/warning/danger tu repository.
     *
     * Output:
     * - Chuoi class CSS tuong ung muc tot/canh bao/nguy co.
     */
    function gaugeValueClass(status) {
        const normalizedStatus = String(status || "").toLowerCase();

        if (normalizedStatus === "danger") {
            return "vendor-gauge__value vendor-gauge__value--danger";
        }

        if (normalizedStatus === "warning") {
            return "vendor-gauge__value vendor-gauge__value--warning";
        }

        return "vendor-gauge__value vendor-gauge__value--good";
    }

    /**
     * Mo ta chuc nang:
     * Chon class badge cho hang doi tac theo tier repository tra ve.
     *
     * Input:
     * - tier: PLATINUM, GOLD, SILVER hoac RISK.
     *
     * Output:
     * - Chuoi class CSS cho badge hang doi tac.
     */
    function vendorTierClass(tier) {
        const normalizedTier = String(tier || "").toLowerCase();

        if (normalizedTier === "platinum") {
            return "vendor-tier vendor-tier--platinum";
        }

        if (normalizedTier === "gold") {
            return "vendor-tier vendor-tier--gold";
        }

        if (normalizedTier === "risk") {
            return "vendor-tier vendor-tier--risk";
        }

        return "vendor-tier vendor-tier--silver";
    }

    /**
     * Mo ta chuc nang:
     * Chuyen status cong no thanh class badge tang truong.
     *
     * Input:
     * - status: normal, watch, warning, danger hoac no_previous_data tu repository.
     *
     * Output:
     * - Chuoi class CSS cho badge cong no.
     */
    function payablesTrendClass(status) {
        const normalizedStatus = String(status || "").toLowerCase();

        if (normalizedStatus === "danger") {
            return "partner-trend-badge partner-trend-badge--danger";
        }

        if (normalizedStatus === "warning") {
            return "partner-trend-badge partner-trend-badge--warning";
        }

        if (normalizedStatus === "watch") {
            return "partner-trend-badge partner-trend-badge--watch";
        }

        return "partner-trend-badge partner-trend-badge--normal";
    }

    /**
     * Mo ta chuc nang:
     * Chuyen status bien dong gia thanh class cua dong table.
     *
     * Input:
     * - status: normal, watch, warning hoac critical tu repository.
     *
     * Output:
     * - Chuoi class CSS cho dong table canh bao gia.
     */
    function priceVarianceRowClass(status) {
        const normalizedStatus = String(status || "").toLowerCase();

        if (normalizedStatus === "critical") {
            return "partner-critical-row";
        }

        if (normalizedStatus === "warning") {
            return "partner-warning-row";
        }

        if (normalizedStatus === "watch") {
            return "partner-watch-row";
        }

        return "partner-normal-row";
    }

    /**
     * Mo ta chuc nang:
     * Chuyen status bien dong gia thanh class badge phan tram.
     *
     * Input:
     * - status: normal, watch, warning hoac critical tu repository.
     *
     * Output:
     * - Chuoi class CSS cho badge bien dong gia.
     */
    function priceVarianceBadgeClass(status) {
        const normalizedStatus = String(status || "").toLowerCase();

        if (normalizedStatus === "critical") {
            return "partner-variance partner-variance--critical";
        }

        if (normalizedStatus === "warning") {
            return "partner-variance partner-variance--warning";
        }

        if (normalizedStatus === "watch") {
            return "partner-variance partner-variance--watch";
        }

        return "partner-variance partner-variance--normal";
    }

    /**
     * Mo ta chuc nang:
     * Ve hoac cap nhat Chart.js tren canvas co san.
     *
     * Input:
     * - id: id canvas chart.
     * - config: cau hinh Chart.js.
     *
     * Output:
     * - Chart.js instance moi duoc render vao canvas, instance cu bi huy.
     */
    function renderChart(id, config) {
        const canvas = document.getElementById(id);

        if (!canvas || !window.Chart) {
            return;
        }

        window.Chart.getChart(canvas)?.destroy();
        new window.Chart(canvas, config);
    }

    /**
     * Mo ta chuc nang:
     * Render danh sach va phan hang doi tac len bang da co san.
     *
     * Input:
     * - data[] gom partner_name, item_count, total_spend_amount, tier, performance_score, performance_rate.
     *
     * Output:
     * - Cap nhat cac dong bang phan hang doi tac.
     */
    function renderVendors(data) {
        const tbody = root.querySelector("[data-vendors-table-body]");
        const vendors = Array.isArray(data) ? data : [];

        if (!tbody) {
            return;
        }

        if (!vendors.length) {
            tbody.innerHTML = `
                <tr>
                    <td class="partner-empty-row" colspan="4">Chưa có dữ liệu đối tác.</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = vendors
            .map(
                (vendor) => `
            <tr>
                <td>
                    <div class="partner-vendor-name">${escapeHtml(vendor.partner_name)}</div>
                    <div class="partner-vendor-category">
                        ${Number(vendor.item_count) || 0} mặt hàng đang cung cấp
                    </div>
                    <div class="partner-purchaser">${escapeHtml(vendor.warning_text)}</div>
                </td>
                <td>
                    <span class="${vendorTierClass(vendor.tier)}">${escapeHtml(vendor.tier || "RISK")}</span>
                </td>
                <td><div class="partner-money">${money(vendor.total_spend_amount)}</div></td>
                <td>
                    <div class="partner-score">
                        <span>*</span>
                        ${decimal(vendor.performance_score)}
                        <small>(${decimal(vendor.performance_rate)}%)</small>
                    </div>
                </td>
            </tr>
        `,
            )
            .join("");
    }

    /**
     * Mo ta chuc nang:
     * Render tong hop OTD toan chuoi len gauge/card co san.
     *
     * Input:
     * - data.on_time_delivery_rate
     * - data.quality_score
     * - data.average_delay_hours
     * - data.low_otd_vendor_count
     *
     * Output:
     * - Cap nhat gauge OTD, chat luong hang hoa, thoi gian tre va canh bao tong hop.
     */
    function renderOnTimeDeliverySummary(data) {
        const summary = data || {};
        const rate = Number(summary.on_time_delivery_rate) || 0;
        const boundedRate = Math.max(0, Math.min(100, rate));
        const lowVendorCount = Number(summary.low_otd_vendor_count) || 0;
        const showAlert = Boolean(summary.show_alert);

        const fill = root.querySelector("[data-otd-fill]");
        const rateNode = root.querySelector("[data-otd-rate]");
        const qualityNode = root.querySelector("[data-quality-score]");
        const delayNode = root.querySelector("[data-delay-hours]");
        const alertNode = root.querySelector("[data-otd-alert]");
        const alertCountNode = root.querySelector(
            "[data-low-otd-vendor-count]",
        );
        const alertMessageNode = root.querySelector("[data-otd-alert-message]");

        if (fill) {
            fill.style.width = `${boundedRate}%`;
        }

        if (rateNode) {
            rateNode.className = gaugeValueClass(summary.status);
            rateNode.textContent = `${decimal(rate)}%`;
        }

        if (qualityNode) {
            qualityNode.textContent = decimal(summary.quality_score);
        }

        if (delayNode) {
            delayNode.textContent = decimal(summary.average_delay_hours);
        }

        if (alertCountNode) {
            alertCountNode.textContent = `${lowVendorCount} đối tác`;
        }

        if (alertMessageNode) {
            alertMessageNode.textContent = summary.warning_text || "";
        }

        if (alertNode) {
            alertNode.hidden = !showAlert;
        }
    }

    /**
     * Mo ta chuc nang:
     * Render quan ly dong tien va cong no phai tra len summary va bar chart co san.
     *
     * Input:
     * - data.summary.total_payable_amount
     * - data.summary.growth_percent
     * - data.summary.warning_text
     * - data.chart[] danh sach thang va tong cong no.
     *
     * Output:
     * - Cap nhat tong cong no, badge tang truong, insight va bieu do cot.
     */
    function renderPayableCashflow(data) {
        const summary = data && data.summary ? data.summary : {};
        const chartRows = Array.isArray(data?.chart) ? data.chart : [];
        const totalNode = root.querySelector("[data-payables-total]");
        const trendNode = root.querySelector("[data-payables-trend]");
        const insightNode = root.querySelector("[data-payables-insight]");

        if (totalNode) {
            totalNode.textContent = money(summary.total_payable_amount);
        }

        if (trendNode) {
            trendNode.className = payablesTrendClass(summary.status);
            trendNode.textContent = `${signedPercent(summary.growth_percent)} so với tháng trước`;
        }

        if (insightNode) {
            insightNode.innerHTML = `<strong>Phân tích rủi ro:</strong> ${escapeHtml(summary.warning_text || "Không có cảnh báo dòng tiền.")}`;
        }

        renderChart("vendorPayablesChart", {
            type: "bar",
            data: {
                labels: chartRows.map(
                    (row) => row.month_label || row.month || "",
                ),
                datasets: [
                    {
                        label: "Tổng công nợ phải trả",
                        data: chartRows.map(
                            (row) => Number(row.total_payable_amount) || 0,
                        ),
                        backgroundColor: "#f87171",
                        borderColor: "#ef4444",
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                return money(context.parsed.y);
                            },
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback(value) {
                                return `${Math.round(Number(value) / 1000000)} tr`;
                            },
                        },
                    },
                },
            },
        });
    }

    /**
     * Mo ta chuc nang:
     * Render bang canh bao bien dong gia nhap len table co san.
     *
     * Input:
     * - data[] gom item_name, supplier_name, average_price_3_months, latest_import_price, variance_percent, status.
     *
     * Output:
     * - Cap nhat cac dong bang canh bao bien dong gia nhap.
     */
    function renderPriceVarianceAlerts(data) {
        const tbody = root.querySelector("[data-price-variance-table-body]");
        const items = Array.isArray(data) ? data : [];

        if (!tbody) {
            return;
        }

        if (!items.length) {
            tbody.innerHTML = `
                <tr>
                    <td class="partner-empty-row" colspan="4">Chưa có cảnh báo biến động giá nhập.</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = items
            .map(
                (item) => `
            <tr class="${priceVarianceRowClass(item.status)}">
                <td>
                    <div class="partner-item-name">${escapeHtml(item.item_name)}</div>
                    <div class="partner-vendor-category">NCC: ${escapeHtml(item.supplier_name)}</div>
                    <div class="partner-purchaser">${escapeHtml(item.warning_text)}</div>
                </td>
                <td>
                    <span class="partner-money">${money(item.average_price_3_months)}</span>
                </td>
                <td>
                    <span class="${String(item.status).toLowerCase() === "critical" ? "partner-money partner-money--danger" : "partner-money"}">
                        ${money(item.latest_import_price)}
                    </span>
                </td>
                <td>
                    <span class="${priceVarianceBadgeClass(item.status)}">
                        ${signedPercent(item.variance_percent)}
                    </span>
                </td>
            </tr>
        `,
            )
            .join("");
    }

    $(document).ready(function () {
        DashboardEngine.run([
            {
                url: root.dataset.vendorsUrl,
                onSuccess: renderVendors,
            },
            {
                url: root.dataset.otdSummaryUrl,
                onSuccess: renderOnTimeDeliverySummary,
            },
            {
                url: root.dataset.payablesUrl,
                onSuccess: renderPayableCashflow,
            },
            {
                url: root.dataset.priceVarianceUrl,
                onSuccess: renderPriceVarianceAlerts,
            },
        ]);
    });
})(window.jQuery);
