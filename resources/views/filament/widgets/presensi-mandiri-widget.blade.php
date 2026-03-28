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
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Presensi Mandiri</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 text-center">
                Silakan nyalakan GPS dan ambil foto selfie untuk melakukan absensi.
            </p>

            <form wire:submit.prevent="submit" class="w-full max-w-md">
                {{ $this->form }}

                <div class="mt-6">
                    @if($tipeAbsens !== 'Selesai')
                    <button type="submit" 
                        wire:loading.attr="disabled"
                        x-bind:disabled="!$wire.data.lat || isSearching"
                        class="w-full flex items-center justify-center gap-3 px-6 py-4 text-white font-extrabold text-lg bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 rounded-xl shadow-2xl transition duration-200 disabled:cursor-not-allowed transform hover:scale-[1.02] active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ $labelTombol }}
                    </button>
                    @endif
                    
                    <!-- GPS Status Display -->
                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p x-html="statusText" :class="statusClass"></p>
                        <div x-show="showRetry" style="display: none;" class="text-center mt-2">
                            <button type="button" @click="getGPS()" class="text-[10px] underline text-indigo-600 font-bold uppercase tracking-wider">
                                🔄 Coba Hubungkan GPS Lagi
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
