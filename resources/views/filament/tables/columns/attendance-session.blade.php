<div class="flex items-center gap-2 p-1 rounded-lg border w-full max-w-[155px] {{ $isMasuk ? 'bg-success-50/50 border-success-100 dark:bg-success-950/20 dark:border-success-900/30' : 'bg-amber-50/50 border-amber-100 dark:bg-amber-950/20 dark:border-amber-900/30' }}">
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
            $ketLower = strtolower($data->keterangan ?? '');
            $isBle = str_contains($ketLower, 'bluetooth') || str_contains($ketLower, 'ble');
            $isNfcHp = str_contains($ketLower, 'nfc') || str_contains($ketLower, 'hp') || str_contains($ketLower, 'smartphone');
            $isRfid = str_contains($ketLower, 'rfid');
            $hasRealPhoto = !empty($data->photo) && $data->photo !== 'rfid_placeholder' && file_exists(public_path('storage/' . $data->photo));
            
            if ($isBle) {
                $photoUrl = asset('images/ble_placeholder.png');
                $badgeLabel = 'BLE';
                $badgeClass = 'bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-900/50 dark:text-sky-300 dark:border-sky-700';
                $badgeTitle = 'Presensi via Bluetooth BLE (' . ($data->keterangan ?? 'Wemos ESP32') . ')';
            } elseif ($hasRealPhoto) {
                $photoUrl = asset('storage/' . $data->photo);
                $badgeLabel = 'SELFIE';
                $badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-900/50 dark:text-emerald-300 dark:border-emerald-700';
                $badgeTitle = 'Presensi via Selfie GPS';
            } elseif ($isNfcHp) {
                $photoUrl = asset('images/rfid_placeholder.png');
                $badgeLabel = 'NFC HP';
                $badgeClass = 'bg-teal-100 text-teal-800 border-teal-300 dark:bg-teal-900/50 dark:text-teal-300 dark:border-teal-700';
                $badgeTitle = 'Presensi via NFC Smartphone (HP)';
            } elseif ($isRfid) {
                $photoUrl = asset('images/rfid_placeholder.png');
                $badgeLabel = 'RFID';
                $badgeClass = 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900/50 dark:text-blue-300 dark:border-blue-700';
                $badgeTitle = 'Presensi via Mesin RFID Fisik';
            } else {
                $photoUrl = $data->photo ? asset('storage/' . $data->photo) : asset('images/logo_BG.png');
                $badgeLabel = 'MANUAL';
                $badgeClass = 'bg-gray-100 text-gray-800 border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700';
                $badgeTitle = $data->keterangan ?? 'Manual';
            }
        @endphp
        
        <div x-data="{ open: false }" class="flex-shrink-0">
            <!-- Tombol Pemicu -->
            <button 
                type="button"
                @click="open = true"
                class="flex-shrink-0 group relative overflow-hidden rounded-lg shadow-sm hover:scale-105 transition-transform"
                title="{{ $badgeTitle }}"
            >
                <img src="{{ $photoUrl }}" class="w-10 h-10 object-cover ring-2 {{ $isBle ? 'ring-sky-400' : ($hasRealPhoto ? 'ring-emerald-400' : 'ring-indigo-400') }}" />
                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                    <x-heroicon-m-magnifying-glass-plus class="w-4 h-4 text-white" />
                </div>
            </button>

            <!-- Modal Teleport -->
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
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-6 bg-black/90 backdrop-blur-md"
                    @keydown.escape.window="open = false"
                >
                    <!-- Container Gambar Proporsional -->
                    <div 
                        class="relative w-full max-w-sm" 
                        @click.away="open = false"
                    >
                        <img 
                            src="{{ $photoUrl }}" 
                            class="w-full rounded-2xl shadow-2xl border-[5px] border-white object-cover aspect-[3/4] shadow-black/50"
                        />
                        
                        <!-- Tombol Close Melayang di Kanan Atas -->
                        <button 
                            @click="open = false" 
                            class="absolute -top-4 -right-4 bg-red-500 hover:bg-red-600 text-white p-2 rounded-full shadow-lg transition-transform hover:scale-110"
                        >
                            <x-heroicon-o-x-mark class="w-6 h-6 stroke-[3px]" />
                        </button>

                        <div class="absolute bottom-4 left-0 right-0 text-center px-4">
                             <div class="inline-block bg-black/60 backdrop-blur-md text-white px-4 py-1.5 rounded-full text-xs font-bold border border-white/20 shadow-lg">
                                Sesi {{ $label }} - {{ $jam }} ({{ $badgeLabel }})
                             </div>
                             @if(!empty($data->keterangan))
                             <div class="mt-1 text-[11px] text-gray-200 bg-black/40 px-3 py-1 rounded-lg backdrop-blur-sm">
                                {{ $data->keterangan }}
                             </div>
                             @endif
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        <div class="flex flex-col text-left">
            <span class="text-xs font-bold leading-none {{ $isMasuk ? 'text-success-700 dark:text-success-400' : 'text-amber-700 dark:text-amber-400' }}">{{ $jam }}</span>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-[8px] uppercase font-bold tracking-tight {{ $isMasuk ? 'text-success-600 dark:text-success-400/90' : 'text-amber-600 dark:text-amber-400/90' }}">
                    {{ $label }}
                </span>
                <span class="text-[7.5px] font-black px-1 py-0.5 rounded border {{ $badgeClass }} leading-none whitespace-nowrap" title="{{ $badgeTitle }}">
                    {{ $badgeLabel }}
                </span>
            </div>
        </div>
    @else
        <div class="text-[10px] text-gray-300 italic px-2">--- No Data</div>
    @endif
</div>
