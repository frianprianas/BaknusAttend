<x-filament-panels::page>
    @php $user = auth()->user(); @endphp

    <div class="max-w-xl mx-auto w-full">

        @if($izinHariIni)
            {{-- Status: Aktif --}}
            <x-filament::section>
                <div class="flex flex-col items-center gap-5 py-4 text-center">
                    <div class="w-20 h-20 rounded-full flex items-center justify-center text-5xl
                        {{ $izinHariIni->tipe === 'Izin' ? 'bg-blue-100 dark:bg-blue-900' : 'bg-amber-100 dark:bg-amber-900' }}">
                        {{ $izinHariIni->tipe === 'Izin' ? '📋' : '🤒' }}
                    </div>

                    <div>
                        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white">
                            Pengajuan {{ $izinHariIni->tipe }} Aktif
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $izinHariIni->tanggal->isoFormat('dddd, D MMMM Y') }}
                        </p>
                        <div class="mt-3 inline-block px-3 py-1 rounded-full text-xs font-bold
                            {{ $izinHariIni->status === 'Disetujui' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                            Status: {{ $izinHariIni->status }}
                        </div>
                    </div>

                    <div class="w-full bg-gray-50 dark:bg-gray-800 border border-dashed border-gray-300 dark:border-gray-600 rounded-xl px-5 py-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300 italic">"{{ $izinHariIni->alasan }}"</p>
                    </div>

                    @if($izinHariIni->bukti)
                        <a href="{{ asset('storage/' . $izinHariIni->bukti) }}" target="_blank"
                            class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 underline font-medium">
                            📎 Lihat Lampiran Bukti
                        </a>
                    @endif

                    <div class="w-full bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl px-5 py-3">
                        <p class="text-sm font-bold text-red-700 dark:text-red-400">⛔ Absensi Dinonaktifkan</p>
                        <p class="text-xs text-red-500 dark:text-red-300 mt-1">
                            Anda tidak dapat melakukan absensi selama pengajuan ini aktif.
                        </p>
                    </div>

                    <button type="button"
                        wire:click="batalkan"
                        wire:confirm="Yakin ingin membatalkan pengajuan ini?"
                        class="px-5 py-2 text-sm font-semibold text-red-600 hover:text-red-800 border border-red-300 hover:border-red-500 rounded-xl transition">
                        ✖ Batalkan Pengajuan
                    </button>
                </div>
            </x-filament::section>

        @else
            {{-- Form Pengajuan Baru --}}
            <x-filament::section>
                <div class="flex flex-col items-center mb-6 text-center">
                    <div class="w-16 h-16 rounded-full bg-orange-100 dark:bg-orange-900 flex items-center justify-center text-4xl mb-3">
                        📄
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Ajukan Izin / Sakit</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Halo, <strong>{{ $user->name }}</strong>! Isi formulir di bawah jika Anda tidak dapat hadir hari ini.
                    </p>
                </div>

                <form wire:submit.prevent="submit" class="space-y-4">
                    {{ $this->form }}

                    <div class="pt-2">
                        <button type="submit"
                            wire:loading.attr="disabled"
                            class="w-full flex items-center justify-center gap-2 px-6 py-3 text-white font-bold text-base
                                bg-orange-500 hover:bg-orange-600 disabled:bg-gray-400 rounded-xl shadow-md
                                transition duration-200 transform hover:scale-[1.02] active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
