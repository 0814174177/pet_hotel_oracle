(function (window) {
    function isEmptyValue(value) {
        return value === null || typeof value === "undefined" || value === "";
    }

    function toNumber(value) {
        const number = Number(value);

        return Number.isFinite(number) ? number : 0;
    }

    function formatNumber(value, options = {}) {
        if (isEmptyValue(value)) {
            return "—";
        }

        return new Intl.NumberFormat("vi-VN", options).format(toNumber(value));
    }

    function formatPercent(value, options = {}) {
        if (isEmptyValue(value)) {
            return "—";
        }

        return `${formatNumber(value, {
            maximumFractionDigits: 2,
            ...options,
        })}%`;
    }

    function formatCurrency(value, options = {}) {
        if (isEmptyValue(value)) {
            return "—";
        }

        return `${formatNumber(value, {
            maximumFractionDigits: 0,
            ...options,
        })} đ`;
    }

    function safeGet(source, path, defaultValue = null) {
        if (!path) {
            return source ?? defaultValue;
        }

        const keys = Array.isArray(path) ? path : String(path).split(".");
        let cursor = source;

        for (const key of keys) {
            if (cursor === null || typeof cursor === "undefined") {
                return defaultValue;
            }

            cursor = cursor[key];
        }

        return typeof cursor === "undefined" ? defaultValue : cursor;
    }

    function comparisonChange(comparison) {
        if (!comparison) {
            return null;
        }

        return (
            comparison.change_percent ??
            comparison.growth_percent ??
            comparison.percent ??
            null
        );
    }

    function normalizeTrend(trend, changePercent = null) {
        const normalized = String(trend || "").toLowerCase();

        if (["no_previous_data", "no-previous-data"].includes(normalized)) {
            return "no_previous_data";
        }

        if (["up", "increase", "increased", "positive"].includes(normalized)) {
            return "up";
        }

        if (
            ["down", "decrease", "decreased", "negative"].includes(normalized)
        ) {
            return "down";
        }

        if (["neutral", "equal", "flat", "same"].includes(normalized)) {
            return "neutral";
        }

        if (!isEmptyValue(changePercent)) {
            const numericChange = toNumber(changePercent);

            if (numericChange > 0) {
                return "up";
            }

            if (numericChange < 0) {
                return "down";
            }
        }

        return "neutral";
    }

    function formatTrend(comparison, options = {}) {
        const changePercent = comparisonChange(comparison);
        const trend = normalizeTrend(comparison?.trend, changePercent);

        if (trend === "no_previous_data" || isEmptyValue(changePercent)) {
            return {
                text: options.noPreviousText || "Chưa có dữ liệu kỳ trước",
                trend: "no_previous_data",
                changePercent: null,
            };
        }

        return {
            text: formatPercent(Math.abs(toNumber(changePercent))),
            trend,
            changePercent: toNumber(changePercent),
        };
    }

    function escapeSelectorValue(value) {
        const stringValue = String(value);

        if (window.CSS && typeof window.CSS.escape === "function") {
            return window.CSS.escape(stringValue);
        }

        return stringValue.replace(/\\/g, "\\\\").replace(/"/g, '\\"');
    }

    function findKpiCard(scope, kpiKey) {
        const root = scope || document;
        const escapedKey = escapeSelectorValue(kpiKey);
        const selector = `[data-kpi="${escapedKey}"], [data-kpi-key="${escapedKey}"]`;

        if (typeof root.matches === "function" && root.matches(selector)) {
            return root;
        }

        return root.querySelector(selector);
    }

    function setTrendState(card, trendResult, options = {}) {
        const trendNode = card.querySelector(
            "[data-kpi-trend], .kpi-card__trend",
        );
        const arrowNode = card.querySelector(
            "[data-kpi-arrow], .kpi-card__arrow",
        );

        if (!trendNode) {
            return;
        }

        const trend = normalizeTrend(
            trendResult?.trend,
            trendResult?.changePercent,
        );
        const isIncrease = trend === "up";
        const isDecrease = trend === "down";
        const positiveWhenIncrease = options.positiveWhenIncrease !== false;
        const isPositive = positiveWhenIncrease ? !isDecrease : !isIncrease;
        const stateClass =
            trend === "neutral" || trend === "no_previous_data"
                ? "kpi-card__trend--neutral"
                : isPositive
                  ? "kpi-card__trend--positive"
                  : "kpi-card__trend--negative";

        trendNode.classList.remove(
            "kpi-card__trend--positive",
            "kpi-card__trend--negative",
            "kpi-card__trend--neutral",
        );
        trendNode.classList.add(stateClass);

        if (arrowNode) {
            arrowNode.textContent = isIncrease ? "▲" : isDecrease ? "▼" : "";
        }
    }

    function renderKpiCard(kpiKey, payload, config = {}) {
        const card = findKpiCard(config.root || document, kpiKey);

        if (!card) {
            return;
        }

        const valueNode = card.querySelector(
            "[data-kpi-value], .kpi-card__value",
        );
        const trendNode = card.querySelector(
            "[data-kpi-trend], .kpi-card__trend",
        );
        const trendValueNode = card.querySelector(
            "[data-kpi-trend-value], .kpi-card__trend-value",
        );
        const periodLabelNode = card.querySelector(
            "[data-kpi-period-label], .kpi-card__period-label",
        );
        const detailNode = card.querySelector("[data-kpi-detail]");
        const fallbackValue = config.emptyValue ?? "—";
        const fallbackDetail = config.emptyDetail ?? "";
        const value =
            typeof config.getValue === "function"
                ? config.getValue(payload)
                : safeGet(payload, "current.value", null);
        const detail =
            typeof config.getDetail === "function"
                ? config.getDetail(payload)
                : null;
        const comparison =
            typeof config.getComparison === "function"
                ? config.getComparison(payload)
                : safeGet(payload, "comparison", null);
        const formattedTrend = formatTrend(comparison, config);
        const customTrend =
            typeof config.getTrend === "function"
                ? config.getTrend(payload)
                : null;
        const trendResult =
            customTrend && typeof customTrend === "object"
                ? { ...formattedTrend, ...customTrend }
                : {
                      ...formattedTrend,
                      text: isEmptyValue(customTrend)
                          ? formattedTrend.text
                          : customTrend,
                  };

        if (valueNode) {
            valueNode.textContent = isEmptyValue(value) ? fallbackValue : value;
        }

        if (trendNode && !config.preserveTrend) {
            const targetNode = trendValueNode || trendNode;
            targetNode.textContent = isEmptyValue(trendResult.text)
                ? config.emptyTrend || "Chưa có dữ liệu kỳ trước"
                : trendResult.text;
            setTrendState(card, trendResult, config);
        }

        if (periodLabelNode) {
            const periodLabel =
                typeof config.getPeriodLabel === "function"
                    ? config.getPeriodLabel(payload)
                    : config.periodLabel;

            if (typeof periodLabel !== "undefined") {
                periodLabelNode.textContent = periodLabel;
            }
        }

        if (detailNode) {
            detailNode.textContent = isEmptyValue(detail)
                ? fallbackDetail
                : detail;
        }
    }

    window.DashboardKpiAdapter = {
        renderKpiCard,
        formatNumber,
        formatPercent,
        formatCurrency,
        formatTrend,
        safeGet,
        setTrendState,
    };

    function toIsoDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");

        return `${year}-${month}-${day}`;
    }

    function defaultDateRange() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const startDate = new Date(today);
        startDate.setDate(today.getDate() - 2);

        const endDate = new Date(today);
        endDate.setDate(today.getDate() - 1);

        return {
            start_date: toIsoDate(startDate),
            end_date: toIsoDate(endDate),
        };
    }

    function ensureDefaultDateRange(startDateEl, endDateEl) {
        if (!startDateEl || !endDateEl) {
            return;
        }

        if (startDateEl.value || endDateEl.value) {
            return;
        }

        const range = defaultDateRange();
        startDateEl.value = range.start_date;
        endDateEl.value = range.end_date;
    }

    const DashboardEngine = {
        run: function (apiConfigs) {
            const configs = Array.isArray(apiConfigs) ? apiConfigs : [];
            const applyBtn = document.querySelector(".js-apply-filter");

            if (!applyBtn) {
                console.error("Không tìm thấy phần tử .js-apply-filter");
                return;
            }

            // Hàm xử lý logic khi click
            const handleFilterClick = function () {
                const startDateEl = document.querySelector(".js-start-date");
                const endDateEl = document.querySelector(".js-end-date");

                ensureDefaultDateRange(startDateEl, endDateEl);

                const payload = {
                    start_date: startDateEl ? startDateEl.value : "",
                    end_date: endDateEl ? endDateEl.value : "",
                };

                if (
                    payload.start_date &&
                    payload.end_date &&
                    payload.start_date > payload.end_date
                ) {
                    // Đổi alert thành Báo Popup để thân thiện hơn với người dùng
                    alert("Ngày bắt đầu không thể lớn hơn ngày kết thúc.");
                    return;
                }

                // Khởi tạo chuỗi query string từ object payload
                const queryString = new URLSearchParams(payload).toString();

                configs.forEach(function (config) {
                    // Lặp qua từng cấu hình API
                    if (!config || !config.url) return;

                    if (typeof config.onBefore === "function") {
                        config.onBefore(payload);
                    }

                    const requestUrl = `${config.url}?${queryString}`; // Kết hợp URL với query string

                    fetch(requestUrl, {
                        method: "GET",
                        headers: {
                            Accept: "application/json",
                        },
                    })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error(
                                    `HTTP error! status: ${response.status}`, // Thêm thông tin lỗi HTTP vào log để dễ dàng debug
                                );
                            }
                            return response.json(); // Phân tích phản hồi thành JSON
                        })
                        .then((data) => {
                            if (
                                data.success &&
                                typeof config.onSuccess === "function"
                            ) {
                                console.log(
                                    "Dashboard API response:",
                                    config.url,
                                    data,
                                );
                                config.onSuccess(data.data);
                            } else if (typeof config.onError === "function") {
                                config.onError(data);
                            }
                        })
                        .catch((error) => {
                            console.error(
                                "Dashboard API error:",
                                config.url,
                                error,
                            );

                            if (typeof config.onError === "function") {
                                config.onError(error);
                            }
                        });
                });
            };

            // Mô phỏng .off().on() của jQuery để tránh gắn sự kiện click nhiều lần
            applyBtn.removeEventListener("click", applyBtn._dashboardHandler);

            applyBtn._dashboardHandler = handleFilterClick;

            applyBtn.addEventListener("click", applyBtn._dashboardHandler);

            // Tự động kích hoạt (Mô phỏng .trigger("click"))
            applyBtn.click();
        },
    };

    window.DashboardEngine = DashboardEngine;
})(window);
