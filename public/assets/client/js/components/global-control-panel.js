(function (window) {
    window.GlobalControlPanel = window.GlobalControlPanel || {
        init: function () {
            if (!window.DashboardEngine) {
                console.warn(
                    "GlobalControlPanel requires DashboardEngine for dashboard filters.",
                );
            }
        },
    };
})(window);
