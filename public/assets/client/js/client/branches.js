(function () {
    const form = document.querySelector("[data-branch-filter]");
    const keywordInput = document.querySelector("[data-branch-keyword]");
    const districtSelect = document.querySelector("[data-branch-district]");
    const resetButton = document.querySelector("[data-branch-reset]");
    const list = document.querySelector("[data-branch-list]");
    const feedback = document.querySelector("[data-branch-feedback]");
    const mapCount = document.querySelector("[data-branch-map-count]");
    const mapMarkers = document.querySelector("[data-branch-map-markers]");
    const leafletMapEl = document.getElementById("branchLeafletMap");

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

    const initLeafletMap = function () {
        if (!leafletMapEl || !window.L || leafletMap) {
            return;
        }

        leafletMap = window.L.map(leafletMapEl, {
            scrollWheelZoom: false,
        }).setView([10.7769, 106.7009], 11);

        window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 18,
            attribution: "&copy; OpenStreetMap contributors",
        }).addTo(leafletMap);

        leafletLayer = window.L.layerGroup().addTo(leafletMap);
        leafletMapEl.classList.add("is-leaflet-ready");
    };

    const renderLeafletMap = function (branches) {
        initLeafletMap();

        if (!leafletMap || !leafletLayer || !window.L) {
            return;
        }

        leafletLayer.clearLayers();

        const bounds = [];

        branches.forEach(function (branch) {
            const coordinates = branchCoordinates(branch);

            if (!coordinates) {
                return;
            }

            bounds.push(coordinates);

            window.L.marker(coordinates)
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

        if (bounds.length === 1) {
            leafletMap.setView(bounds[0], 14);
        } else if (bounds.length > 1) {
            leafletMap.fitBounds(bounds, { padding: [28, 28] });
        } else {
            leafletMap.setView([10.7769, 106.7009], 11);
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

        if (!mapMarkers) {
            renderLeafletMap(branches);
            return;
        }

        if (!branches.length) {
            mapMarkers.innerHTML = `
                <div class="map-empty">
                    <i class="fa-regular fa-map"></i>
                    <span>Không có địa điểm phù hợp</span>
                </div>
            `;
            renderLeafletMap(branches);
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

    const initialBranches = (() => {
        if (!leafletMapEl) {
            return [];
        }

        try {
            return JSON.parse(leafletMapEl.dataset.branches || "[]");
        } catch (error) {
            return [];
        }
    })();

    renderLeafletMap(initialBranches);
}());
