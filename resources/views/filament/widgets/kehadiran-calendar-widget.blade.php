<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col">
            <!-- Header Navigasi Kalender -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 dark:text-white tracking-tight uppercase">
                        Kalender Presensi Saya
                    </h2>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1 italic">
                        {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button wire:click="previousMonth" class="p-2 bg-gray-100 dark:bg-gray-800 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <button wire:click="nextMonth" class="p-2 bg-gray-100 dark:bg-gray-800 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
            </div>

            <!-- Legenda Warna -->
            <div class="flex flex-wrap items-center gap-6 mb-8 text-[10px] font-black uppercase tracking-widest">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-md bg-indigo-600 shadow-md shadow-indigo-500/30"></div>
                    <span class="text-gray-600 dark:text-gray-400">Hadir Lengkap (In & Out)</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-md bg-sky-400 shadow-md shadow-sky-400/30"></div>
                    <span class="text-gray-600 dark:text-gray-400">Masuk Saja</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-md bg-gray-200 dark:bg-gray-800"></div>
                    <span class="text-gray-400">Tanpa Data</span>
                </div>
            </div>

            <!-- Body Kalender (Grid 7 Kolom) -->
            <div class="grid grid-cols-7 gap-2 md:gap-4">
                <!-- Nama Hari -->
                @foreach(['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $dayName)
                    <div class="text-center text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-2">
                        {{ $dayName }}
                    </div>
                @endforeach

                <!-- Spasi di Awal Bulan -->
                @for($i = 0; $i < $firstDayOfMonth; $i++)
                    <div class="aspect-square rounded-2xl bg-gray-50 dark:bg-gray-900/10"></div>
                @endfor

                <!-- Tanggal -->
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $status = $presenceData[$day] ?? 'none';
                        $bgClass = $status === 'dark' ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-600/30 ring-2 ring-indigo-400 ring-offset-2 dark:ring-offset-gray-900' 
                                 : ($status === 'light' ? 'bg-sky-400 text-white shadow-lg shadow-sky-400/20' 
                                 : 'bg-white dark:bg-gray-800 text-gray-400 border border-gray-100 dark:border-gray-750');
                    @endphp
                    <div class="aspect-square relative group transition-all duration-300">
                        <div class="absolute inset-0 {{ $bgClass }} rounded-2x md:rounded-[1.5rem] flex items-center justify-center font-black text-lg backdrop-blur-md transform group-hover:scale-105 group-hover:-rotate-3 cursor-default">
                            {{ $day }}
                            
                            @if($status !== 'none')
                                <div class="absolute bottom-1 w-1 h-1 bg-white rounded-full animate-pulse"></div>
                            @endif
                        </div>
                    </div>
                @endfor
            </div>

            <div class="mt-8 p-4 bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-200 dark:border-indigo-800 rounded-2xl">
                <p class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold italic text-center leading-relaxed">
                    "Disiplin adalah kunci kesuksesan. Terus tingkatkan kehadiran Anda demi masa depan Bakti Nusantara 666."
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
