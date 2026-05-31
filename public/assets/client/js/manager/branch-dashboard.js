(function ($) {
    const root = document.getElementById("managerBranchDashboard");

    if (!root || !window.DashboardEngine) {
        return;
    }

    function setCard(index, value) {
        const node = root.querySelectorAll(".manager-kpi-grid .kpi-card__value")[index];

        if (node && value !== undefined && value !== null && value !== "") {
            node.textContent = value;
        }
    }

    function renderOverview(data) {
        if (Array.isArray(data.kpis)) {
            data.kpis.forEach((item, index) => setCard(index, item.value));
        }
    }

    $(document).ready(function () {
        DashboardEngine.run([
            {
                url: root.dataset.overviewUrl,
                onSuccess: renderOverview,
            },
        ]);
    });
})(window.jQuery);
