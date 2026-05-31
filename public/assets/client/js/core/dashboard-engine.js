(function (window) {
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
                            }
                        })
                        .catch((error) => {
                            console.error(
                                "Dashboard API error:",
                                config.url,
                                error,
                            );
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
