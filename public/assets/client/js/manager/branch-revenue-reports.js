(function ($) {
    const root = document.getElementById("managerRevenueReportPage");

    if (!root || !window.DashboardEngine) {
        return;
    }

    function setText(selector, value) {
        const node = root.querySelector(selector);

        if (node && value !== undefined && value !== null && value !== "") {
            node.textContent = value;
        }
    }

    function renderTarget(data) {
        setText("[data-target-current]", data.current_revenue);
        setText("[data-target-progress]", data.progress_percent);
    }

    function renderServiceMix(data) {
        if (!data || !Array.isArray(data.service_mix)) {
            return;
        }

        const hotel = data.service_mix.find((item) => /hotel/i.test(item.name || item.label || ""));
        const spa = data.service_mix.find((item) => /spa|groom/i.test(item.name || item.label || ""));

        setText("[data-hotel-share]", hotel?.value);
        setText("[data-spa-share]", spa?.value);
    }

    $(document).ready(function () {
        DashboardEngine.run([
            { url: root.dataset.targetProgressUrl, onSuccess: renderTarget },
            { url: root.dataset.revenueComparisonUrl, onSuccess: function () {} },
            { url: root.dataset.serviceMixUrl, onSuccess: renderServiceMix },
            { url: root.dataset.employeePerformanceUrl, onSuccess: function () {} },
            { url: root.dataset.customerRetentionUrl, onSuccess: function () {} },
        ]);
    });
})(window.jQuery);
