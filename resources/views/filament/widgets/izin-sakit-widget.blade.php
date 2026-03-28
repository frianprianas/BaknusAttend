<x-filament-widgets::widget>
    <x-filament::section>
        @php $user = auth()->user(); @endphp

        @if($izinHariIni)
            {{-- Status: Sudah Mengajukan Izin/Sakit --}}
            <div class="flex flex-col items-center gap-4 p-4">
                <div class="w-16 h-16 rounded-full flex items-center justify-center text-4xl
                    {{ $izinHariIni->tipe === 'Izin' ? 'bg-blue-100' : 'bg-yellow-100' }}">
                    {{ $izinHariIni->tipe === 'Izin' ? '📋' : '🤒' }}
                </div>

                <div class="text-center">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                        Pengajuan {{ $izinHariIni->tipe }} Aktif
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Status: 
                        <span class="font-semibold {{ $izinHariIni->status === 'Disetujui' ? 'text-green-600' : 'text-orange-500' }}">
                            {{ $izinHariIni->status }}
                        </span>
                        · {{ $izinHariIni->tanggal->format('d M Y') }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-2 bg-gray-50 dark:bg-gray-800 rounded-lg px-4 py-2 border border-dashed border-gray-300 dark:border-gray-600">
                        "{{ $izinHariIni->alasan }}"
                    </p>
                    @if($izinHariIni->bukti)
                        <a href="{{ asset('storage/' . $izinHariIni->bukti) }}" target="_blank"
                            class="inline-flex items-center gap-1 mt-2 text-xs text-indigo-600 underline hover:text-indigo-800">
                            📎 Lihat Lampiran Bukti
                        </a>
                    @endif
                </div>

                <div class="w-full max-w-xs bg-orange-50 border border-orange-200 rounded-lg px-4 py-3 text-center">
                    <p class="text-xs font-bold text-orange-700">⛔ Absensi Dinonaktifkan</p>
                    <p class="text-xs text-orange-500 mt-1">Anda tidak bisa melakukan absensi selama pengajuan aktif.</p>
                </div>

                <button type="button"
                    wire:click="batalkan"
                    wire:confirm="Batalkan pengajuan izin/sakit hari ini?"
                    class="text-sm text-red-500 hover:text-red-700 underline font-semibold transition">
                    ✖ Batalkan Pengajuan
                </button>
            </div>
        @else
            {{-- Form Pengajuan Baru --}}
            <div class="flex flex-col items-center p-4">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-1">Izin / Sakit</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 text-center">
                    Ajukan keterangan izin atau sakit jika Anda tidak dapat hadir hari ini.
                </p>

                <form wire:submit.prevent="submit" class="w-full max-w-md space-y-4">
                    {{ $this->form }}

                    <button type="submit"
                        wire:loading.attr="disabled"
                        class="w-full flex items-center justify-center gap-2 px-6 py-3 text-white font-bold text-base bg-orange-500 hover:bg-orange-600 disabled:bg-gray-400 rounded-xl shadow-lg transition duration-200 transform hover:scale-[1.02] active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Kirim Pengajuan
                    </button>
                </form>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
