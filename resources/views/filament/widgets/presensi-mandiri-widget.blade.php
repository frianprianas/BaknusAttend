<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center p-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Presensi Mandiri</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 text-center">
                Silakan nyalakan GPS dan ambil foto selfie untuk melakukan absensi.
            </p>

            <form wire:submit.prevent="submit" class="w-full max-w-md">
                {{ $this->form }}

                <div class="mt-6">
                    <button type="submit" 
                        id="btn-presensi"
                        disabled
                        class="w-full px-6 py-3 text-white font-bold bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 rounded-xl shadow-lg transition duration-200 disabled:cursor-not-allowed">
                        Kirim Presensi Sekarang
                    </button>
                    
                    <!-- GPS Status Display -->
                    <div id="gps-status-container" class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p id="gps-status" class="text-xs text-center text-gray-500 font-bold leading-relaxed"></p>
                        <div id="gps-retry-container" class="hidden text-center mt-2">
                            <button type="button" onclick="getGPS()" class="text-[10px] underline text-indigo-600 font-bold uppercase tracking-wider">
                                🔄 Coba Hubungkan GPS Lagi
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script>
            function getGPS() {
                const btn = document.getElementById('btn-presensi');
                const status = document.getElementById('gps-status');
                const retryContainer = document.getElementById('gps-retry-container');

                if (!navigator.geolocation) {
                    status.innerHTML = '❌ Browser Anda tidak mendukung GPS.';
                    status.className = 'text-xs text-center text-red-500 font-bold';
                    return;
                }

                status.innerHTML = '<span class="animate-pulse">🛰️ Sedang Meminta Izin & Melacak Lokasi Anda... Jika muncul notifikasi, harap tekan "ALLOW" / "IZINKAN".</span>';
                status.className = 'text-xs text-center text-orange-500 font-bold italic';
                retryContainer.classList.add('hidden');
                btn.disabled = true;

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        
                        @this.set('data.lat', lat);
                        @this.set('data.long', lon);
                        
                        status.innerHTML = '✅ LOKASI GPS TERKUNCI <br> <span class="text-[10px] text-gray-400 font-mono">[' + lat.toFixed(6) + ', ' + lon.toFixed(6) + ']</span>';
                        status.className = 'text-xs text-center text-green-600 font-bold';
                        btn.disabled = false;
                        retryContainer.classList.add('hidden');
                    },
                    (error) => {
                        btn.disabled = true;
                        retryContainer.classList.remove('hidden');
                        
                        if (error.code === 1) { // PERMISSION_DENIED
                            status.innerHTML = '❌ IZIN GPS DITOLAK! <br> <span class="text-[10px] text-gray-500 font-normal italic">Anda WAJIB memberikan izin lokasi di pengaturan browser untuk bisa melakukan absensi mandiri.</span>';
                            status.className = 'text-xs text-center text-red-600 font-bold';
                            alert('Peringatan: Absensi Mandiri hanya bisa dilakukan jika GPS Aktif. Mohon izinkan akses lokasi pada browser Anda.');
                        } else if (error.code === 2) { // POSITION_UNAVAILABLE
                            status.innerHTML = '❌ SINYAL GPS LEMAH <br> <span class="text-[10px] text-gray-500 font-normal">Pastikan Anda berada di luar ruangan atau dekat jendela.</span>';
                            status.className = 'text-xs text-center text-red-500 font-bold';
                        } else {
                            status.innerHTML = '❌ WAKTU GPS HABIS (TIMEOUT) <br> <span class="text-[10px] text-gray-500 font-normal">Harap muat ulang halaman atau pindah ke area terbuka.</span>';
                            status.className = 'text-xs text-center text-red-500 font-bold';
                        }
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            }

            // Jalankan segera saat dashboard terbuka
            document.addEventListener('DOMContentLoaded', () => {
                getGPS();
                
                // Interval pengecekan setiap 60 detik agar lokasi selalu akurat
                setInterval(() => {
                    const currentLat = @this.get('data.lat');
                    if (!currentLat) {
                        getGPS();
                    }
                }, 60000);
            });
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
