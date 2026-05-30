(function () {
    const FILTER_KEYS = [
        "filter_type",
        "period_type",
        "period",
        "date",
        "month",
        "year",
        "start_date",
        "end_date",
        "high_cancel_amount",
        "cancel_rate_threshold",
        "action_count_threshold",
        "revenue_drop_threshold",
    ];
    const PERIOD_FILTER_KEYS = [
        "period_type",
        "period",
        "date",
        "month",
        "year",
        "start_date",
        "end_date",
    ];
    const ENDPOINTS = {
        currentOccupancy: "currentOccupancyUrl",
        occupancyRate: "occupancyRateUrl",
        revpar: "revparUrl",
        customerTrend: "customerTrendUrl",
        totalRevenue: "totalRevenueUrl",
        estimatedCogs: "estimatedCogsUrl",
        revenueMix: "revenueMixUrl",
        revenueCogsTrend: "revenueCogsTrendUrl",
        branchRevenue: "branchRevenueUrl",
        branchRanking: "branchRankingUrl",
        topUsedServices: "topUsedServicesUrl",
        riskAlerts: "riskAlertsUrl",
    };

    function create(root) {
        if (!root || !window.PetHotelApi) {
            throw new Error("Không thể khởi tạo API hook cho CEO Dashboard.");
        }

        function currentSearchParams() {
            const source = new URLSearchParams(window.location.search);
            const params = new URLSearchParams();

            FILTER_KEYS.forEach((key) => {
                if (source.has(key)) {
                    params.set(key, source.get(key));
                }
            });

            if (!params.has("filter_type") && !params.has("period_type") && !params.has("period")) {
                params.set("filter_type", "month");
            }

            return params;
        }

        function endpointUrl(endpointName) {
            const datasetKey = ENDPOINTS[endpointName];
            const baseUrl = root.dataset[datasetKey];

            if (!baseUrl) {
                throw new Error(`Thiếu cấu hình endpoint: ${endpointName}`);
            }

            const url = new URL(baseUrl, window.location.origin);
            currentSearchParams().forEach((value, key) => url.searchParams.set(key, value));

            return url.toString();
        }

        async function get(endpointName) {
            const payload = await window.PetHotelApi.request(endpointUrl(endpointName), {
                method: "GET",
            });

            return payload?.data;
        }

        async function getAll() {
            const results = await Promise.allSettled(
                Object.keys(ENDPOINTS).map(async (endpointName) => [
                    endpointName,
                    await get(endpointName),
                ])
            );
            const data = {};
            const errors = [];

            results.forEach((result) => {
                if (result.status === "fulfilled") {
                    data[result.value[0]] = result.value[1];
                } else {
                    errors.push(result.reason.message);
                }
            });

            return { data, errors };
        }

        function getFilterType() {
            return currentSearchParams().get("filter_type")
                || currentSearchParams().get("period_type")
                || currentSearchParams().get("period")
                || "month";
        }

        function setFilterType(filterType) {
            const params = new URLSearchParams(window.location.search);

            PERIOD_FILTER_KEYS.forEach((key) => params.delete(key));
            params.set("filter_type", filterType);
            window.history.replaceState({}, "", `${window.location.pathname}?${params.toString()}`);
        }

        return {
            get,
            getAll,
            getFilterType,
            setFilterType,
        };
    }

    window.CeoDashboardApi = {
        create,
    };
})();
