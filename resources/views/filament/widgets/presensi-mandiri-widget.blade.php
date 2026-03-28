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
                        status.innerHTML = '<span class="animate-pulse">📍 Sedang Mencari Lokasi GPS Anda...</span>';
                        status.className = 'mt-2 text-xs text-center text-orange-500 font-bold';
                        btn.disabled = true;

                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                @this.set('data.lat', position.coords.latitude);
                                @this.set('data.long', position.coords.longitude);
                                status.innerHTML = '✅ Lokasi Terkunci (' + position.coords.latitude.toFixed(6) + ', ' + position.coords.longitude.toFixed(6) + ')';
                                status.className = 'mt-2 text-xs text-center text-green-600 font-bold';
                                btn.disabled = false;
                            },
                            (error) => {
                                console.error('GPS Error:', error);
                                btn.disabled = true;
                                if (error.code === 1) {
                                    status.innerHTML = '❌ Izin GPS Ditolak! <br> <span class="text-[10px] text-gray-500 font-normal">Harap aktifkan GPS & muat ulang halaman ini.</span>';
                                    alert('Peringatan: Presensi Mandiri WAJIB menggunakan GPS. Mohon aktifkan izin lokasi di browser Anda.');
                                } else {
                                    status.innerHTML = '❌ Gagal mendapatkan lokasi. <br> <a href="javascript:location.reload()" class="underline text-indigo-600">Klik untuk Coba Lagi</a>';
                                }
                                status.className = 'mt-2 text-xs text-center text-red-500 font-bold';
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 10000,
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

                // Cek ulang setiap 60 detik jika belum ada koordinat
                const gpsInterval = setInterval(() => {
                    const lat = @this.get('data.lat');
                    if (!lat) {
                        getGPS();
                    }
                }, 60000);
            });
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
