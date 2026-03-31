<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            .fi-fo-file-upload-dropzone-label { display: none !important; }
            .filepond--root:not(.filepond--has-file) {
                background-color: #e0e7ff !important;
                border: 2px dashed #4f46e5 !important;
                border-radius: 1rem !important;
                cursor: pointer !important;
                min-height: 160px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                transition: all 0.3s;
            }
            .filepond--root:not(.filepond--has-file)::before {
                content: "📷";
                font-size: 3.5rem;
                display: block;
                margin-bottom: 0.5rem;
                text-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .filepond--root:not(.filepond--has-file)::after {
                content: "KLIK UNTUK MULAI KAMERA";
                font-weight: 900;
                font-size: 1.15rem;
                color: #4f46e5;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .dark .filepond--root:not(.filepond--has-file) {
                background-color: rgba(67, 56, 202, 0.1) !important;
                border-color: #6366f1 !important;
            }
            .dark .filepond--root:not(.filepond--has-file)::after {
                color: #a5b4fc;
            }
            .filepond--root.filepond--has-file::before,
            .filepond--root.filepond--has-file::after {
                display: none !important;
            }
        </style>
        <div x-data="{
                statusText: '',
                statusClass: '',
                showRetry: false,
                isSearching: false,
                isScanningFace: false,
                faceApiLoaded: false,
                
                init() {
                    this.getGPS();
                    this.loadFaceApi();
                    
                    setInterval(() => {
                        if (!this.$wire.data.lat) {
                            this.getGPS();
                        }
                    }, 60000);
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
                    if (!navigator.geolocation) {
                        this.statusText = '❌ Browser Anda tidak mendukung GPS.';
                        this.statusClass = 'text-xs text-center text-red-500 font-bold';
                        return;
                    }

                    this.statusText = '<span class=\'animate-pulse\'>🛰️ Sedang Meminta Izin & Melacak Lokasi Anda... Jika muncul notifikasi, harap tekan ALLOW / IZINKAN.</span>';
                    this.statusClass = 'text-xs text-center text-orange-500 font-bold italic';
                    this.showRetry = false;
                    this.isSearching = true;

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lon = position.coords.longitude;
                            
                            this.$wire.set('data.lat', lat);
                            this.$wire.set('data.long', lon);
                            
                            this.statusText = '✅ LOKASI GPS TERKUNCI <br> <span class=\'text-[10px] text-gray-400 font-mono\'>[' + lat.toFixed(6) + ', ' + lon.toFixed(6) + ']</span>';
                            this.statusClass = 'text-xs text-center text-green-600 font-bold';
                            this.showRetry = false;
                            this.isSearching = false;
                        },
                        (error) => {
                            this.isSearching = false;
                            this.showRetry = true;
                            
                            if (error.code === 1) {
                                this.statusText = '❌ IZIN GPS DITOLAK! <br> <span class=\'text-[10px] text-gray-500 font-normal italic\'>Anda WAJIB memberikan izin lokasi di pengaturan browser untuk bisa melakukan absensi mandiri.</span>';
                                this.statusClass = 'text-xs text-center text-red-600 font-bold';
                                alert('Peringatan: Absensi Mandiri hanya bisa dilakukan jika GPS Aktif. Mohon izinkan akses lokasi pada browser Anda.');
                            } else if (error.code === 2) {
                                this.statusText = '❌ SINYAL GPS LEMAH <br> <span class=\'text-[10px] text-gray-500 font-normal\'>Pastikan Anda berada di luar ruangan atau dekat jendela.</span>';
                                this.statusClass = 'text-xs text-center text-red-500 font-bold';
                            } else {
                                this.statusText = '❌ WAKTU GPS HABIS (TIMEOUT) <br> <span class=\'text-[10px] text-gray-500 font-normal\'>Harap muat ulang halaman atau pindah ke area terbuka.</span>';
                                this.statusClass = 'text-xs text-center text-red-500 font-bold';
                            }
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0
                        }
                    );
                },

                async validateFaceAndSubmit() {
                    const imgSource = document.querySelector('.filepond--item canvas') || document.querySelector('.filepond--image-preview img');
                    
                    if (!imgSource) {
                        this.$wire.submit(); 
                        return;
                    }

                    this.isScanningFace = true;
                    
                    // 1. Coba deteksi menggunakan model akurat face-api.js
                    if (this.faceApiLoaded && window.faceapi) {
                        try {
                            const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 });
                            const detections = await faceapi.detectAllFaces(imgSource, options);
                            
                            this.isScanningFace = false;
                            if (detections.length > 0) {
                                this.$wire.submit();
                            } else {
                                alert('🛑 FOTO DITOLAK: AI tidak mendeteksi wajah sama sekali. Tolong foto wajah Anda menghadap ke kamera dengan jelas.');
                            }
                            return;
                        } catch (e) {
                            console.error("FaceAPI error:", e);
                        }
                    }

                    // 2. Fallback cadangan pakai FaceDetector eksperimental Chrome bawaan
                    if ('FaceDetector' in window) {
                        try {
                            const detector = new FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
                            const faces = await detector.detect(imgSource);
                            this.isScanningFace = false;
                            if (faces.length > 0) {
                                this.$wire.submit();
                            } else {
                                alert('🛑 FOTO DITOLAK: AI tidak mendeteksi wajah sama sekali. Tolong foto wajah Anda menghadap ke kamera dengan jelas.');
                            }
                            return;
                        } catch (e) {
                            console.error("Native FaceDetector error:", e);
                        }
                    }
                    
                    // Bypass jika kedua security gagal berjalan
                    this.isScanningFace = false;
                    this.$wire.submit();
                }
            }" 
            class="flex flex-col items-center justify-center p-4">
            
            <!-- Student/User Profile Header Card -->
            <div class="w-full max-w-3xl mb-10 animate-in fade-in slide-in-from-top-4 duration-700">
                <div class="relative overflow-hidden bg-white/5 dark:bg-gray-800/40 backdrop-blur-xl rounded-[2.5rem] p-6 md:p-8 border border-white/20 dark:border-gray-700/50 shadow-2xl">
                    <!-- Subtle Decorative Background -->
                    <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-600/20 rounded-full blur-[80px]"></div>
                    <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-purple-600/10 rounded-full blur-[60px]"></div>
                    
                    <div class="flex flex-col md:flex-row items-center gap-8 relative z-10">
                        <!-- Avatar Section -->
                        <div class="relative group shrink-0">
                            <div class="absolute -inset-1 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
                            <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gray-200 dark:bg-gray-700 rounded-2xl overflow-hidden border-4 border-white dark:border-gray-800 shadow-xl flex items-center justify-center">
                                @if($userAvatar)
                                    <img src="{{ $userAvatar }}" 
                                         alt="Avatar" 
                                         class="w-full h-full object-cover transform transition duration-700 group-hover:scale-110"
                                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($userName) }}&color=7F9CF5&background=EBF4FF';">
                                @else
                                    <span class="text-5xl font-black text-indigo-600 opacity-20">
                                        {{ strtoupper(substr($userName ?? '?', 0, 1)) }}
                                    </span>
                                @endif
                            </div>
                            <!-- Link Ganti Foto -->
                            <a href="https://baknusmail.smkbn666.sch.id" target="_blank" class="mt-3 flex items-center justify-center gap-1.5 text-[10px] font-black uppercase tracking-tighter text-indigo-600 dark:text-indigo-400 hover:text-purple-600 transition-colors bg-white/50 dark:bg-gray-800/50 py-1 px-2 rounded-lg border border-indigo-100 dark:border-indigo-900 group-hover:scale-105 transform duration-300 shadow-sm">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                BaknusMail (Ganti Foto)
                            </a>
                        </div>

                        <!-- Info Section -->
                        <div class="flex-1 text-center md:text-left">
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 text-xs font-black uppercase tracking-widest mb-3">
                                Welcome Back,
                            </span>
                            <h3 class="text-3xl md:text-4xl font-black text-gray-800 dark:text-white leading-tight mb-2 tracking-tight">
                                {{ $userName }}
                            </h3>
                            <div class="flex flex-col md:flex-row items-center gap-2 md:gap-4 text-gray-500 dark:text-gray-400 font-bold text-sm">
                                <span class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700/50 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-600">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    {{ $userEmail }}
                                </span>
                                @if($userClass)
                                <span class="flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-1.5 rounded-xl shadow-lg shadow-indigo-500/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    Kelas: {{ $userClass }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 mb-2 tracking-tight text-center uppercase">Presensi Mandiri</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-8 text-center font-medium">
                Silakan nyalakan GPS dan ambil foto selfie untuk melakukan absensi.
                <span class="text-[11px] text-orange-600 dark:text-orange-400 font-bold block mt-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-100 dark:border-orange-800">
                    ⚠️ PENTING: Sistem HP akan memblokir foto secara otomatis jika tidak mendeteksi wajah dengan jelas.
                </span>
            </p>

            <form @submit.prevent="validateFaceAndSubmit" class="w-full max-w-2xl relative">
                @if($tipeAbsens === 'Selesai')
                    <div class="flex flex-col items-center justify-center p-10 bg-green-50 dark:bg-green-900/20 border-2 border-dashed border-green-500 rounded-3xl animate-in fade-in zoom-in duration-500">
                        <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mb-6 shadow-lg shadow-green-500/50">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-black text-green-700 dark:text-green-400 text-center uppercase tracking-tight">Presensi Selesai!</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-center mt-3 font-medium text-lg leading-relaxed">
                            Terima kasih sudah mengisi presensi hari ini. <br>
                            Selamat beristirahat dan tetap semangat! 🚀
                        </p>
                    </div>
                @elseif(auth()->user()->role === 'Siswa')
                    <div class="flex flex-col items-center justify-center p-12 bg-indigo-50 dark:bg-indigo-900/10 border-2 border-dashed border-indigo-200 dark:border-indigo-800 rounded-3xl animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div class="w-20 h-20 bg-indigo-600 rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-indigo-600/30 rotate-3">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-black text-indigo-800 dark:text-indigo-400 text-center tracking-tight uppercase">Siswa / Pelajar</h3>
                        <div class="mt-6 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-indigo-100 dark:border-indigo-900 mx-auto max-w-md">
                            <p class="text-gray-600 dark:text-gray-300 text-center font-bold text-lg leading-relaxed">
                                Mohon Maaf, Fasilitas Presensi Wajah sedang dinonaktifkan sementara untuk Siswa.
                            </p>
                            <div class="mt-4 flex items-center justify-center gap-2 text-indigo-600 dark:text-indigo-300 font-extrabold text-center bg-indigo-50 dark:bg-indigo-900/30 p-3 rounded-xl border border-indigo-200 dark:border-indigo-800">
                                <span class="text-2xl">🎴</span>
                                <span>Silakan melakukan presensi dengan Kartu Pelajar (RFID) Anda.</span>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Local AI Scanning Overlay -->
                    <div x-show="isScanningFace" style="display: none;" class="absolute inset-0 z-50 flex items-center justify-center bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-xl border border-orange-200">
                        <div class="flex flex-col items-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border-2 border-orange-500 transform transition-all scale-100 opacity-100">
                            <svg class="animate-spin h-12 w-12 text-orange-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <h3 class="text-lg font-bold text-orange-600 dark:text-orange-400 animate-pulse text-center">
                                Sabar, HP Anda sedang menscan Wajah...
                            </h3>
                            <p class="text-xs text-gray-500 mt-2 italic text-center">Memastikan gambar layak dikirim ke Server Pusat.</p>
                        </div>
                    </div>

                    <!-- Server AI Loading Overlay -->
                    <div wire:loading wire:target="submit" class="absolute inset-0 z-[49] flex items-center justify-center bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-xl border border-indigo-200">
                        <div class="flex flex-col items-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border-2 border-indigo-500">
                            <x-filament::loading-indicator class="h-12 w-12 text-indigo-600 mb-4" />
                            <h3 class="text-lg font-bold text-indigo-700 dark:text-indigo-400 animate-pulse text-center">
                                Verifikasi wajah dengan BaknusAI sedang berjalan...
                            </h3>
                            <p class="text-xs text-gray-500 mt-2 italic text-center">Mohon tunggu sebentar, kami sedang mencocokkan wajah Anda.</p>
                        </div>
                    </div>

                    {{ $this->form }}

                    <div class="mt-8 flex justify-center">
                        <button type="submit" 
                            wire:loading.attr="disabled"
                            x-bind:disabled="!$wire.data.lat || isSearching || isScanningFace"
                            class="w-full flex items-center justify-center gap-3 px-6 py-5 text-white font-black text-xl bg-gradient-to-r from-indigo-600 to-indigo-800 hover:from-indigo-500 hover:to-indigo-700 rounded-2xl shadow-[0_10px_25px_-5px_rgba(79,70,229,0.5)] transform transition-transform hover:-translate-y-1 active:translate-y-0 disabled:bg-gray-400 disabled:from-gray-400 disabled:to-gray-500 disabled:shadow-none disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="submit">🚀 KIRIM PRESENSI SEKARANG</span>
                            <span wire:loading wire:target="submit" class="flex items-center gap-2">
                                <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Sedang memproses...
                            </span>
                        </button>
                    </div>

                    <!-- GPS Status Display -->
                    <div class="mt-4 p-4 bg-white/50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 transition-all duration-300">
                        <div class="flex items-center justify-center gap-3">
                            <div x-show="!isSearching && $wire.data.lat" class="p-2 bg-green-100 rounded-full">
                                <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p x-html="statusText" :class="statusClass" class="leading-relaxed"></p>
                        </div>
                        <div x-show="showRetry" style="display: none;" class="text-center mt-3">
                            <button type="button" @click="getGPS()" class="px-4 py-2 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 transition font-bold text-xs uppercase">
                                🔄 Coba Hubungkan GPS Lagi
                            </button>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
