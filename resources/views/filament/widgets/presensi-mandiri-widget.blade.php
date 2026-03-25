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
                        class="w-full px-6 py-3 text-white font-bold bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        Kirim Presensi Sekarang
                    </button>
                    <p id="gps-status" class="mt-2 text-xs text-center text-orange-500 font-semibold"></p>
                </div>
            </form>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btn = document.getElementById('btn-presensi');
                const status = document.getElementById('gps-status');

                function getGPS() {
                    if (navigator.geolocation) {
                        status.textContent = '📍 Mencari Lokasi GPS...';
                        btn.disabled = true;

                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                @this.set('data.lat', position.coords.latitude);
                                @this.set('data.long', position.coords.longitude);
                                status.textContent = '✅ Lokasi Terkunci (' + position.coords.latitude.toFixed(6) + ', ' + position.coords.longitude.toFixed(6) + ')';
                                btn.disabled = false;
                                console.log('Location Found:', position.coords.latitude, position.coords.longitude);
                            },
                            (error) => {
                                console.error('GPS Error:', error);
                                status.textContent = '❌ Gagal mendapatkan lokasi. Harap izinkan akses GPS.';
                                btn.disabled = true;
                                if (error.code === 1) {
                                    alert('Izin GPS ditolak. Anda wajib mengaktifkan GPS untuk absensi.');
                                }
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 15000,
                                maximumAge: 0
                            }
                        );
                    } else {
                        status.textContent = '❌ Browser tidak mendukung Geolocation.';
                        btn.disabled = true;
                    }
                }

                // Jalankan saat load
                getGPS();

                // Cek ulang setiap 30 detik
                setInterval(getGPS, 30000);
            });
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
