<div class="flex items-center gap-2 p-1 rounded-lg border w-full max-w-[140px] {{ $isMasuk ? 'bg-success-50/50 border-success-100' : 'bg-amber-50/50 border-amber-100' }}">
    @php
        $record = $getRecord();
        $model = $modelClass;
        $idField = ($model === \App\Models\KehadiranSiswa::class) ? 'nis' : 'nipy';
        
        $query = $model::where($idField, $record->{$idField})
            ->whereDate('waktu_tap', $record->tanggal);

        if ($isMasuk) {
            $data = $query->where('keterangan', 'like', '%Masuk%')->orderBy('waktu_tap', 'asc')->first();
        } else {
            $data = $query->where('keterangan', 'like', '%Pulang%')->orderBy('waktu_tap', 'desc')->first();
        }
    @endphp

    @if($data)
        @php
            $jam = \Illuminate\Support\Carbon::parse($data->waktu_tap)->format('H:i');
            $photoUrl = $data->photo ? asset('storage/' . $data->photo) : url('/images/user-placeholder.png');
        @endphp
        
        <div x-data="{ open: false }" class="flex-shrink-0">
            <!-- Tombol Pemicu -->
            <button 
                type="button"
                @click="open = true"
                class="flex-shrink-0 group relative overflow-hidden rounded-lg shadow-sm hover:scale-105 transition-transform"
            >
                <img src="{{ $photoUrl }}" class="w-10 h-10 object-cover ring-2 ring-white" />
                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                    <x-heroicon-m-magnifying-glass-plus class="w-4 h-4 text-white" />
                </div>
            </button>

            <!-- Modal Teleport (DILEMPAR KE BODY BIAR TIDAK SEGEDE GABAN) -->
            <template x-teleport="body">
                <div 
                    x-show="open" 
                    x-cloak
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/95 backdrop-blur-md"
                    @keydown.escape.window="open = false"
                >
                    <div 
                        class="relative max-w-[90vw] max-h-[90vh] flex flex-col items-center"
                        @click.away="open = false"
                    >
                        <img 
                            src="{{ $photoUrl }}" 
                            class="rounded-xl shadow-2xl border-[6px] border-white object-contain max-w-full max-h-full shadow-white/10"
                            x-transition:enter="transition ease-out duration-300 transform"
                            x-transition:enter-start="scale-90"
                            x-transition:enter-end="scale-100"
                        />
                        <button 
                            @click="open = false" 
                            class="mt-4 bg-white/10 hover:bg-white/20 text-white px-6 py-2 rounded-full border border-white/20 backdrop-blur-sm transition-all text-sm font-bold flex items-center gap-2"
                        >
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                            Tutup
                        </button>
                    </div>
                </div>
            </template>
        </div>
        
        <div class="flex flex-col text-left">
            <span class="text-xs font-bold leading-none {{ $isMasuk ? 'text-success-700' : 'text-amber-700' }}">{{ $jam }}</span>
            <span class="text-[8px] uppercase font-bold tracking-tight {{ $isMasuk ? 'text-success-500/80' : 'text-amber-500/80' }}">
                {{ $label }}
            </span>
        </div>
    @else
        <div class="text-[10px] text-gray-300 italic px-2">--- No Data</div>
    @endif
</div>
