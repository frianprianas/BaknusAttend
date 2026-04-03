<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}" />
<script src="{{ asset('leaflet/leaflet.js') }}"></script>

<style>
    #osm-map-container { position: relative; margin: 1rem 1.5rem 1.5rem; border-radius: 1rem; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1); border: 2px solid #e5e7eb; }
    #osm-map { height: 400px; width: 100%; z-index: 10; background: #f3f4f6; }
    #osm-search-box { position: absolute; top: 12px; left: 60px; right: 12px; z-index: 400; max-width: 340px; }
    #osm-search-input { width: 100%; padding: 8px 38px 8px 14px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 13px; box-shadow: 0 4px 12px rgba(0,0,0,.15); outline: none; background: rgba(255,255,255,.97); }
    #osm-search-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.2), 0 4px 12px rgba(0,0,0,.15); }
    #osm-search-btn { position: absolute; right: 6px; top: 5px; background: none; border: none; cursor: pointer; color: #6b7280; padding: 4px; }
    #osm-search-btn:hover { color: #6366f1; }
    #osm-results { position: absolute; width: 100%; margin-top: 6px; background: rgba(255,255,255,.97); border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,.15); max-height: 200px; overflow-y: auto; display: none; list-style: none; padding: 4px; }
    #osm-results li { padding: 8px 12px; font-size: 12px; cursor: pointer; border-radius: 6px; color: #374151; line-height: 1.4; }
    #osm-results li:hover { background: #eef2ff; color: #4338ca; }
    #osm-coords { position: absolute; bottom: 10px; left: 10px; z-index: 400; background: rgba(255,255,255,.92); backdrop-filter: blur(4px); padding: 6px 12px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,.1); font-size: 11px; font-weight: 600; color: #4b5563; pointer-events: none; }
    #osm-coords b { color: #6366f1; }
    .osm-label { display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 8px; padding-left: 4px; }
    .dark #osm-map-container { border-color: #374151; }
    .dark #osm-search-input { background: rgba(31,41,55,.97); color: #e5e7eb; border-color: #4b5563; }
    .dark #osm-results { background: rgba(31,41,55,.97); border-color: #4b5563; }
    .dark #osm-results li { color: #d1d5db; }
    .dark #osm-results li:hover { background: #312e81; color: #c7d2fe; }
    .dark #osm-coords { background: rgba(31,41,55,.92); color: #9ca3af; }
    .dark .osm-label { color: #d1d5db; }
</style>

<div style="padding: 0 0 1rem;">
    <span class="osm-label" style="padding-left: 1.5rem;">📍 Pilih Lokasi di Peta (klik/cari)</span>
    <div id="osm-map-container">
        <div id="osm-search-box">
            <input type="text" id="osm-search-input" placeholder="Cari lokasi sekolah/gedung...">
            <button type="button" id="osm-search-btn" onclick="osmSearch()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
            <ul id="osm-results"></ul>
        </div>
        <div id="osm-map"></div>
        <div id="osm-coords">
            Lat: <b id="osm-lat-display">-</b> &nbsp; Lng: <b id="osm-lng-display">-</b>
            <br><span style="font-size:9px;font-weight:normal;font-style:italic;color:#9ca3af;">*Klik peta untuk pindahkan pin</span>
        </div>
    </div>
</div>

<script>
(function() {
    var map, marker;

    function getLatInput() {
        return document.querySelector('input[wire\\:model\\.live\\.onblur="data.lat"], input[wire\\:model="data.lat"], input[id$="data.lat"]');
    }
    function getLongInput() {
        return document.querySelector('input[wire\\:model\\.live\\.onblur="data.long"], input[wire\\:model="data.long"], input[id$="data.long"]');
    }

    function setInputValue(input, value) {
        if (!input) return;
        var nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
        nativeInputValueSetter.call(input, value);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function readCurrentCoords() {
        var latEl = getLatInput();
        var lngEl = getLongInput();
        var lat = latEl ? parseFloat(latEl.value) : null;
        var lng = lngEl ? parseFloat(lngEl.value) : null;
        return { lat: (lat && !isNaN(lat)) ? lat : null, lng: (lng && !isNaN(lng)) ? lng : null };
    }

    function placeMarker(lat, lng) {
        if (marker) map.removeLayer(marker);
        L.Icon.Default.imagePath = '{{ asset("leaflet/images") }}/';
        marker = L.marker([lat, lng]).addTo(map);
        document.getElementById('osm-lat-display').textContent = lat.toFixed(8);
        document.getElementById('osm-lng-display').textContent = lng.toFixed(8);
        setInputValue(getLatInput(), lat.toFixed(8));
        setInputValue(getLongInput(), lng.toFixed(8));
    }

    function initOsmMap() {
        var coords = readCurrentCoords();
        var centerLat = coords.lat || -6.914744;
        var centerLng = coords.lng || 107.609810;
        var zoom = coords.lat ? 17 : 5;

        L.Icon.Default.imagePath = '{{ asset("leaflet/images") }}/';
        map = L.map('osm-map').setView([centerLat, centerLng], zoom);

        var streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        });

        var satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: '© Esri World Imagery'
        });

        var hybridLabels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: ''
        });

        var satelliteHybrid = L.layerGroup([satelliteLayer, hybridLabels]);

        streetLayer.addTo(map);

        L.control.layers({
            '🗺️ Peta Jalan': streetLayer,
            '🛰️ Satelit': satelliteHybrid
        }, null, { position: 'topright', collapsed: false }).addTo(map);

        if (coords.lat && coords.lng) {
            marker = L.marker([coords.lat, coords.lng]).addTo(map);
            document.getElementById('osm-lat-display').textContent = coords.lat.toFixed(8);
            document.getElementById('osm-lng-display').textContent = coords.lng.toFixed(8);
        }

        map.on('click', function(e) {
            placeMarker(e.latlng.lat, e.latlng.lng);
        });
    }

    window.osmSearch = function() {
        var q = document.getElementById('osm-search-input').value;
        if (!q) return;
        fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(q) + '&format=json&limit=5')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var ul = document.getElementById('osm-results');
                ul.innerHTML = '';
                if (data.length === 0) {
                    ul.style.display = 'none';
                    return;
                }
                data.forEach(function(item) {
                    var li = document.createElement('li');
                    li.textContent = item.display_name;
                    li.onclick = function() {
                        var lat = parseFloat(item.lat);
                        var lng = parseFloat(item.lon);
                        placeMarker(lat, lng);
                        map.setView([lat, lng], 17);
                        ul.style.display = 'none';
                        document.getElementById('osm-search-input').value = item.display_name;
                    };
                    ul.appendChild(li);
                });
                ul.style.display = 'block';
            });
    };

    document.getElementById('osm-search-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); window.osmSearch(); }
    });

    document.addEventListener('click', function(e) {
        var results = document.getElementById('osm-results');
        var searchBox = document.getElementById('osm-search-box');
        if (!searchBox.contains(e.target)) results.style.display = 'none';
    });

    // Inisiasi peta setelah DOM siap + sedikit delay agar input Filament sudah selesai render
    setTimeout(initOsmMap, 500);
})();
</script>
