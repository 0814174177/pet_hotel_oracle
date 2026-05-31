(function () {
    const form = document.querySelector("[data-branch-filter]");
    const keywordInput = document.querySelector("[data-branch-keyword]");
    const districtSelect = document.querySelector("[data-branch-district]");
    const resetButton = document.querySelector("[data-branch-reset]");
    const list = document.querySelector("[data-branch-list]");
    const feedback = document.querySelector("[data-branch-feedback]");
    const mapCount = document.querySelector("[data-branch-map-count]");
    const leafletMapEl = document.getElementById("branchLeafletMap");
    const mapPlaceholder = document.querySelector("[data-branch-map-placeholder]");

    if (!form || !keywordInput || !districtSelect || !list) {
        return;
    }

    const apiUrl = form.dataset.apiUrl;
    let debounceTimer = null;
    let latestRequestId = 0;
    let leafletMap = null;
    let leafletLayer = null;

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

    const branchCoordinates = function (branch) {
        const map = branch.map || {};
        const lat = Number(map.lat);
        const lng = Number(map.lng);

        if (Number.isFinite(lat) && Number.isFinite(lng)) {
            return [lat, lng];
        }

        return null;
    };

    const markerIcon = function (index) {
        return window.L.divIcon({
            className: "branch-map-pin",
            html: `<span>${index + 1}</span>`,
            iconSize: [34, 42],
            iconAnchor: [17, 42],
            popupAnchor: [0, -36],
        });
    };

    const setMapPlaceholder = function (message, isError) {
        if (!mapPlaceholder) {
            return;
        }

        mapPlaceholder.hidden = !message;
        mapPlaceholder.classList.toggle("is-error", Boolean(isError));
        mapPlaceholder.innerHTML = message
            ? `<i class="fa-regular fa-map"></i><span>${escapeHtml(message)}</span>`
            : "";
    };

    const initLeafletMap = function () {
        if (!leafletMapEl || leafletMap) {
            return Boolean(leafletMap);
        }

        if (!window.L) {
            leafletMapEl.classList.add("is-leaflet-unavailable");
            setMapPlaceholder("Không tải được thư viện bản đồ. Vui lòng kiểm tra kết nối mạng.", true);
            return false;
        }

        leafletMap = window.L.map(leafletMapEl, {
            scrollWheelZoom: false,
            zoomControl: true,
        }).setView([10.7769, 106.7009], 11);

        window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap contributors",
        }).addTo(leafletMap);

        leafletLayer = window.L.layerGroup().addTo(leafletMap);
        leafletMapEl.classList.add("is-leaflet-ready");
        setMapPlaceholder("", false);

        window.setTimeout(function () {
            leafletMap.invalidateSize();
        }, 80);

        return true;
    };

    const renderLeafletMap = function (branches) {
        if (!initLeafletMap()) {
            return;
        }

        leafletLayer.clearLayers();

        const bounds = [];

        branches.forEach(function (branch, index) {
            const coordinates = branchCoordinates(branch);

            if (!coordinates) {
                return;
            }

            bounds.push(coordinates);

            window.L.marker(coordinates, {
                icon: markerIcon(index),
                title: branch.name || "",
            })
                .addTo(leafletLayer)
                .bindPopup(`
                    <div class="branch-leaflet-popup">
                        <strong>${escapeHtml(branch.name)}</strong>
                        <span>${escapeHtml(branch.address)}</span>
                        <span>${escapeHtml(branch.phone)}</span>
                        <a href="${escapeHtml(branch.detail_url)}">Xem chi tiết</a>
                    </div>
                `);
        });

        if (!bounds.length) {
            setMapPlaceholder("Không có địa điểm phù hợp", false);
            leafletMap.setView([10.7769, 106.7009], 11);
            return;
        }

        setMapPlaceholder("", false);

        if (bounds.length === 1) {
            leafletMap.setView(bounds[0], 14);
        } else {
            leafletMap.fitBounds(bounds, { padding: [34, 34], maxZoom: 14 });
        }
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

        renderLeafletMap(branches);
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

    const initialBranches = (function () {
        if (!leafletMapEl) {
            return [];
        }

        try {
            return JSON.parse(leafletMapEl.dataset.branches || "[]");
        } catch (error) {
            return [];
        }
    }());

    renderMap(initialBranches);
}());
