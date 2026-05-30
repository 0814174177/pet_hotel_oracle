(function () {
    const form = document.querySelector("[data-branch-filter]");
    const keywordInput = document.querySelector("[data-branch-keyword]");
    const districtSelect = document.querySelector("[data-branch-district]");
    const resetButton = document.querySelector("[data-branch-reset]");
    const list = document.querySelector("[data-branch-list]");
    const feedback = document.querySelector("[data-branch-feedback]");
    const mapCount = document.querySelector("[data-branch-map-count]");
    const mapMarkers = document.querySelector("[data-branch-map-markers]");

    if (!form || !keywordInput || !districtSelect || !list) {
        return;
    }

    const apiUrl = form.dataset.apiUrl;
    let debounceTimer = null;
    let latestRequestId = 0;

    const escapeHtml = function (value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    const setFeedback = function (message, type) {
        if (!feedback) {
            return;
        }

        feedback.textContent = message || "";
        feedback.className = "branch-feedback";
        if (type) {
            feedback.classList.add("is-" + type);
        }
        feedback.hidden = !message;
    };

    const setLoading = function (isLoading) {
        list.classList.toggle("is-loading", isLoading);
        setFeedback(isLoading ? "Đang tải danh sách chi nhánh..." : "", "loading");
    };

    const branchHours = function (branch) {
        if (branch.hours) {
            return branch.hours;
        }

        return [branch.open_time, branch.close_time].filter(Boolean).join(" - ") || "Đang cập nhật";
    };

    const renderBranches = function (branches) {
        if (!branches.length) {
            list.innerHTML = `
                <div class="branch-empty">
                    <i class="fa-regular fa-map"></i>
                    <h3>Không tìm thấy chi nhánh phù hợp</h3>
                    <p>Hãy thử đổi từ khóa hoặc chọn khu vực khác.</p>
                </div>
            `;
            return;
        }

        list.innerHTML = branches.map(function (branch) {
            return `
                <article class="branch-card">
                    <img src="${escapeHtml(branch.image_url)}" alt="${escapeHtml(branch.name)}">

                    <div class="branch-info">
                        <div class="branch-card-heading">
                            <h3>${escapeHtml(branch.name)}</h3>
                            <span>${escapeHtml(branch.district)}</span>
                        </div>

                        <p><i class="fa-solid fa-location-dot"></i> ${escapeHtml(branch.address)}</p>
                        <p><i class="fa-solid fa-phone"></i> ${escapeHtml(branch.phone)}</p>
                        <p><i class="fa-regular fa-clock"></i> ${escapeHtml(branchHours(branch))}</p>
                        <p class="rating">
                            <i class="fa-solid fa-star"></i> ${escapeHtml(branch.rating)}
                            <span>(${escapeHtml(branch.review_count)} đánh giá)</span>
                        </p>
                    </div>

                    <div class="branch-actions">
                        <a href="${escapeHtml(branch.booking_url)}" class="branch-booking-btn">Đặt phòng</a>
                        <a href="${escapeHtml(branch.detail_url)}" class="branch-detail-btn">Xem chi tiết</a>
                    </div>
                </article>
            `;
        }).join("");
    };

    const renderMap = function (branches) {
        if (mapCount) {
            mapCount.textContent = branches.length;
        }

        if (!mapMarkers) {
            return;
        }

        if (!branches.length) {
            mapMarkers.innerHTML = `
                <div class="map-empty">
                    <i class="fa-regular fa-map"></i>
                    <span>Không có địa điểm phù hợp</span>
                </div>
            `;
            return;
        }

        mapMarkers.innerHTML = branches.map(function (branch, index) {
            const map = branch.map || {};
            const x = Number.isFinite(Number(map.x)) ? Number(map.x) : 50;
            const y = Number.isFinite(Number(map.y)) ? Number(map.y) : 50;

            return `
                <a
                    href="${escapeHtml(branch.detail_url)}"
                    class="map-marker"
                    style="--marker-x: ${x}%; --marker-y: ${y}%;"
                    aria-label="${escapeHtml(branch.name)}"
                >
                    <span class="marker-label">${escapeHtml(branch.name)}</span>
                    <span class="marker-dot">${index + 1}</span>
                </a>
            `;
        }).join("");
    };

    const fetchBranches = function () {
        if (!apiUrl) {
            return;
        }

        const params = new URLSearchParams();
        const keyword = keywordInput.value.trim();
        const district = districtSelect.value || "all";

        if (keyword) {
            params.set("keyword", keyword);
        }
        if (district && district !== "all") {
            params.set("district", district);
        }

        const requestId = ++latestRequestId;
        setLoading(true);

        fetch(`${apiUrl}?${params.toString()}`, {
            headers: {
                Accept: "application/json",
            },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("Request failed");
                }
                return response.json();
            })
            .then(function (payload) {
                if (requestId !== latestRequestId) {
                    return;
                }

                const branches = Array.isArray(payload.data) ? payload.data : [];
                renderBranches(branches);
                renderMap(branches);
                setFeedback("", "");
            })
            .catch(function () {
                if (requestId !== latestRequestId) {
                    return;
                }

                setFeedback("Không thể tải danh sách chi nhánh. Vui lòng thử lại sau.", "error");
            })
            .finally(function () {
                if (requestId === latestRequestId) {
                    setLoading(false);
                }
            });
    };

    const scheduleFetch = function () {
        // Debounce input so typing does not call the API on every keystroke.
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(fetchBranches, 400);
    };

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        fetchBranches();
    });

    keywordInput.addEventListener("input", scheduleFetch);

    districtSelect.addEventListener("change", function () {
        window.clearTimeout(debounceTimer);
        fetchBranches();
    });

    if (resetButton) {
        resetButton.addEventListener("click", function () {
            keywordInput.value = "";
            districtSelect.value = "all";
            window.clearTimeout(debounceTimer);
            fetchBranches();
        });
    }
}());
