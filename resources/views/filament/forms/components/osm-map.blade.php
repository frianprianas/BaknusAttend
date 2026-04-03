<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}" />

    <div
        x-data="{
            lat: @entangle('data.lat'),
            long: @entangle('data.long'),
            map: null,
            marker: null,
            searchQuery: '',
            searchResults: [],
            isSearching: false,

            initMap() {
                // Default ke tengah Indonesia jika belum diset
                let centerLat = this.lat || -0.789275;
                let centerLong = this.long || 113.921327;
                let zoomLevel = this.lat ? 17 : 5;

                this.map = L.map($refs.mapContainer).setView([centerLat, centerLong], zoomLevel);

                // Tambahkan tile openstreetmap (GRATIS)
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap BaknusAttend'
                }).addTo(this.map);

                if (this.lat && this.long) {
                    this.marker = L.marker([this.lat, this.long]).addTo(this.map);
                }

                // Event ketika peta diklik
                this.map.on('click', (e) => {
                    this.setMarker(e.latlng.lat, e.latlng.lng);
                });

                // Sinkronisasi bolak-balik kalau Admin ngetik manual di input form
                this.$watch('lat', value => {
                    if (value && this.long) {
                        this.updateMarkerOnly(value, this.long);
                    }
                });
                this.$watch('long', value => {
                    if (this.lat && value) {
                        this.updateMarkerOnly(this.lat, value);
                    }
                });
            },

            updateMarkerOnly(lat, lng) {
                if(!this.map) return;
                if(this.marker) this.map.removeLayer(this.marker);
                this.marker = L.marker([lat, lng]).addTo(this.map);
            },

            setMarker(lat, lng) {
                this.updateMarkerOnly(lat, lng);
                // Update nilai di form bawaan Filament (wire:model)
                this.lat = lat.toFixed(8);
                this.long = lng.toFixed(8);
            },

            async searchLocation() {
                if(!this.searchQuery) return;
                this.isSearching = true;
                
                try {
                    // Cari tempat pakai API Nominatim (Gratis bawaan OpenStreetMap)
                    let url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(this.searchQuery)}&format=json&limit=5`;
                    let res = await fetch(url);
                    this.searchResults = await res.json();
                } catch(e) {
                    console.error(e);
                }
                this.isSearching = false;
            },

            selectLocation(item) {
                let lat = parseFloat(item.lat);
                let lng = parseFloat(item.lon);
                
                this.setMarker(lat, lng);
                this.map.setView([lat, lng], 17); // Terbang ke lokasi dengan zoom dekat
                
                // Reset search
                this.searchResults = [];
                this.searchQuery = item.display_name;
            },

            loadLeaflet() {
                if (typeof L === 'undefined') {
                    const script = document.createElement('script');
                    script.src = '{{ asset('leaflet/leaflet.js') }}';
                    script.onload = () => this.initMap();
                    document.head.appendChild(script);
                } else {
                    this.initMap();
                }
            }
        }"
        x-init="loadLeaflet()"
        wire:ignore.self
        class="w-full relative rounded-xl overflow-hidden shadow-sm border border-gray-300 dark:border-gray-700"
    >
        <!-- Fitur Pencarian Terintegrasi -->
        <div class="absolute top-3 left-14 right-3 sm:left-16 sm:right-auto sm:w-80 z-[400] flex gap-2">
            <div class="relative w-full">
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    @keydown.enter.prevent="searchLocation" 
                    placeholder="Cari lokasi sekolah/gedung..." 
                    class="w-full px-4 py-2 pr-10 rounded-lg border-gray-300 dark:border-gray-600 shadow-lg text-sm text-gray-900 bg-white/95 backdrop-blur focus:ring-2 focus:ring-primary-500 outline-none transition-all"
                >
                <button type="button" @click="searchLocation" class="absolute right-2 top-1.5 p-1 text-gray-500 hover:text-primary-600 transition-colors">
                    <svg x-show="!isSearching" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <svg x-show="isSearching" style="display:none;" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </button>

                <!-- Daftar Hasil Pencarian (Autocomplete List) -->
                <ul x-show="searchResults.length > 0" @click.outside="searchResults = []" style="display:none;" class="absolute w-full mt-2 bg-white/95 backdrop-blur border border-gray-200 rounded-lg shadow-2xl max-h-60 overflow-y-auto text-xs text-gray-800 divide-y divide-gray-100 p-1">
                    <template x-for="result in searchResults">
                        <li @click="selectLocation(result)" class="px-3 py-2 hover:bg-primary-50 hover:text-primary-700 cursor-pointer rounded-md transition-colors leading-relaxed line-clamp-2" x-text="result.display_name"></li>
                    </template>
                </ul>
            </div>
        </div>
        
        <!-- Peta Kontainer -->
        <div wire:ignore x-ref="mapContainer" class="w-full h-[400px] z-10 bg-gray-100 dark:bg-gray-800"></div>

        <!-- Info Kordinat Floating -->
        <div class="absolute bottom-3 left-3 z-[400] bg-white/90 backdrop-blur px-3 py-1.5 rounded shadow-lg border border-gray-100 text-[11px] font-semibold text-gray-600 flex flex-col pointer-events-none">
            <span>Lat: <b class="text-primary-600" x-text="lat || '-'"></b></span>
            <span>Lng: <b class="text-primary-600" x-text="long || '-'"></b></span>
            <span class="text-[9px] font-normal italic mt-0.5 text-gray-400">*Bisa diklik untuk pindah marker</span>
        </div>
    </div>
    </div>
