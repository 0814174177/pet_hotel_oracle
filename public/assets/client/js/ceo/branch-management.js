(function ($) {
    const root = document.getElementById("ceoBranchPage");

    if (!root || !window.DashboardEngine || !window.DashboardKpiAdapter) {
        return;
    }

    const Kpi = window.DashboardKpiAdapter;

    function number(value) {
        return new Intl.NumberFormat("vi-VN").format(Number(value) || 0);
    }

    function setCard(key, value, trend) {
        Kpi.renderKpiCard(key, { value, trend }, {
            root,
            periodLabel: "",
            getValue: (payload) => payload.value,
            getTrend: (payload) => payload.trend,
        });
    }

    function renderActiveCount(data) {
        setCard("active-branch-count", `${data.value || 0} co so`, "He thong van hanh");
    }

    function renderHighest(data) {
        if (data) {
            setCard("highest-revenue-branch", `${number(data.revenue)}d`, data.branch_name || "");
        }
    }

    function renderLowest(data) {
        if (data) {
            setCard("lowest-occupancy-branch", `${data.occupancy_rate || 0}%`, data.branch_name || "");
        }
    }

    function renderBranches(data) {
        const tbody = root.querySelector(".branch-table tbody");

        if (!tbody || !Array.isArray(data)) {
            return;
        }

        tbody.innerHTML = data.map((branch) => `
            <tr>
                <td>
                    <div class="branch-name">${branch.branch_name || ""}</div>
                    <div class="branch-code">CN-${branch.branch_id}</div>
                </td>
                <td>${branch.region || ""}</td>
                <td><span class="branch-revenue">${number(branch.revenue)}d</span></td>
                <td class="text-center"><span class="${Number(branch.occupancy_rate) >= 50 ? "occupancy-high" : "occupancy-low"}">${branch.occupancy_rate || 0}%</span></td>
                <td>
                    <div class="manager-name">${branch.manager_name || "Chua gan"}</div>
                    <div class="manager-phone">${branch.address || ""}</div>
                </td>
                <td><span class="status-badge ${branch.status === "active" ? "status-active" : "status-maintenance"}">${branch.status === "active" ? "Hoat dong" : "Tam ngung"}</span></td>
                <td><button type="button" class="branch-action-btn">Xem chi tiet</button></td>
            </tr>
        `).join("");
    }

    $(document).ready(function () {
        DashboardEngine.run([
            { url: root.dataset.activeCountUrl, onSuccess: renderActiveCount },
            { url: root.dataset.highestRevenueUrl, onSuccess: renderHighest },
            { url: root.dataset.lowestOccupancyUrl, onSuccess: renderLowest },
            { url: root.dataset.branchesUrl, onSuccess: renderBranches },
        ]);
    });
})(window.jQuery);
