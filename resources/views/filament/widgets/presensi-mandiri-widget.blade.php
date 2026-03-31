<x-filament-widgets::widget>
    <!-- PRODUCTION FINAL: 2.3.0-STABLE-FORCE-HTTPS -->
    <script>
        // Masukkan mesin ke laci global agar tidak "hilang" oleh sistem Livewire
        window.mesinAbsenFormalFixV15 = function() {
            return {
                statusText: 'Mengecek GPS...', statusClass: 'text-gray-400', isBusy: false, busyText: '', gpsLocked: false, faceApiLoaded: false,
                init() {
                    this.getGPS();
                    this.loadFaceApi();
                    setInterval(() => { if(!this.gpsLocked) this.getGPS(false); }, 45000);
                },
                loadFaceApi() {
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js';
                    s.onload = async () => {
                        try {
                            await faceapi.nets.tinyFaceDetector.loadFromUri('https://vladmandic.github.io/face-api/model/');
                            this.faceApiLoaded = true;
                        } catch(e) { console.error("Face model load err", e); }
                    };
                    document.head.appendChild(s);
                },
                getGPS(showStatus = true) {
                    if (!navigator.geolocation) { this.statusText = '❌ PERANGKAT TIDAK MENDUKUNG GPS'; return; }
                    if (showStatus) this.statusText = '🛰️ MELACAK GPS...';
                    navigator.geolocation.getCurrentPosition(
                        (p) => {
                            this.$wire.set('data.lat', p.coords.latitude);
                            this.$wire.set('data.long', p.coords.longitude);
                            this.gpsLocked = true;
                            this.statusText = '✅ POSISI TERKUNCI [' + p.coords.latitude.toFixed(4) + ']';
                            this.statusClass = 'text-green-500 font-bold';
                        },
                        (e) => { 
                            this.gpsLocked = false; 
                            this.statusText = '❌ GPS TIDAK AKTIF / DITOLAK';
                            this.statusClass = 'text-red-500 font-bold';
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                },
                async submitAbsenFinal() {
                    // Cek Ulang GPS jika masih merah
                    if (!this.gpsLocked) {
                        this.isBusy = true; this.busyText = 'Menarik GPS Ulang...';
                        await new Promise(r => {
                            navigator.geolocation.getCurrentPosition((p) => {
                                this.$wire.set('data.lat', p.coords.latitude);
                                this.$wire.set('data.long', p.coords.longitude);
                                this.gpsLocked = true; r();
                            }, () => r(), { timeout: 5000 });
                        });
                    }

                    const img = document.querySelector('.filepond--item canvas') || document.querySelector('.filepond--image-preview img');
                    if (!img) { this.$wire.submit(); return; }

                    this.isBusy = true; this.busyText = 'Validasi Wajah...';

                    if (this.faceApiLoaded && window.faceapi) {
                        try {
                            const det = await faceapi.detectAllFaces(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 }));
                            if (det.length === 0) {
                                this.isBusy = false;
                                alert('🛑 STOP! Foto Anda tidak terdeteksi wajah bapak. Pastikan wajah jelas dan menghadap kamera.');
                                return;
                            }
                        } catch(e) { console.error("AI Detect err", e); }
                    }
                    
                    this.busyText = 'Mengirim Datamu...';
                    this.$wire.submit();
                }
            };
        }
    </script>

    <x-filament::section class="fi-fo-transparent-absen">
        <style>
            .fi-fo-transparent-absen { background: transparent !important; border: none !important; box-shadow: none !important; padding: 0 !important; }
            .fi-fo-file-upload-dropzone-label { display: none !important; }
            .filepond--root:not(.filepond--has-file) {
                background-color: #f1f5f9 !important;
                border: 2px dashed #94a3b8 !important;
                border-radius: 1.5rem !important;
                min-height: 160px;
                display: flex; direction: column; align-items: center; justify-content: center;
            }
            .filepond--root:not(.filepond--has-file)::before { content: "📷"; font-size: 3.5rem; margin-bottom: 0.5rem; opacity: 0.6; }
            .filepond--root:not(.filepond--has-file)::after { content: "KLIK UNTUK MULAI KAMERA"; font-weight: 800; font-size: 0.9rem; color: #475569; letter-spacing: 0.05em; }
            
            .dark .filepond--root:not(.filepond--has-file) { background-color: rgba(30, 41, 59, 0.5) !important; border-color: #334155 !important; }
            .dark .filepond--root:not(.filepond--has-file)::after { color: #94a3b8; }

            .btn-absen-formal {
                background: #4f46e5 !important; color: white !important; padding: 20px !important; border-radius: 14px !important;
                width: 100% !important; font-weight: 800 !important; font-size: 1.15rem !important; cursor: pointer !important;
                border: none !important; transition: all 0.2s ease-in-out; text-transform: uppercase;
            }
            .btn-absen-formal:hover { background: #4338ca !important; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
            .btn-absen-formal:disabled { background: #94a3b8 !important; opacity: 0.5; cursor: not-allowed; transform: none; }
        </style>

        <div x-data="window.mesinAbsenFormalFixV15()" class="flex flex-col items-center justify-center">
            
            <!-- Profil Card -->
            <div class="w-full max-w-2xl mb-8 p-6 bg-white/5 dark:bg-gray-800/20 backdrop-blur-md rounded-2xl border border-white/10 dark:border-gray-700/30">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-xl flex items-center justify-center overflow-hidden">
                        @if($userAvatar)
                            <img src="{{ $userAvatar }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-3xl font-black text-gray-400">{{ strtoupper(substr($userName ?? '?', 0, 1)) }}</span>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white">{{ $userName }}</h3>
                        <p class="text-[11px] text-gray-500 font-bold uppercase mt-1">{{ $userEmail }} @if($userClass) | {{ $userClass }} @endif</p>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-2xl text-center mb-6">
                <h2 class="text-sm font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest leading-relaxed">PRESENSI {{ strtoupper($tipeAbsens) }}</h2>
                <div class="h-1 w-12 bg-indigo-500 mx-auto mt-2 rounded-full opacity-50"></div>
            </div>

            <form @submit.prevent="submitAbsenFinal()" class="w-full max-w-2xl relative">
                @if($tipeAbsens === 'Selesai')
                    <div class="p-8 bg-green-50 dark:bg-green-900/10 border-2 border-dashed border-green-500/30 rounded-2xl text-center">
                        <p class="text-lg font-bold text-green-600 uppercase">Tugas Hari Ini Selesai 🚀</p>
                    </div>
                @else
                    <!-- Overlay Sibuk -->
                    <div x-show="isBusy" style="display: none;" class="absolute inset-0 z-50 flex items-center justify-center bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm rounded-2xl">
                        <div class="text-center">
                            <div class="animate-spin h-8 w-8 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto mb-3"></div>
                            <h3 class="text-sm font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-widest" x-text="busyText"></h3>
                        </div>
                    </div>

                    {{ $this->form }}

                    <div class="mt-8">
                        <button type="submit" 
                            wire:loading.attr="disabled" 
                            x-bind:disabled="isBusy" 
                            class="btn-absen-formal">
                            <span wire:loading.remove wire:target="submit">KIRIM PRESENSI {{ strtoupper($tipeAbsens) }}</span>
                            <span wire:loading wire:target="submit">MENGIRIM...</span>
                        </button>
                    </div>

                    <!-- GPS Info -->
                    <div class="mt-4 flex items-center justify-center gap-2 px-4 py-2 rounded-full bg-gray-100 dark:bg-gray-800/30 w-fit mx-auto border border-gray-200 dark:border-gray-700/50">
                        <div :class="gpsLocked ? 'bg-green-500 animate-pulse' : 'bg-red-400'" class="w-2 h-2 rounded-full"></div>
                        <p x-html="statusText" :class="statusClass" class="text-[9px] font-black uppercase tracking-tighter"></p>
                    </div>
                @endif
            </form>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
