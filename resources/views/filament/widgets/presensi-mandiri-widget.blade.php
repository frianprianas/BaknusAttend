<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            /* Override Filament's default Drag & Drop text */
            .fi-fo-file-upload-dropzone .fi-fo-file-upload-dropzone-label { display: none !important; }
            .fi-fo-file-upload-dropzone::after { content: "{{ $labelTombol }}"; font-weight: bold; font-size: 1rem; color: #4f46e5; display: block; margin-top: 10px; text-align: center; }
        </style>
        <div x-data="{
                statusText: '',
                statusClass: '',
                showRetry: false,
                isSearching: false,
                
                init() {
                    this.getGPS();
                    
                    setInterval(() => {
                        if (!this.$wire.data.lat) {
                            this.getGPS();
                        }
                    }, 60000);
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
                }
            }" 
            class="flex flex-col items-center justify-center p-4">
            
            <!-- Student/User Profile Header Card -->
            <div class="w-full max-w-2xl mb-8 animate-in fade-in slide-in-from-top-4 duration-700">
                <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 to-purple-700 rounded-3xl p-6 shadow-2xl shadow-indigo-500/20">
                    <!-- Decorative Circles in background -->
                    <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-24 h-24 bg-white/5 rounded-full blur-xl"></div>
                    
                    <div class="flex flex-col md:flex-row items-center gap-6 relative z-10 text-center md:text-left">
                        <div class="w-20 h-20 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/30 shadow-inner overflow-hidden">
                            @if($userAvatar)
                                <img src="{{ $userAvatar }}" 
                                     alt="Avatar" 
                                     class="w-full h-full object-cover"
                                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($userName) }}&color=7F9CF5&background=EBF4FF';">
                            @else
                                <span class="text-3xl font-black text-white">
                                    {{ strtoupper(substr($userName ?? '?', 0, 1)) }}
                                </span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-black text-white leading-tight mb-1">{{ $userName }}</h3>
                            <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-4 text-white/80 font-medium text-sm">
                                <span class="flex items-center gap-1.5 pt-1 md:pt-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    {{ $userEmail }}
                                </span>
                                @if($userClass)
                                <span class="hidden md:inline text-white/40">|</span>
                                <span class="flex items-center gap-1.5 px-3 py-1 bg-white/15 rounded-full text-xs font-black uppercase tracking-widest border border-white/20">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
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
            </p>

            <form wire:submit.prevent="submit" class="w-full max-w-2xl relative">
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
                    <!-- Loading Overlay -->
                    <div wire:loading wire:target="submit" class="absolute inset-0 z-50 flex items-center justify-center bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-xl border border-indigo-200">
                        <div class="flex flex-col items-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border-2 border-indigo-500">
                            <x-filament::loading-indicator class="h-12 w-12 text-indigo-600 mb-4" />
                            <h3 class="text-lg font-bold text-indigo-700 dark:text-indigo-400 animate-pulse text-center">
                                Verifikasi wajah dengan BaknusAI sedang berjalan...
                            </h3>
                            <p class="text-xs text-gray-500 mt-2 italic text-center">Mohon tunggu sebentar, kami sedang mencocokkan wajah Anda.</p>
                        </div>
                    </div>

                    {{ $this->form }}

                    <div class="mt-4 flex justify-center">
                        <button type="submit" 
                            wire:loading.attr="disabled"
                            x-bind:disabled="!$wire.data.lat || isSearching"
                            class="w-full flex items-center justify-center gap-3 px-6 py-4 text-white font-extrabold text-base bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 rounded-xl shadow-lg transition duration-200 disabled:cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span wire:loading.remove wire:target="submit">{{ $labelTombol }}</span>
                            <span wire:loading wire:target="submit" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
