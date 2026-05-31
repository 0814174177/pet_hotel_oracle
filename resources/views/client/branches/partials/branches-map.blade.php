<aside class="branch-map-panel">
    <div class="branch-map-header">
        <div>
            <span>Bản đồ</span>
            <h2>Vị trí chi nhánh</h2>
        </div>
        <strong data-branch-map-count>{{ $branches->count() }}</strong>
    </div>

    <div
        class="branch-map branch-map--leaflet"
        id="branchLeafletMap"
        data-branches='@json($branches->values())'
        aria-label="Bản đồ vị trí chi nhánh Pet Hotel"
    >
        <div class="map-loading" data-branch-map-placeholder>
            <i class="fa-regular fa-map"></i>
            <span>Đang tải bản đồ...</span>
        </div>
    </div>
</aside>
