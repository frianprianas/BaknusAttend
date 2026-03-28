<x-filament-widgets::widget>
    <x-filament::section>
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
                    <button type="submit" 
                        wire:loading.attr="disabled"
                        x-bind:disabled="!$wire.data.lat || isSearching"
                        class="w-full px-6 py-3 text-white font-bold bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 rounded-xl shadow-lg transition duration-200 disabled:cursor-not-allowed">
                        Kirim Presensi Sekarang
                    </button>
                    
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
