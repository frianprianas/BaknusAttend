<x-filament-widgets::widget>
    <x-filament::section>
        <!-- VERSION: 2.1.3-PRODUCTION-HTTPS -->
        <style>
            .fi-fo-file-upload-dropzone-label { display: none !important; }
            .filepond--root:not(.filepond--has-file) {
                background-color: #e0e7ff !important;
                border: 2.2px dashed #4f46e5 !important;
                border-radius: 1.5rem !important;
                min-height: 180px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            .filepond--root:not(.filepond--has-file)::before {
                content: "📷";
                font-size: 4.5rem;
                display: block;
                margin-bottom: 0.5rem;
            }
            .filepond--root:not(.filepond--has-file)::after {
                content: "KLIK UNTUK MULAI KAMERA";
                font-weight: 900;
                font-size: 1.15rem;
                color: #4f46e5;
                text-transform: uppercase;
            }
            .tombol-gas-absen {
                background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%) !important;
                color: white !important;
                padding: 24px !important;
                border-radius: 18px !important;
                width: 100% !important;
                font-weight: 900 !important;
                font-size: 1.3rem !important;
                text-align: center !important;
                display: block !important;
                box-shadow: 0 12px 24px -6px rgba(79, 70, 229, 0.4) !important;
                cursor: pointer !important;
                border: none !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .tombol-gas-absen:hover { transform: translateY(-4px); box-shadow: 0 15px 30px -8px rgba(79, 70, 229, 0.5) !important; }
            .tombol-gas-absen:disabled { background: #9ca3af !important; cursor: not-allowed; transform: none; box-shadow: none !important; }
        </style>

        <div x-data="mesinAbsenBaknusFinalV7()" class="flex flex-col items-center justify-center p-4">
            
            <!-- User Header -->
            <div class="w-full max-w-3xl mb-10 p-6 bg-white dark:bg-gray-800/50 backdrop-blur-xl rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-8">
                    <div class="w-20 h-20 bg-indigo-100 dark:bg-indigo-900 rounded-2xl flex items-center justify-center overflow-hidden border-4 border-white dark:border-gray-800 shadow-lg">
                        @if($userAvatar)
                            <img src="{{ $userAvatar }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-4xl font-black text-indigo-600">{{ strtoupper(substr($userName ?? '?', 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h3 class="text-3xl font-black text-gray-800 dark:text-white leading-tight mb-1">{{ $userName }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-bold tracking-tight bg-gray-100 dark:bg-gray-700 inline-block px-3 py-1 rounded-lg border border-gray-200 dark:border-gray-600">
                            {{ $userEmail }} @if($userClass) • {{ $userClass }} @endif
                        </p>
                    </div>
                </div>
            </div>

            <h2 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 mb-8 uppercase tracking-widest text-center italic">Presensi {{ $tipeAbsens }}</h2>

            <form @submit.prevent="validateFaceAndSubmit" class="w-full max-w-2xl relative">
                @if($tipeAbsens === 'Selesai')
                    <div class="p-12 bg-green-50 dark:bg-green-900/20 border-4 border-dashed border-green-500 rounded-[2.5rem] text-center shadow-2xl animate-in zoom-in duration-500">
                        <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-green-500/50 text-4xl">✅</div>
                        <h3 class="text-3xl font-black text-green-700 dark:text-green-400 uppercase tracking-tight">Presensi Selesai!</h3>
                        <p class="text-gray-600 dark:text-gray-300 mt-3 font-bold text-lg">Terima kasih atas dedikasi Anda hari ini.</p>
                    </div>
                @else
                    <!-- Local AI Scanning Overlay -->
                    <div x-show="isScanningFace" style="display: none;" class="absolute inset-0 z-50 flex items-center justify-center bg-white/95 dark:bg-gray-900/95 backdrop-blur-md rounded-3xl border-4 border-orange-500 text-center p-8">
                        <div>
                            <div class="relative w-24 h-24 mx-auto mb-6">
                                <div class="absolute inset-0 border-4 border-orange-200 rounded-full"></div>
                                <div class="absolute inset-0 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
                            </div>
                            <h3 class="text-2xl font-black text-orange-600 dark:text-orange-400 uppercase tracking-tight">Mengecek Wajah Anda...</h3>
                            <p class="text-sm font-bold text-gray-500 dark:text-gray-400 mt-3">Sabar bapak, sedang memastikan foto wajah jelas di HP Anda agar Server tidak boros Token.</p>
                        </div>
                    </div>

                    <!-- Server AI Loading Overlay -->
                    <div wire:loading wire:target="submit" class="absolute inset-0 z-[51] flex items-center justify-center bg-white/95 dark:bg-gray-900/95 backdrop-blur-md rounded-3xl border-4 border-indigo-600 text-center p-8">
                        <div>
                            <div class="relative w-24 h-24 mx-auto mb-6">
                                <div class="absolute inset-0 border-4 border-indigo-200 rounded-full"></div>
                                <div class="absolute inset-0 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                            </div>
                            <h3 class="text-2xl font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-tight">Sync ke Server Pusat...</h3>
                            <p class="text-sm font-bold text-gray-500 dark:text-gray-400 mt-3 italic">Mencocokkan identitas Anda dengan Database BaknusAI.</p>
                        </div>
                    </div>

                    {{ $this->form }}

                    <div class="mt-10">
                        <button type="submit" 
                            wire:loading.attr="disabled" 
                            x-bind:disabled="!$wire.data.lat || isSearching || isScanningFace" 
                            class="tombol-gas-absen group">
                            <span wire:loading.remove wire:target="submit" class="flex items-center justify-center gap-3 group-hover:scale-105 transform transition duration-300">
                                🚀 GASPOL KIRIM ABSENSI {{ strtoupper($tipeAbsens) }}
                            </span>
                            <span wire:loading wire:target="submit" class="animate-pulse">MEMPROSES DATA...</span>
                        </button>
                    </div>

                    <!-- GPS Status Display -->
                    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800/40 rounded-2xl border border-gray-100 dark:border-gray-700 text-center">
                        <p x-html="statusText" :class="statusClass" class="text-xs font-black uppercase tracking-tighter"></p>
                    </div>
                @endif
            </form>
        </div>

        <script>
            // Paksa nama unik agar cache tidak bisa berkutik
            function mesinAbsenBaknusFinalV7() {
                return {
                    statusText: '', statusClass: '', isSearching: false, isScanningFace: false, faceApiLoaded: false,
                    init() {
                        this.getGPS();
                        this.loadFaceApi();
                        setInterval(() => { if (!this.$wire.data.lat) this.getGPS(); }, 60000);
                    },
                    loadFaceApi() {
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js';
                        script.onload = async () => {
                            try {
                                await faceapi.nets.tinyFaceDetector.loadFromUri('https://vladmandic.github.io/face-api/model/');
                                this.faceApiLoaded = true;
                            } catch (e) {
                                console.error("Model Load Error", e);
                            }
                        };
                        document.head.appendChild(script);
                    },
                    getGPS() {
                        if (!navigator.geolocation) { this.statusText = '❌ GPS TIDAK DIDUKUNG'; return; }
                        this.statusText = '🛰️ MENCARI TITK GPS...'; this.isSearching = true;
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                const lat = pos.coords.latitude; const lon = pos.coords.longitude;
                                this.$wire.set('data.lat', lat); this.$wire.set('data.long', lon);
                                this.statusText = '✅ LOKASI TERKUNCI [' + lat.toFixed(5) + ']';
                                this.statusClass = 'text-green-600 dark:text-green-400 font-black';
                                this.isSearching = false;
                            },
                            (err) => { 
                                this.isSearching = false; 
                                this.statusText = '❌ GPS GAGAL: HARAP AKTIFKAN LOKASI'; 
                                this.statusClass = 'text-red-500 font-black'; 
                            },
                            { enableHighAccuracy: true, timeout: 15000 }
                        );
                    },
                    async validateFaceAndSubmit() {
                        const img = document.querySelector('.filepond--item canvas') || document.querySelector('.filepond--image-preview img');
                        if (!img) { this.$wire.submit(); return; }
                        
                        this.isScanningFace = true;
                        
                        if (this.faceApiLoaded && window.faceapi) {
                            try {
                                const det = await faceapi.detectAllFaces(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 }));
                                this.isScanningFace = false;
                                if (det.length !== 0) { 
                                    this.$wire.submit(); 
                                } else { 
                                    alert('🛑 STOP! AI HP bapak tidak melihat wajah di foto ini bapak. Harap foto wajah jelas menghadap kamera agar Token Server tidak hangus percuma.');
                                }
                                return;
                            } catch (e) { console.error("Local Scan Error", e); }
                        }
                        
                        // Fallback jika scrip macet
                        this.isScanningFace = false;
                        this.$wire.submit();
                    }
                }
            }
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
