(function ($) {
    const root = document.getElementById("ceoBranchPage");

    if (!root || !window.DashboardEngine || !window.DashboardKpiAdapter) {
        return;
    }

    const Kpi = window.DashboardKpiAdapter;
    const searchInput = root.querySelector(".branch-toolbar__search input");
    const filterSelects = root.querySelectorAll(".branch-toolbar__filters select");
    const regionFilter = filterSelects[0] || null;
    const statusFilter = filterSelects[1] || null;

    let branchRows = [];

    function number(value) {
        return new Intl.NumberFormat("vi-VN").format(Number(value) || 0);
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function normalize(value) {
        return String(value ?? "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .trim();
    }

    function setCard(key, value, trend) {
        Kpi.renderKpiCard(
            key,
            { value, trend },
            {
                root,
                periodLabel: "",
                getValue: (payload) => payload.value,
                getTrend: (payload) => payload.trend,
            },
        );
    }

    function renderActiveCount(data) {
        setCard(
            "active-branch-count",
            `${data.value || 0} co so`,
            "He thong van hanh",
        );
    }

    function renderHighest(data) {
        if (data) {
            setCard(
                "highest-revenue-branch",
                `${number(data.revenue)}d`,
                data.branch_name || "",
            );
        }
    }

    function renderLowest(data) {
        if (data) {
            setCard(
                "lowest-occupancy-branch",
                `${data.occupancy_rate || 0}%`,
                data.branch_name || "",
            );
        }
    }

    function statusText(branch) {
        if (branch.status_label) {
            return branch.status_label;
        }

        return branch.status === "active" ? "Hoat dong" : "Tam ngung";
    }

    function isMaintenance(branch) {
        const label = normalize(statusText(branch));

        return branch.status === "maintenance" || label.includes("bao tri");
    }

    function isInactive(branch) {
        const label = normalize(statusText(branch));

        return branch.status === "inactive"
            || label.includes("ngung")
            || label.includes("tam ngung");
    }

    function statusClass(branch) {
        if (isInactive(branch)) {
            return "status-inactive";
        }

        if (isMaintenance(branch)) {
            return "status-maintenance";
        }

        return "status-active";
    }

    function statusMatches(branch, selectedStatus) {
        if (!selectedStatus) {
            return true;
        }

        const normalizedStatus = normalize(selectedStatus);

        if (normalizedStatus === "active") {
            return branch.status === "active" && !isMaintenance(branch);
        }

        if (normalizedStatus === "inactive") {
            return isInactive(branch);
        }

        if (normalizedStatus === "maintenance") {
            return isMaintenance(branch);
        }

        return normalize(branch.status) === normalizedStatus
            || normalize(statusText(branch)).includes(normalizedStatus);
    }

    function searchMatches(branch, keyword) {
        if (!keyword) {
            return true;
        }

        const branchCode = branch.branch_code || `CN-${branch.branch_id || ""}`;
        const haystack = [
            branchCode,
            branch.branch_id,
            branch.branch_name,
            branch.region,
            branch.manager_name,
            branch.manager_phone,
            branch.address,
            statusText(branch),
            branch.warning,
        ].map(normalize).join(" ");

        return haystack.includes(keyword);
    }

    function regionMatches(branch, selectedRegion) {
        if (!selectedRegion) {
            return true;
        }

        return normalize(branch.region).includes(normalize(selectedRegion));
    }

    function filteredBranches() {
        const keyword = normalize(searchInput?.value || "");
        const selectedRegion = regionFilter?.value || "";
        const selectedStatus = statusFilter?.value || "";

        return branchRows.filter((branch) => (
            searchMatches(branch, keyword)
            && regionMatches(branch, selectedRegion)
            && statusMatches(branch, selectedStatus)
        ));
    }

    function populateRegionFilter(rows) {
        if (!regionFilter) {
            return;
        }

        const selectedValue = regionFilter.value;
        const allLabel = regionFilter.options[0]?.textContent || "Tat ca khu vuc";
        const regions = [...new Set(rows.map((branch) => branch.region).filter(Boolean))]
            .sort((left, right) => normalize(left).localeCompare(normalize(right)));

        regionFilter.innerHTML = "";

        const allOption = document.createElement("option");
        allOption.value = "";
        allOption.textContent = allLabel;
        regionFilter.appendChild(allOption);

        regions.forEach((region) => {
            const option = document.createElement("option");
            option.value = region;
            option.textContent = region;
            regionFilter.appendChild(option);
        });

        if ([...regionFilter.options].some((option) => option.value === selectedValue)) {
            regionFilter.value = selectedValue;
        }
    }

    function ensureStatusOptions() {
        if (!statusFilter) {
            return;
        }

        const options = [...statusFilter.options];
        const hasInactive = options.some((option) => option.value === "inactive");

        if (hasInactive) {
            return;
        }

        const inactiveOption = document.createElement("option");
        inactiveOption.value = "inactive";
        inactiveOption.textContent = "Tam ngung";

        const maintenanceOption = options.find((option) => option.value === "maintenance");

        if (maintenanceOption) {
            statusFilter.insertBefore(inactiveOption, maintenanceOption);
        } else {
            statusFilter.appendChild(inactiveOption);
        }
    }

    function renderBranchTable(rows) {
        const tbody = root.querySelector(".branch-table tbody");

        if (!tbody) {
            return;
        }

        if (!rows.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="branch-empty">
                        Khong tim thay chi nhanh phu hop voi bo loc hien tai.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = rows
            .map((branch) => {
                const branchCode = branch.branch_code || `CN-${branch.branch_id || ""}`;
                const occupancyClass = Number(branch.occupancy_rate) >= 50
                    ? "occupancy-high"
                    : "occupancy-low";

                return `
                    <tr>
                        <td>
                            <div class="branch-name">${escapeHtml(branch.branch_name)}</div>
                            <div class="branch-code">${escapeHtml(branchCode)}</div>
                        </td>
                        <td>${escapeHtml(branch.region)}</td>
                        <td><span class="branch-revenue">${number(branch.revenue)}d</span></td>
                        <td class="text-center">
                            <span class="${occupancyClass}">${escapeHtml(branch.occupancy_rate || 0)}%</span>
                        </td>
                        <td>
                            <div class="manager-name">${escapeHtml(branch.manager_name || "Chua gan")}</div>
                            <div class="manager-phone">${escapeHtml(branch.manager_phone || "")}</div>
                        </td>
                        <td>
                            <span class="status-badge ${statusClass(branch)}">${escapeHtml(statusText(branch))}</span>
                        </td>
                        <td><button type="button" class="branch-action-btn">Xem chi tiet</button></td>
                    </tr>
                `;
            })
            .join("");
    }

    function applyBranchFilters() {
        renderBranchTable(filteredBranches());
    }

    function renderBranches(data) {
        branchRows = Array.isArray(data) ? data : [];
        populateRegionFilter(branchRows);
        applyBranchFilters();
    }

    function bindBranchFilters() {
        ensureStatusOptions();

        searchInput?.addEventListener("input", applyBranchFilters);
        regionFilter?.addEventListener("change", applyBranchFilters);
        statusFilter?.addEventListener("change", applyBranchFilters);
    }

    $(document).ready(function () {
        bindBranchFilters();

        DashboardEngine.run([
            { url: root.dataset.activeCountUrl, onSuccess: renderActiveCount },
            { url: root.dataset.highestRevenueUrl, onSuccess: renderHighest },
            { url: root.dataset.lowestOccupancyUrl, onSuccess: renderLowest },
            { url: root.dataset.branchesUrl, onSuccess: renderBranches },
        ]);
    });
})(window.jQuery);
