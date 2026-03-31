<x-filament-widgets::widget>
    <x-filament::section class="fi-fo-transparent-section">
        <!-- VERSION: 2.2.0-STABLE-FORMAL -->
        <style>
            /* Reset & Clean UI */
            .fi-fo-transparent-section {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .fi-fo-file-upload-dropzone-label { display: none !important; }
            .filepond--root:not(.filepond--has-file) {
                background-color: #f1f5f9 !important;
                border: 2px dashed #94a3b8 !important;
                border-radius: 1rem !important;
                min-height: 160px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }
            .filepond--root:not(.filepond--has-file):hover {
                border-color: #4f46e5 !important;
                background-color: #eef2ff !important;
            }
            .filepond--root:not(.filepond--has-file)::before {
                content: "📷";
                font-size: 3.5rem;
                display: block;
                margin-bottom: 0.5rem;
                opacity: 0.7;
            }
            .filepond--root:not(.filepond--has-file)::after {
                content: "KLIK UNTUK MULAI KAMERA";
                font-weight: 800;
                font-size: 1rem;
                color: #475569;
                letter-spacing: 0.05em;
            }
            /* Dark Mode Adjustments */
            .dark .filepond--root:not(.filepond--has-file) {
                background-color: rgba(30, 41, 59, 0.5) !important;
                border-color: #334155 !important;
            }
            .dark .filepond--root:not(.filepond--has-file)::after {
                color: #94a3b8;
            }

            /* FORMAL BUTTON */
            .tombol-presensi-formal {
                background: #4f46e5 !important;
                color: white !important;
                padding: 18px !important;
                border-radius: 12px !important;
                width: 100% !important;
                font-weight: 800 !important;
                font-size: 1.1rem !important;
                text-align: center !important;
                display: block !important;
                cursor: pointer !important;
                border: none !important;
                transition: all 0.2s ease-in-out;
                text-transform: uppercase;
                letter-spacing: 0.02em;
            }
            .tombol-presensi-formal:hover { background: #4338ca !important; }
            .tombol-presensi-formal:active { transform: scale(0.98); }
            .tombol-presensi-formal:disabled { 
                background: #94a3b8 !important; 
                opacity: 0.6;
                cursor: not-allowed; 
            }
        </style>

        <div x-data="mesinAbsenFormalV10()" class="flex flex-col items-center justify-center">
            
            <!-- Minimalist User Header -->
            <div class="w-full max-w-2xl mb-8 p-6 bg-white/5 dark:bg-gray-800/20 backdrop-blur-sm rounded-2xl border border-white/10 dark:border-gray-700/30">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center overflow-hidden border border-gray-200 dark:border-gray-600">
                        @if($userAvatar)
                            <img src="{{ $userAvatar }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-3xl font-black text-gray-400">{{ strtoupper(substr($userName ?? '?', 0, 1)) }}</span>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white leading-tight">{{ $userName }}</h3>
                        <p class="text-xs text-gray-500 font-medium tracking-tight mt-1">{{ $userEmail }} @if($userClass) | {{ $userClass }} @endif</p>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-2xl text-center mb-6">
                <h2 class="text-lg font-extrabold text-gray-700 dark:text-gray-300 uppercase tracking-widest">PRESENSI {{ strtoupper($tipeAbsens) }}</h2>
                <div class="h-1 w-12 bg-indigo-500 mx-auto mt-2 rounded-full"></div>
            </div>

            <form @submit.prevent="validateAndSubmit" class="w-full max-w-2xl relative">
                @if($tipeAbsens === 'Selesai')
                    <div class="p-8 bg-green-50 dark:bg-green-900/10 border-2 border-dashed border-green-500/30 rounded-2xl text-center">
                        <h3 class="text-xl font-bold text-green-600 uppercase tracking-tight">Presensi Hari Ini Lengkap</h3>
                    </div>
                @else
                    <!-- Overlays -->
                    <div x-show="isBusy" style="display: none;" class="absolute inset-0 z-50 flex items-center justify-center bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm rounded-2xl border border-indigo-500/30">
                        <div class="text-center">
                            <div class="animate-spin h-8 w-8 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto mb-3"></div>
                            <h3 class="text-sm font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-tight" x-text="busyText"></h3>
                        </div>
                    </div>

                    {{ $this->form }}

                    <div class="mt-8">
                        <button type="submit" 
                            wire:loading.attr="disabled" 
                            x-bind:disabled="isBusy" 
                            class="tombol-presensi-formal">
                            <span wire:loading.remove wire:target="submit">KIRIM PRESENSI {{ strtoupper($tipeAbsens) }}</span>
                            <span wire:loading wire:target="submit">MEMPROSES...</span>
                        </button>
                    </div>

                    <!-- Location Status Indicator -->
                    <div class="mt-4 flex items-center justify-center gap-2">
                        <div :class="gpsLocked ? 'bg-green-500' : 'bg-gray-400'" class="w-2 h-2 rounded-full"></div>
                        <p x-html="statusText" class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-tighter"></p>
                    </div>
                @endif
            </form>
        </div>

        <script>
            function mesinAbsenFormalV10() {
                return {
                    statusText: 'Mencari Lokasi GPS...',
                    isBusy: false,
                    busyText: '',
                    gpsLocked: false,
                    faceApiLoaded: false,
                    
                    init() {
                        this.getGPS();
                        this.loadFaceApi();
                        // Refresh GPS setiap 45 detik agar data tidak basi
                        setInterval(() => { this.getGPS(false); }, 45000);
                    },

                    loadFaceApi() {
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js';
                        script.onload = async () => {
                            try {
                                await faceapi.nets.tinyFaceDetector.loadFromUri('https://vladmandic.github.io/face-api/model/');
                                this.faceApiLoaded = true;
                            } catch (e) { console.error("Model Error", e); }
                        };
                        document.head.appendChild(script);
                    },
                    
                    getGPS(showStatus = true) {
                        if (!navigator.geolocation) { 
                            this.statusText = '❌ Perangkat tidak mendukung GPS'; 
                            return; 
                        }
                        
                        if (showStatus) {
                            this.statusText = '🛰️ MELACAK LOKASI...';
                        }

                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                const lat = pos.coords.latitude;
                                const lon = pos.coords.longitude;
                                this.$wire.set('data.lat', lat);
                                this.$wire.set('data.long', lon);
                                this.gpsLocked = true;
                                this.statusText = '✅ GPS TERKUNCI (Sinyal Kuat)';
                            },
                            (err) => { 
                                this.gpsLocked = false;
                                this.statusText = '❌ GAGAL MENDAPAT LOKASI (Harap Beri Izin)';
                            },
                            { enableHighAccuracy: true, timeout: 10000 }
                        );
                    },

                    async validateAndSubmit() {
                        // Jika GPS hilang, coba tarik lagi sebentar sebelum lapor robot
                        if (!this.gpsLocked) {
                            this.isBusy = true;
                            this.busyText = 'Mengambil Ulang Lokasi...';
                            await new Promise(r => {
                                navigator.geolocation.getCurrentPosition(
                                    (pos) => {
                                        this.$wire.set('data.lat', pos.coords.latitude);
                                        this.$wire.set('data.long', pos.coords.longitude);
                                        this.gpsLocked = true;
                                        r();
                                    },
                                    () => { r(); }, // Lanjut saja, biar divalidasi backend nanti
                                    { enableHighAccuracy: true, timeout: 5000 }
                                );
                            });
                        }

                        const img = document.querySelector('.filepond--item canvas') || document.querySelector('.filepond--image-preview img');
                        
                        // Jika tidak ada foto, biarkan Livewire handle validation (Required)
                        if (!img) { this.$wire.submit(); return; }

                        this.isBusy = true;
                        this.busyText = 'Memeriksa Wajah...';

                        if (this.faceApiLoaded && window.faceapi) {
                            try {
                                const det = await faceapi.detectAllFaces(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.35 }));
                                if (det.length === 0) { 
                                    this.isBusy = false;
                                    alert('🛑 STOP: Foto Anda tidak terdeteksi wajah. Pastikan wajah terlihat jelas dan menghadap kamera.');
                                    return;
                                }
                            } catch (e) { console.error("AI Error", e); }
                        }
                        
                        this.busyText = 'Mengirim Presensi...';
                        this.$wire.submit();
                    }
                }
            }
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
