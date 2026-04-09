<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <x-filament::section>
                <x-slot name="heading">
                    Konfigurasi Singkron
                </x-slot>

                <form wire:submit.prevent="startSync">
                    {{ $this->form }}

                    <div class="mt-4">
                        <x-filament::button 
                            type="submit" 
                            size="lg" 
                            class="w-full"
                            wire:loading.attr="disabled"
                            icon="heroicon-o-arrow-path"
                            color="primary"
                        >
                            <span wire:loading.remove wire:target="startSync">Mulai Singkronisasi</span>
                            <span wire:loading wire:target="startSync">Menyiapkan...</span>
                        </x-filament::button>
                    </div>
                </form>

                <div class="mt-4 text-xs text-gray-500 italic">
                    * Format CSV: nama,kelas (Delimiter bisa , atau ; secara otomatis dideteksi)
                </div>

                @if(!$isSyncing && $report['total'] > 0)
                <div class="mt-6 p-4 bg-green-50 dark:bg-green-950/20 rounded-lg border border-green-200 dark:border-green-800">
                    <h3 class="text-lg font-bold mb-2 text-green-800 dark:text-green-300">Laporan Singkronisasi</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Total Baris:</span>
                            <span class="font-mono font-bold">{{ $report['total'] }}</span>
                        </div>
                        <div class="flex justify-between text-green-700 dark:text-green-400">
                            <span>Berhasil:</span>
                            <span class="font-mono font-bold">{{ $report['success'] }}</span>
                        </div>
                        <div class="flex justify-between text-red-700 dark:text-red-400">
                            <span>Gagal:</span>
                            <span class="font-mono font-bold">{{ $report['failed'] }}</span>
                        </div>
                        <div class="pt-2 border-t border-green-200 dark:border-green-800 flex justify-between text-xs opacity-75">
                            <span>Status:</span>
                            <span class="font-bold">SELESAI</span>
                        </div>
                    </div>
                </div>
                @endif
            </x-filament::section>
        </div>

        <div class="md:col-span-2">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span class="ml-2 font-mono text-sm">terminal@baknus-attend:~/sync</span>
                    </div>
                </x-slot>

                <div 
                    id="terminal-window"
                    class="bg-[#0D1117] text-[#58A6FF] font-mono p-5 rounded-xl h-[480px] overflow-y-auto border border-gray-800 shadow-[0_20px_50px_rgba(0,0,0,0.5)] relative custom-scrollbar"
                    x-data="{ 
                        scrollToBottom() { 
                            this.$el.scrollTop = this.$el.scrollHeight 
                        } 
                    }"
                    x-init="scrollToBottom(); $watch('logs', () => scrollToBottom())"
                >
                    <div class="absolute top-0 left-0 right-0 h-8 bg-gray-800/50 backdrop-blur-md flex items-center px-4 gap-1.5 border-b border-white/5">
                        <div class="w-2.5 h-2.5 bg-[#FF5F56] rounded-full"></div>
                        <div class="w-2.5 h-2.5 bg-[#FFBD2E] rounded-full"></div>
                        <div class="w-2.5 h-2.5 bg-[#27C93F] rounded-full"></div>
                        <span class="ml-2 text-[10px] text-gray-400 uppercase tracking-widest font-bold">Bash — Sync Output</span>
                    </div>

                    <div class="pt-8">
                        @if(empty($logs))
                            <div class="text-gray-600 italic">
                                <span class="text-green-500">guest@baknus</span>:<span class="text-blue-500">~</span>$ ./start-sync.sh<br>
                                [SYSTEM] Menunggu input file CSV...
                            </div>
                        @endif
                        
                        @foreach($logs as $log)
                            <div class="mb-1 leading-relaxed animate-in fade-in slide-in-from-bottom-1 duration-200">
                                <span class="text-green-500 font-bold">➜</span> 
                                <span class="text-gray-500 text-[10px] mr-2">[{{ date('H:i:s') }}]</span> 
                                <span class="text-white">{{ $log }}</span>
                            </div>
                        @endforeach

                        @if($isSyncing)
                            <div class="flex items-center gap-2 mt-4 text-[#79C0FF]">
                                <span class="animate-pulse font-bold tracking-tighter">WORKING</span>
                                <div class="flex gap-1.5">
                                    <span class="w-1 h-4 bg-[#58A6FF] animate-[bounce_1s_infinite_0ms]"></span>
                                    <span class="w-1 h-4 bg-[#58A6FF] animate-[bounce_1s_infinite_200ms]"></span>
                                    <span class="w-1 h-4 bg-[#58A6FF] animate-[bounce_1s_infinite_400ms]"></span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <style>
                    .custom-scrollbar::-webkit-scrollbar {
                        width: 6px;
                    }
                    .custom-scrollbar::-webkit-scrollbar-track {
                        background: rgba(0, 0, 0, 0.1);
                    }
                    .custom-scrollbar::-webkit-scrollbar-thumb {
                        background: rgba(255, 255, 255, 0.1);
                        border-radius: 10px;
                    }
                    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                        background: rgba(255, 255, 255, 0.2);
                    }
                </style>

                <div class="mt-4">
                    <div class="flex justify-between text-xs mb-1 text-gray-500">
                        <span>Progress Singkronisasi</span>
                        <span>{{ $currentIndex }} / {{ $totalLines }} ({{ $totalLines > 0 ? round(($currentIndex / $totalLines) * 100) : 0 }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div 
                            class="bg-primary-600 h-full transition-all duration-300 ease-out shadow-[0_0_10px_rgba(var(--primary-600),0.5)]" 
                            style="width: {{ $totalLines > 0 ? ($currentIndex / $totalLines) * 100 : 0 }}%"
                        ></div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>

    @script
    <script>
        $wire.on('start-processing', () => {
            $wire.processNext();
        });

        $wire.on('process-next', () => {
            setTimeout(() => {
                $wire.processNext();
            }, 50); // Kecepatan terminal
        });
    </script>
    @endscript
</x-filament-panels::page>
