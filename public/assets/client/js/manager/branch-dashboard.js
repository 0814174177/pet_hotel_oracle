(function ($) {
    const root = document.getElementById("managerBranchDashboard");

    if (!root || !window.DashboardEngine || !window.DashboardKpiAdapter) {
        return;
    }

    const Kpi = window.DashboardKpiAdapter;

    /**
     * Mo ta chuc nang:
     * Chuyen gia tri dem null/undefined ve so an toan de render KPI.
     *
     * Input:
     * - value: Gia tri co the la number/string/null.
     *
     * Output:
     * - Number hop le, mac dinh 0.
     *
     * Ghi chu:
     * - Khong tinh toan nghiep vu, chi format hien thi.
     */
    function safeCount(value) {
        const number = Number(value);

        return Number.isFinite(number) ? number : 0;
    }

    /**
     * Mo ta chuc nang:
     * Format so luong KPI kem don vi ngan gon.
     *
     * Input:
     * - value: So luong can hien thi.
     * - unit: Don vi hien thi.
     *
     * Output:
     * - Chuoi hien thi an toan cho KPI card.
     *
     * Ghi chu:
     * - Chi phuc vu render frontend, khong tao query string hay goi API.
     */
    function formatCount(value, unit) {
        const formatted = Kpi.formatNumber(safeCount(value));

        return unit ? `${formatted} ${unit}` : formatted;
    }

    /**
     * Mo ta chuc nang:
     * Chuyen KPI co current/previous/growth_percent thanh comparison cho KpiAdapter.
     *
     * Input:
     * - payload: Data KPI tu Repository.
     *
     * Output:
     * - Object comparison gom growth_percent va trend.
     *
     * Ghi chu:
     * - Ky truoc da duoc backend tinh, JS khong tu tinh ky truoc.
     */
    function scheduleComparison(payload) {
        return {
            growth_percent: payload?.growth_percent ?? null,
            trend: payload?.trend ?? "no_previous_data",
        };
    }

    /**
     * Mo ta chuc nang:
     * Tao text va trang thai trend cho KPI phong walk-in.
     *
     * Input:
     * - payload.walkin_rooms gom available_rooms va warning.
     *
     * Output:
     * - Object trend cho KpiAdapter render canh bao.
     *
     * Ghi chu:
     * - Canh bao nghiep vu den tu Repository; JS chi chon mau hien thi theo so phong.
     */
    function walkinTrend(payload) {
        const availableRooms = safeCount(payload?.available_rooms);

        return {
            text: payload?.warning || "CÒN PHÒNG",
            trend: availableRooms <= 5 ? "down" : "up",
            changePercent: null,
        };
    }

    /**
     * Mo ta chuc nang:
     * Chuan hoa severity canh bao kho ve red/yellow/green.
     *
     * Input:
     * - severity: Gia tri tu API.
     *
     * Output:
     * - Chuoi severity hop le cho DOM/CSS.
     *
     * Ghi chu:
     * - Quy tac severity duoc tinh o Repository, JS chi phong ve gia tri la.
     */
    function normalizeInventorySeverity(severity) {
        const normalized = String(severity || "").toLowerCase();

        return ["red", "yellow", "green"].includes(normalized)
            ? normalized
            : "green";
    }

    /**
     * Mo ta chuc nang:
     * Chuan hoa severity booking huy sat gio ve red/yellow/orange/green.
     *
     * Input:
     * - severity: Gia tri tu API cho tung dong booking.
     *
     * Output:
     * - Chuoi severity hop le cho badge canh bao.
     *
     * Ghi chu:
     * - Quy tac severity duoc tinh o Repository, JS chi phong ve gia tri la.
     */
    function normalizeLateCancelSeverity(severity) {
        const normalized = String(severity || "").toLowerCase();

        return ["red", "yellow", "orange", "green"].includes(normalized)
            ? normalized
            : "green";
    }

    /**
     * Mo ta chuc nang:
     * Cap nhat class mau cho title canh bao kho theo severity.
     *
     * Input:
     * - titleNode: Node hien thi noi dung canh bao.
     * - severity: red/yellow/green.
     *
     * Output:
     * - DOM title duoc cap nhat class mau tuong ung.
     *
     * Ghi chu:
     * - Chi cap nhat class canh bao, khong thay doi layout.
     */
    function applyInventorySeverity(titleNode, severity) {
        if (!titleNode) {
            return;
        }

        titleNode.classList.remove(
            "manager-alert-title--red",
            "manager-alert-title--orange",
            "manager-alert-title--green",
        );

        if (severity === "red") {
            titleNode.classList.add("manager-alert-title--red");
        } else if (severity === "yellow") {
            titleNode.classList.add("manager-alert-title--orange");
        } else {
            titleNode.classList.add("manager-alert-title--green");
        }
    }

    /**
     * Mo ta chuc nang:
     * Render nhom KPI tong quan lich chi nhanh len cac card co san.
     *
     * Input:
     * - data.checkin/current/previous/growth_percent
     * - data.checkout/current/previous/growth_percent
     * - data.spa_grooming/current/previous/growth_percent
     * - data.walkin_rooms/available_rooms/warning
     *
     * Output:
     * - Cap nhat DOM KPI card trong managerBranchDashboard.
     *
     * Ghi chu:
     * - Khong goi API rieng, khong bind nut loc, khong tu tinh query string.
     */
    function renderOverview(data) {
        const payload = data || {};
        const scheduleKpis = [
            {
                cardKey: "check-in-schedule",
                dataKey: "checkin",
                unit: "pet",
            },
            {
                cardKey: "check-out-schedule",
                dataKey: "checkout",
                unit: "pet",
            },
            {
                cardKey: "grooming-schedule",
                dataKey: "spa_grooming",
                unit: "lịch",
            },
        ];

        scheduleKpis.forEach((config) => {
            Kpi.renderKpiCard(config.cardKey, payload[config.dataKey] || {}, {
                root,
                periodLabel: "so với kỳ trước",
                getValue: (item) => formatCount(item?.current, config.unit),
                getComparison: scheduleComparison,
            });
        });

        Kpi.renderKpiCard("walk-in-available-rooms", payload.walkin_rooms || {}, {
            root,
            periodLabel: "",
            getValue: (item) => formatCount(item?.available_rooms, "phòng"),
            getTrend: walkinTrend,
            getComparison: () => ({
                growth_percent: 0,
                trend: "neutral",
            }),
        });
    }

    /**
     * Mo ta chuc nang:
     * Render canh bao ton kho trong bang cong viec khan cap cua Manager Dashboard.
     *
     * Input:
     * - data.current.out_of_stock_count
     * - data.current.low_stock_count
     * - data.current.inventory_warning
     * - data.current.severity
     *
     * Output:
     * - Cap nhat so vat tu het hang, sap het, noi dung canh bao va mau severity.
     *
     * Ghi chu:
     * - Khong goi API rieng, khong bind nut loc, khong tu tao query string.
     */
    function renderInventoryWarning(data) {
        const current = data?.current || {};
        const severity = normalizeInventorySeverity(current.severity);
        const card = root.querySelector("[data-inventory-warning-card]");
        const summary = root.querySelector("[data-inventory-warning-summary]");
        const directTitle = card
            ? Array.from(card.children).find((child) => child.tagName === "SPAN")
            : null;
        const title = root.querySelector("[data-inventory-warning-text]")
            || directTitle;
        const outOfStockNode = root.querySelector("[data-inventory-out-of-stock-count]");
        const lowStockNode = root.querySelector("[data-inventory-low-stock-count]");
        const outOfStockCount = safeCount(current.out_of_stock_count);
        const lowStockCount = safeCount(current.low_stock_count);

        if (card) {
            card.dataset.severity = severity;
        }

        if (summary) {
            summary.dataset.severity = severity;
        }

        if (title) {
            title.textContent = current.inventory_warning || "AN TOAN: VAT TU TIEU HAO DANG O MUC AN TOAN";
            applyInventorySeverity(title, severity);
        }

        if (outOfStockNode) {
            outOfStockNode.textContent = Kpi.formatNumber(outOfStockCount);
        }

        if (lowStockNode) {
            lowStockNode.textContent = Kpi.formatNumber(lowStockCount);
        }
    }

    /**
     * Mo ta chuc nang:
     * Render canh bao y te tam thoi trong bang cong viec khan cap.
     *
     * Input:
     * - data.current.health_warning_count
     * - data.current.health_warning_text
     * - data.current.severity
     *
     * Output:
     * - Cap nhat so pet can theo doi, noi dung canh bao va mau severity.
     *
     * Ghi chu:
     * - Khong goi API rieng, khong bind nut loc, khong tu tao query string.
     */
    function renderHealthWarning(data) {
        const current = data?.current || {};
        const severity = normalizeInventorySeverity(current.severity);
        const card = root.querySelector("[data-health-warning-card]");
        const summary = root.querySelector("[data-health-warning-summary]");
        const directTitle = card
            ? Array.from(card.children).find((child) => child.tagName === "SPAN")
            : null;
        const title = root.querySelector("[data-health-warning-text]")
            || directTitle;
        const countNode = root.querySelector("[data-health-warning-count]");
        const warningCount = safeCount(current.health_warning_count);

        if (card) {
            card.dataset.severity = severity;
        }

        if (summary) {
            summary.dataset.severity = severity;
        }

        if (title) {
            title.textContent = current.health_warning_text || "0 Canh bao Y te - Tat ca cac be deu dang khoe manh va an uong tot.";
            applyInventorySeverity(title, severity);
        }

        if (countNode) {
            countNode.textContent = Kpi.formatNumber(warningCount);
        }
    }

    /**
     * Mo ta chuc nang:
     * Render canh bao radar rui ro tai chinh trong ky loc.
     *
     * Input:
     * - data.current.cancelled_or_refunded_orders
     * - data.current.lost_revenue_amount
     * - data.current.financial_risk_warning
     * - data.current.severity
     *
     * Output:
     * - Cap nhat so don huy/hoan, gia tri that thoat, noi dung canh bao va mau severity.
     *
     * Ghi chu:
     * - Khong goi API rieng, khong bind nut loc, khong tu tao query string.
     */
    function renderFinancialRiskWarning(data) {
        const current = data?.current || {};
        const severity = normalizeInventorySeverity(current.severity);
        const card = root.querySelector("[data-financial-risk-warning-card]");
        const warningNode = root.querySelector("[data-financial-risk-warning-text]");
        const cancelledNode = root.querySelector("[data-financial-risk-cancelled-count]");
        const lostAmountNode = root.querySelector("[data-financial-risk-lost-amount]");
        const cancelledCount = safeCount(current.cancelled_or_refunded_orders);
        const lostAmount = Number(current.lost_revenue_amount);

        if (card) {
            card.dataset.severity = severity;
        }

        if (warningNode) {
            warningNode.dataset.severity = severity;
            warningNode.textContent = current.financial_risk_warning || "BINH THUONG";
        }

        if (cancelledNode) {
            cancelledNode.textContent = Kpi.formatNumber(cancelledCount);
        }

        if (lostAmountNode) {
            lostAmountNode.textContent = Kpi.formatCurrency(
                Number.isFinite(lostAmount) ? lostAmount : 0,
            );
        }
    }

    /**
     * Mo ta chuc nang:
     * Them mot cell text vao dong bang booking huy sat gio.
     *
     * Input:
     * - row: Dong bang dang render.
     * - text: Noi dung can hien thi.
     * - className: Class CSS tuy chon.
     *
     * Output:
     * - DOM row duoc them cell moi.
     *
     * Ghi chu:
     * - Dung textContent de render an toan voi du lieu null/undefined.
     */
    function appendLateCancelCell(row, text, className = "") {
        const cell = document.createElement("div");

        if (className) {
            cell.className = className;
        }

        cell.textContent = text ?? "";
        row.appendChild(cell);

        return cell;
    }

    /**
     * Mo ta chuc nang:
     * Render danh sach booking bi huy gan gio check-in trong bang cong viec khan cap.
     *
     * Input:
     * - data.current.total_late_cancelled_bookings
     * - data.current.items[]
     *
     * Output:
     * - Cap nhat tong so booking va bang chi tiet booking huy sat gio.
     *
     * Ghi chu:
     * - Khong goi API rieng, khong bind nut loc, khong tu tao query string.
     */
    function renderLateCancelledBookings(data) {
        const current = data?.current || {};
        const items = Array.isArray(current.items) ? current.items : [];
        const totalNode = root.querySelector("[data-late-cancelled-total]");
        const body = root.querySelector("[data-late-cancelled-body]");
        const empty = root.querySelector("[data-late-cancelled-empty]");
        const total = safeCount(current.total_late_cancelled_bookings ?? items.length);

        if (totalNode) {
            totalNode.textContent = Kpi.formatNumber(total);
        }

        if (!body) {
            return;
        }

        body.innerHTML = "";

        if (empty) {
            empty.hidden = items.length > 0;
        }

        if (items.length === 0) {
            return;
        }

        items.forEach((item) => {
            const row = document.createElement("div");
            const severity = normalizeLateCancelSeverity(item?.severity);
            const hoursBeforeCheckin = safeCount(item?.hours_before_checkin);
            const grandTotal = Number(item?.grand_total);
            const warningCell = document.createElement("div");
            const warningBadge = document.createElement("span");

            row.className = "manager-grid-row manager-late-cancel-row";

            appendLateCancelCell(row, item?.booking_id, "cell-bold");
            appendLateCancelCell(row, item?.customer_name);
            appendLateCancelCell(row, item?.checkin_expected_at);
            appendLateCancelCell(row, item?.cancel_time_assumption);
            appendLateCancelCell(
                row,
                `${Kpi.formatNumber(hoursBeforeCheckin, { maximumFractionDigits: 2 })} giờ`,
                "text-right",
            );
            appendLateCancelCell(
                row,
                Kpi.formatCurrency(Number.isFinite(grandTotal) ? grandTotal : 0),
                "text-right cell-bold",
            );

            warningBadge.className = `late-cancel-badge late-cancel-badge--${severity}`;
            warningBadge.textContent = item?.cancel_warning || "";
            warningCell.appendChild(warningBadge);
            row.appendChild(warningCell);
            body.appendChild(row);
        });
    }

    /**
     * Mo ta chuc nang:
     * Them mot cell text vao dong bang top doanh thu dich vu.
     *
     * Input:
     * - row: Dong bang dang render.
     * - text: Noi dung can hien thi.
     * - className: Class CSS tuy chon.
     *
     * Output:
     * - DOM row duoc them cell moi.
     *
     * Ghi chu:
     * - Dung textContent de render an toan voi du lieu API.
     */
    function appendTopRevenueServiceCell(row, text, className = "") {
        const cell = document.createElement("div");

        if (className) {
            cell.className = className;
        }

        cell.textContent = text ?? "";
        row.appendChild(cell);

        return cell;
    }

    /**
     * Mo ta chuc nang:
     * Cap nhat bar chart Top 5 dich vu tu data API.
     *
     * Input:
     * - items[] gom service_name va service_revenue.
     *
     * Output:
     * - Chart.js hien co duoc cap nhat labels va dataset doanh thu.
     *
     * Ghi chu:
     * - Chi update chart co san, khong goi API rieng.
     */
    function updateTopRevenueServicesChart(items) {
        const canvas = document.getElementById("managerTopRevenueServicesChart");
        const labels = items.map((item) => (
            item?.service_name || `DV-${item?.service_id || ""}`
        ));
        const values = items.map((item) => {
            const revenue = Number(item?.service_revenue);

            return Number.isFinite(revenue) ? revenue : 0;
        });

        if (!canvas || !window.Chart) {
            return;
        }

        const chart = typeof window.Chart.getChart === "function"
            ? window.Chart.getChart(canvas)
            : null;

        if (!chart) {
            new window.Chart(canvas, {
                type: "bar",
                data: {
                    labels,
                    datasets: [
                        {
                            label: "Doanh thu (VND)",
                            data: values,
                            backgroundColor: "#3B82F6",
                            borderColor: "#3B82F6",
                            borderRadius: 4,
                            barThickness: 40,
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
                                    return Kpi.formatCurrency(context.parsed.y);
                                },
                            },
                        },
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true },
                    },
                },
            });

            return;
        }

        chart.data.labels = labels;

        if (!Array.isArray(chart.data.datasets) || chart.data.datasets.length === 0) {
            chart.data.datasets = [
                {
                    label: "Doanh thu (VNĐ)",
                    data: [],
                    backgroundColor: "#3B82F6",
                    borderColor: "#3B82F6",
                    borderRadius: 4,
                    barThickness: 40,
                },
            ];
        }

        chart.data.datasets[0].label = "Doanh thu (VNĐ)";
        chart.data.datasets[0].data = values;
        chart.update();
    }

    /**
     * Mo ta chuc nang:
     * Render bang Top 5 dich vu co doanh thu cao nhat.
     *
     * Input:
     * - data.current.items[] gom rank_no, service_name, service_revenue, service_cases.
     *
     * Output:
     * - Cap nhat chart va bang Top 5 dich vu tren Manager Dashboard.
     *
     * Ghi chu:
     * - Khong goi API rieng, khong bind nut loc, khong tu tao query string.
     */
    function renderTopRevenueServices(data) {
        const current = data?.current || {};
        const items = Array.isArray(current.items) ? current.items : [];
        const table = root.querySelector("[data-top-revenue-services-table]");
        const body = root.querySelector("[data-top-revenue-services-body]");
        const empty = root.querySelector("[data-top-revenue-services-empty]");

        updateTopRevenueServicesChart(items);

        if (table) {
            table.hidden = items.length === 0;
        }

        if (empty) {
            empty.hidden = items.length > 0;
        }

        if (!body) {
            return;
        }

        body.innerHTML = "";

        if (items.length === 0) {
            return;
        }

        items.forEach((item) => {
            const row = document.createElement("div");
            const serviceRevenue = Number(item?.service_revenue);

            row.className = "manager-grid-row manager-top-service-row";

            appendTopRevenueServiceCell(row, item?.rank_no, "cell-bold");
            appendTopRevenueServiceCell(row, item?.service_name || "", "cell-medium");
            appendTopRevenueServiceCell(
                row,
                Kpi.formatCurrency(Number.isFinite(serviceRevenue) ? serviceRevenue : 0),
                "text-right cell-bold",
            );
            appendTopRevenueServiceCell(
                row,
                Kpi.formatNumber(safeCount(item?.service_cases)),
                "text-right",
            );

            body.appendChild(row);
        });
    }

    /**
     * Mo ta chuc nang:
     * Them mot cell text vao dong bang co cau doanh thu.
     *
     * Input:
     * - row: Dong bang dang render.
     * - text: Noi dung can hien thi.
     * - className: Class CSS tuy chon.
     *
     * Output:
     * - DOM row duoc them cell moi.
     *
     * Ghi chu:
     * - Dung textContent de render an toan voi du lieu API.
     */
    function appendRevenueStructureCell(row, text, className = "") {
        const cell = document.createElement("div");

        if (className) {
            cell.className = className;
        }

        cell.textContent = text ?? "";
        row.appendChild(cell);

        return cell;
    }

    /**
     * Mo ta chuc nang:
     * Cap nhat donut chart co cau doanh thu tu data API.
     *
     * Input:
     * - items[] gom revenue_group va revenue.
     *
     * Output:
     * - Chart.js hien co duoc cap nhat labels va dataset doanh thu.
     *
     * Ghi chu:
     * - Chi update chart co san, khong goi API rieng.
     */
    function updateRevenueStructureChart(items) {
        const canvas = document.getElementById("managerRevenueStructureChart");
        const labels = items.map((item) => item?.revenue_group || "");
        const values = items.map((item) => {
            const revenue = Number(item?.revenue);

            return Number.isFinite(revenue) ? revenue : 0;
        });
        const colors = ["#10B981", "#F59E0B", "#64748B"];

        if (!canvas || !window.Chart) {
            return;
        }

        const chart = typeof window.Chart.getChart === "function"
            ? window.Chart.getChart(canvas)
            : null;

        if (!chart) {
            new window.Chart(canvas, {
                type: "doughnut",
                data: {
                    labels,
                    datasets: [
                        {
                            data: values,
                            backgroundColor: colors,
                            borderColor: "#ffffff",
                            borderWidth: 3,
                            spacing: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: "bottom" },
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    return `${context.label}: ${Kpi.formatCurrency(context.parsed)}`;
                                },
                            },
                        },
                    },
                },
            });

            return;
        }

        chart.data.labels = labels;

        if (!Array.isArray(chart.data.datasets) || chart.data.datasets.length === 0) {
            chart.data.datasets = [
                {
                    data: [],
                    backgroundColor: colors,
                    borderColor: "#ffffff",
                    borderWidth: 3,
                    spacing: 4,
                },
            ];
        }

        chart.data.datasets[0].data = values;
        chart.data.datasets[0].backgroundColor = colors;
        chart.update();
    }

    /**
     * Mo ta chuc nang:
     * Render co cau doanh thu Pet Hotel, Grooming & Spa va Khac.
     *
     * Input:
     * - data.current.items[] gom revenue_group, revenue, percent_of_total.
     *
     * Output:
     * - Cap nhat donut chart, bang ty trong va summary tong doanh thu.
     *
     * Ghi chu:
     * - Khong goi API rieng, khong bind nut loc, khong tu tao query string.
     */
    function renderRevenueStructure(data) {
        const current = data?.current || {};
        const items = Array.isArray(current.items) ? current.items : [];
        const table = root.querySelector("[data-revenue-structure-table]");
        const body = root.querySelector("[data-revenue-structure-body]");
        const empty = root.querySelector("[data-revenue-structure-empty]");
        const summary = root.querySelector("[data-revenue-structure-summary]");
        const totalRevenue = items.reduce((sum, item) => {
            const revenue = Number(item?.revenue);

            return sum + (Number.isFinite(revenue) ? revenue : 0);
        }, 0);
        const hasRevenue = totalRevenue > 0;

        updateRevenueStructureChart(items);

        if (table) {
            table.hidden = items.length === 0;
        }

        if (empty) {
            empty.hidden = items.length > 0;
        }

        if (summary) {
            summary.textContent = hasRevenue
                ? `Tổng doanh thu trong kỳ: ${Kpi.formatCurrency(totalRevenue)}.`
                : "Chưa có doanh thu trong kỳ này.";
        }

        if (!body) {
            return;
        }

        body.innerHTML = "";

        if (items.length === 0) {
            return;
        }

        items.forEach((item) => {
            const row = document.createElement("div");
            const revenue = Number(item?.revenue);
            const percent = Number(item?.percent_of_total);

            row.className = "manager-grid-row manager-revenue-structure-row";

            appendRevenueStructureCell(row, item?.revenue_group || "", "cell-medium");
            appendRevenueStructureCell(
                row,
                Kpi.formatCurrency(Number.isFinite(revenue) ? revenue : 0),
                "text-right cell-bold",
            );
            appendRevenueStructureCell(
                row,
                Kpi.formatPercent(Number.isFinite(percent) ? percent : 0),
                "text-right",
            );

            body.appendChild(row);
        });
    }

    /**
     * Mo ta chuc nang:
     * Them mot cell text vao dong bang cong no.
     *
     * Input:
     * - row: Dong bang dang render.
     * - text: Noi dung can hien thi.
     * - className: Class CSS tuy chon.
     *
     * Output:
     * - DOM row duoc them cell moi.
     *
     * Ghi chu:
     * - Dung textContent de render an toan voi du lieu API.
     */
    function appendUnpaidInvoiceCell(row, text, className = "") {
        const cell = document.createElement("div");

        if (className) {
            cell.className = className;
        }

        cell.textContent = text ?? "";
        row.appendChild(cell);

        return cell;
    }

    /**
     * Mo ta chuc nang:
     * Render danh sach hoa don chua thanh toan du trong bang So Ghi Cong No.
     *
     * Input:
     * - data.current.total_unpaid_invoices
     * - data.current.total_remaining_debt
     * - data.current.items[]
     *
     * Output:
     * - Cap nhat tong so hoa don, tong cong no va bang chi tiet cong no.
     *
     * Ghi chu:
     * - Khong goi API rieng, khong bind nut loc, khong tu tao query string.
     */
    function renderUnpaidInvoices(data) {
        const current = data?.current || {};
        const items = Array.isArray(current.items) ? current.items : [];
        const table = root.querySelector("[data-unpaid-invoices-table]");
        const body = root.querySelector("[data-unpaid-invoices-body]");
        const empty = root.querySelector("[data-unpaid-invoices-empty]");
        const totalNode = root.querySelector("[data-unpaid-invoices-total]");
        const totalDebtNode = root.querySelector("[data-unpaid-invoices-total-debt]");
        const totalInvoices = safeCount(current.total_unpaid_invoices ?? items.length);
        const totalRemainingDebt = Number(current.total_remaining_debt);

        if (totalNode) {
            totalNode.textContent = Kpi.formatNumber(totalInvoices);
        }

        if (totalDebtNode) {
            totalDebtNode.textContent = Kpi.formatCurrency(
                Number.isFinite(totalRemainingDebt) ? totalRemainingDebt : 0,
            );
        }

        if (table) {
            table.hidden = items.length === 0;
        }

        if (empty) {
            empty.hidden = items.length > 0;
        }

        if (!body) {
            return;
        }

        body.innerHTML = "";

        if (items.length === 0) {
            return;
        }

        items.forEach((item) => {
            const row = document.createElement("div");
            const grandTotal = Number(item?.grand_total);
            const paidAmount = Number(item?.paid_amount);
            const remainingDebt = Number(item?.remaining_debt);
            const severity = normalizeInventorySeverity(item?.severity);
            const warningCell = document.createElement("div");
            const warningBadge = document.createElement("span");

            row.className = "manager-grid-row manager-debt-row";

            appendUnpaidInvoiceCell(row, item?.customer_id, "cell-medium");
            appendUnpaidInvoiceCell(row, item?.customer_name || "");
            appendUnpaidInvoiceCell(row, item?.phone || "");
            appendUnpaidInvoiceCell(row, item?.order_id, "cell-bold");
            appendUnpaidInvoiceCell(
                row,
                Kpi.formatCurrency(Number.isFinite(grandTotal) ? grandTotal : 0),
                "text-right",
            );
            appendUnpaidInvoiceCell(
                row,
                Kpi.formatCurrency(Number.isFinite(paidAmount) ? paidAmount : 0),
                "text-right",
            );
            appendUnpaidInvoiceCell(
                row,
                Kpi.formatCurrency(Number.isFinite(remainingDebt) ? remainingDebt : 0),
                "text-right cell-bold",
            );
            appendUnpaidInvoiceCell(row, item?.debt_created_at || "");

            warningBadge.className = `debt-badge debt-badge--${severity}`;
            warningBadge.textContent = item?.debt_warning || "";
            warningCell.appendChild(warningBadge);
            row.appendChild(warningCell);
            body.appendChild(row);
        });
    }

    $(document).ready(function () {
        DashboardEngine.run([
            {
                url: root.dataset.overviewUrl,
                onSuccess: renderOverview,
            },
            {
                url: root.dataset.healthWarningUrl,
                onSuccess: renderHealthWarning,
            },
            {
                url: root.dataset.financialRiskWarningUrl,
                onSuccess: renderFinancialRiskWarning,
            },
            {
                url: root.dataset.inventoryWarningUrl,
                onSuccess: renderInventoryWarning,
            },
            {
                url: root.dataset.lateCancelledBookingsUrl,
                onSuccess: renderLateCancelledBookings,
            },
            {
                url: root.dataset.topRevenueServicesUrl,
                onSuccess: renderTopRevenueServices,
            },
            {
                url: root.dataset.revenueStructureUrl,
                onSuccess: renderRevenueStructure,
            },
            {
                url: root.dataset.unpaidInvoicesUrl,
                onSuccess: renderUnpaidInvoices,
            },
        ]);
    });
})(window.jQuery);
