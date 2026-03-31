<x-filament-widgets::widget>
    <x-filament::section>
        <!-- VERSION: 2.1.0-FIX-CACHE-BUST -->
        <style>
            .fi-fo-file-upload-dropzone-label { display: none !important; }
            .filepond--root:not(.filepond--has-file) {
                background-color: #e0e7ff !important;
                border: 2px dashed #4f46e5 !important;
                border-radius: 1rem !important;
                cursor: pointer !important;
                min-height: 180px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            .filepond--root:not(.filepond--has-file)::before {
                content: "📷";
                font-size: 4rem;
                display: block;
                margin-bottom: 0.5rem;
            }
            .filepond--root:not(.filepond--has-file)::after {
                content: "KLIK DI SINI UNTUK FOTO";
                font-weight: 900;
                font-size: 1.15rem;
                color: #4f46e5;
            }
            /* TOMBOL RAKSASA */
            .tombol-gas-absen {
                background: #4f46e5 !important;
                color: white !important;
                padding: 20px !important;
                border-radius: 15px !important;
                width: 100% !important;
                font-weight: 900 !important;
                font-size: 1.25rem !important;
                text-align: center !important;
                display: block !important;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
                cursor: pointer !important;
                border: none !important;
                transition: transform 0.2s;
            }
            .tombol-gas-absen:hover { transform: scale(1.02); background: #4338ca !important; }
            .tombol-gas-absen:disabled { background: #9ca3af !important; cursor: not-allowed; transform: none; }
        </style>

        <div x-data="presensiWidgetFinal()" class="flex flex-col items-center justify-center p-4">
            
            <!-- User Header -->
            <div class="w-full max-w-3xl mb-8 p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-6">
                    <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900 rounded-2xl flex items-center justify-center overflow-hidden">
                        @if($userAvatar)
                            <img src="{{ $userAvatar }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-3xl font-black text-indigo-600">{{ strtoupper(substr($userName ?? '?', 0, 1)) }}</span>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-gray-800 dark:text-white">{{ $userName }}</h3>
                        <p class="text-sm text-gray-500 font-bold tracking-tight">{{ $userEmail }} @if($userClass) | {{ $userClass }} @endif</p>
                    </div>
                </div>
            </div>

            <h2 class="text-xl font-black text-indigo-600 dark:text-indigo-400 mb-6 uppercase tracking-widest text-center">Presensi {{ $tipeAbsens }}</h2>

            <form @submit.prevent="validateFaceAndSubmit" class="w-full max-w-2xl relative">
                @if($tipeAbsens === 'Selesai')
                    <div class="p-10 bg-green-50 dark:bg-green-900/20 border-2 border-dashed border-green-500 rounded-3xl text-center">
                        <h3 class="text-2xl font-black text-green-700 uppercase">Presensi Selesai! ✅</h3>
                    </div>
                @else
                    <!-- Status Overlays -->
                    <div x-show="isScanningFace" style="display: none;" class="absolute inset-0 z-50 flex items-center justify-center bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm rounded-xl border-4 border-orange-500 text-center p-6">
                        <div>
                            <div class="animate-spin h-10 w-10 border-4 border-orange-500 border-t-transparent rounded-full mx-auto mb-4"></div>
                            <h3 class="text-xl font-black text-orange-600 uppercase">Mengecek Wajah di HP...</h3>
                            <p class="text-sm font-bold text-gray-500 italic mt-2">Pastikan wajah bapak/ibu terlihat jelas di kamera.</p>
                        </div>
                    </div>

                    <div wire:loading wire:target="submit" class="absolute inset-0 z-[51] flex items-center justify-center bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm rounded-xl border-4 border-indigo-500 text-center p-6">
                        <div>
                            <div class="animate-spin h-10 w-10 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto mb-4"></div>
                            <h3 class="text-xl font-black text-indigo-600 uppercase">Verifikasi ke Server...</h3>
                            <p class="text-sm font-bold text-gray-500 italic mt-2">Sabar bapak, sedang mencocokkan identitas.</p>
                        </div>
                    </div>

                    {{ $this->form }}

                    <div class="mt-8">
                        <button type="submit" 
                            wire:loading.attr="disabled" 
                            x-bind:disabled="!$wire.data.lat || isSearching || isScanningFace" 
                            class="tombol-gas-absen">
                            <span wire:loading.remove wire:target="submit">🔥 GASPOL PRESENSI {{ strtoupper($tipeAbsens) }} SEKARANG</span>
                            <span wire:loading wire:target="submit" class="animate-pulse">MEMPROSES...</span>
                        </button>
                    </div>

                    <!-- GPS Status -->
                    <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800/20 rounded-xl text-center">
                        <p x-html="statusText" :class="statusClass" class="text-xs font-bold"></p>
                    </div>
                @endif
            </form>
        </div>

        <script>
            function presensiWidgetFinal() {
                return {
                    statusText: '', statusClass: '', showRetry: false, isSearching: false, isScanningFace: false, faceApiLoaded: false,
                    init() {
                        this.getGPS();
                        this.loadFaceApi();
                        setInterval(() => { if (!this.$wire.data.lat) this.getGPS(); }, 60000);
                    },
                    loadFaceApi() {
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js';
                        script.onload = async () => {
                            await faceapi.nets.tinyFaceDetector.loadFromUri('https://vladmandic.github.io/face-api/model/');
                            this.faceApiLoaded = true;
                        };
                        document.head.appendChild(script);
                    },
                    getGPS() {
                        if (!navigator.geolocation) { this.statusText = '❌ GPS TIDAK DIDUKUNG'; return; }
                        this.statusText = '🛰️ MENCARI LOKASI...'; this.isSearching = true;
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                const lat = pos.coords.latitude; const lon = pos.coords.longitude;
                                this.$wire.set('data.lat', lat); this.$wire.set('data.long', lon);
                                this.statusText = '✅ POSISI TERKUNCI [' + lat.toFixed(4) + ']';
                                this.statusClass = 'text-green-600'; this.isSearching = false;
                            },
                            (err) => { this.isSearching = false; this.statusText = '❌ GPS GAGAL: ' + err.message; this.statusClass = 'text-red-500'; },
                            { enableHighAccuracy: true, timeout: 15000 }
                        );
                    },
                    async validateFaceAndSubmit() {
                        const img = document.querySelector('.filepond--item canvas') || document.querySelector('.filepond--image-preview img');
                        if (!img) { this.$wire.submit(); return; }
                        this.isScanningFace = true;
                        if (this.faceApiLoaded && window.faceapi) {
                            try {
                                const det = await faceapi.detectAllFaces(img, new faceapi.TinyFaceDetectorOptions());
                                this.isScanningFace = false;
                                if (det.length !== 0) { this.$wire.submit(); } 
                                else { alert('🛑 FOTO DITOLAK: AI HP bapak tidak melihat wajah di foto ini. Coba foto ulang bapak.'); }
                                return;
                            } catch (e) { console.error(\"AI API Error\", e); }
                        }
                        this.isScanningFace = false;
                        this.$wire.submit();
                    }
                }
            }
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
