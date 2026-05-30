<aside class="branch-map-panel">
    <div class="branch-map-header">
        <div>
            <span>Bản đồ</span>
            <h2>Vị trí chi nhánh</h2>
        </div>
        <strong data-branch-map-count>{{ $branches->count() }}</strong>
    </div>

    <div class="branch-map branch-map--leaflet" id="branchLeafletMap" data-branches='@json($branches->values())'>
        <div data-branch-map-markers>
            @forelse ($branches as $branch)
                <a
                    href="{{ $branch['detailUrl'] }}"
                    class="map-marker"
                    style="--marker-x: {{ $branch['map']['x'] }}%; --marker-y: {{ $branch['map']['y'] }}%;"
                    aria-label="{{ $branch['name'] }}"
                >
                    <span class="marker-label">{{ $branch['name'] }}</span>
                    <span class="marker-dot">{{ $loop->iteration }}</span>
                </a>
            @empty
                <div class="map-empty">
                    <i class="fa-regular fa-map"></i>
                    <span>Không có địa điểm phù hợp</span>
                </div>
            @endforelse
        </div>

        <div class="map-current">
            <i class="fa-solid fa-location-crosshairs"></i>
            <h3>TP.HCM</h3>
            <p>Pet Hotel</p>
        </div>
    </div>
</aside>
