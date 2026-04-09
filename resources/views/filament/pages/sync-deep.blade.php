<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <x-filament::section>
                <x-slot name="heading">
                    Data Siswa Anomali / Tanpa Kelas
                </x-slot>
                
                <div class="text-sm text-gray-500 mb-4">
                    Tabel ini menampilkan data siswa yang tercatat di database namun belum memiliki ikatan kelas yang valid. Gunakan form di sebelah untuk melakukan perbaikan massal.
                </div>

                {{ $this->table }}
            </x-filament::section>
        </div>

        <div>
            <x-filament::section>
                <x-slot name="heading">
                    Konfigurasi Singkron Mendalam
                </x-slot>

                <form wire:submit.prevent="startSync">
                    {{ $this->form }}

                    <div class="mt-4">
                        <x-filament::button 
                            type="submit" 
                            size="lg" 
                            class="w-full"
                            wire:loading.attr="disabled"
                            icon="heroicon-o-cpu-chip"
                            color="danger"
                        >
                            <span wire:loading.remove wire:target="startSync">Mulai Perbaikan Mendalam</span>
                            <span wire:loading wire:target="startSync">Membaca File...</span>
                        </x-filament::button>
                    </div>
                </form>

                <div class="mt-4 text-xs text-gray-500 italic">
                    * Format CSV: nama,kelas. Sistem akan mencari kecocokan nama dan menimpa kelas lama/kosong menjadi kelas baru secara paksa.
                </div>

                @if(!$isSyncing && $report['total'] > 0)
                <div class="mt-6 p-4 bg-green-50 dark:bg-green-950/20 rounded-lg border border-green-200 dark:border-green-800">
                    <h3 class="text-lg font-bold mb-2 text-green-800 dark:text-green-300">Laporan Perbaikan</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Total Baris:</span>
                            <span class="font-mono font-bold">{{ $report['total'] }}</span>
                        </div>
                        <div class="flex justify-between text-green-700 dark:text-green-400">
                            <span>Diperbaiki/Sukses:</span>
                            <span class="font-mono font-bold">{{ $report['success'] }}</span>
                        </div>
                        <div class="flex justify-between text-red-700 dark:text-red-400">
                            <span>Gagal:</span>
                            <span class="font-mono font-bold">{{ $report['failed'] }}</span>
                        </div>
                    </div>
                </div>
                @endif
            </x-filament::section>
        </div>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                <span class="ml-2 font-mono text-sm">root@baknus-attend:~/deep-sync</span>
            </div>
        </x-slot>

        <div 
            id="terminal-window"
            class="bg-[#0D1117] text-[#FF7B72] font-mono p-5 rounded-xl h-[400px] overflow-y-auto border border-gray-800 shadow-[0_20px_50px_rgba(0,0,0,0.5)] relative custom-scrollbar"
            x-data="{ 
                scrollToBottom() { 
                    this.$el.scrollTop = this.$el.scrollHeight 
                } 
            }"
            x-init="scrollToBottom(); $watch('logs', () => scrollToBottom())"
        >
            <div class="absolute top-0 left-0 right-0 h-8 bg-gray-800/50 backdrop-blur-md flex items-center px-4 gap-1.5 border-b border-white/5">
                <span class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Deep Sync Overwrite System</span>
            </div>

            <div class="pt-8">
                @if(empty($logs))
                    <div class="text-gray-600 italic">
                        <span class="text-red-500">root@baknus</span>:<span class="text-blue-500">~</span>$ ./deep-overwrite.sh<br>
                        [SYSTEM] Ready for deep synchronization. Warning: This will force update relations.
                    </div>
                @endif
                
                @foreach($logs as $log)
                    <div class="mb-1 leading-relaxed animate-in fade-in slide-in-from-bottom-1 duration-200">
                        <span class="text-red-500 font-bold">➜</span> 
                        <span class="text-gray-500 text-[10px] mr-2">[{{ date('H:i:s') }}]</span> 
                        <span class="text-gray-200">{{ $log }}</span>
                    </div>
                @endforeach

                @if($isSyncing)
                    <div class="flex items-center gap-2 mt-4 text-[#FFA657]">
                        <span class="animate-pulse font-bold tracking-tighter">OVERWRITING</span>
                        <div class="flex gap-1.5">
                            <span class="w-1 h-4 bg-[#FFA657] animate-[bounce_1s_infinite_0ms]"></span>
                            <span class="w-1 h-4 bg-[#FFA657] animate-[bounce_1s_infinite_200ms]"></span>
                            <span class="w-1 h-4 bg-[#FFA657] animate-[bounce_1s_infinite_400ms]"></span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <style>
            .custom-scrollbar::-webkit-scrollbar { width: 6px; }
            .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.1); }
            .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
            .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
        </style>

        <div class="mt-4">
            <div class="flex justify-between text-xs mb-1 text-gray-500">
                <span>Progress Mendalam</span>
                <span>{{ $currentIndex }} / {{ $totalLines }} ({{ $totalLines > 0 ? round(($currentIndex / $totalLines) * 100) : 0 }}%)</span>
            </div>
            <div class="w-full bg-gray-900 rounded-full h-3 overflow-hidden">
                <div 
                    class="bg-red-600 h-full transition-all duration-300 ease-out shadow-[0_0_10px_rgba(220,38,38,0.5)]" 
                    style="width: {{ $totalLines > 0 ? ($currentIndex / $totalLines) * 100 : 0 }}%"
                ></div>
            </div>
        </div>
    </x-filament::section>

    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.on('start-deep-processing', () => {
                @this.processNext();
            });

            @this.on('process-deep-next', () => {
                setTimeout(() => {
                    @this.processNext();
                }, 50);
            });

            @this.on('refresh-table', () => {
                // Filament tables will automatically refresh if wired properly, or we can force Livewire to refresh
                // by leaving it empty since the table state handles fetching on render.
            });
        });
    </script>
</x-filament-panels::page>
