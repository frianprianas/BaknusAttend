<x-filament-panels::page>
    <div class="space-y-6">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Pilih Foto Kenangan</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Pilih hingga 20 foto dari presensi Anda. Kami akan merangkainya menjadi sebuah video kilas balik berdurasi pendek yang menarik.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        wire:click="selectAllTampil" 
                        class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-bold transition-all shadow-sm active:scale-95"
                    >
                        Pilih 20 Terbaru
                    </button>
                    <button 
                        wire:click="generateVideo" 
                        wire:loading.attr="disabled"
                        @if(count($selectedPhotos) < 3) disabled @endif
                        class="flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition-all shadow-md active:scale-95 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="generateVideo">🎥 BUAT VIDEO ({{ count($selectedPhotos) }}/20)</span>
                        <span wire:loading wire:target="generateVideo" class="flex items-center gap-2">
                             <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                             MENYUSUN...
                        </span>
                    </button>
                </div>
            </div>

            @if(empty($recentPhotos))
                <div class="p-8 text-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 dark:bg-gray-800/50 dark:border-gray-700">
                    <div class="mx-auto w-12 h-12 text-gray-400 mb-2">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">Belum ada foto presensi fisik yang tersimpan untuk membuat video.</p>
                </div>
            @else
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-2 sm:gap-3">
                    @foreach($recentPhotos as $photo)
                        @php
                            $pathAsli = $photo['path'];
                            $isSelected = in_array($pathAsli, $selectedPhotos);
                            // Amankan path pembacaan gambar UI
                            $imgSrc = str_starts_with($pathAsli, 'absensi-selfie') ? asset('storage/' . $pathAsli) : asset('storage/absensi-selfie/' . $pathAsli);
                        @endphp
                        
                        <div 
                            wire:click="togglePhoto('{{ $pathAsli }}')"
                            class="relative group rounded-lg sm:rounded-xl overflow-hidden cursor-pointer transition-all duration-200 border-2 sm:border-[3px] {{ $isSelected ? 'border-primary-500 scale-[1.02] shadow-lg ring-2 ring-primary-500/20' : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600' }}"
                        >
                            <img src="{{ $imgSrc }}" alt="Presensi" class="w-full aspect-square object-cover bg-gray-100 dark:bg-gray-800 transition-transform duration-300 {{ $isSelected ? 'scale-110 opacity-75' : 'group-hover:scale-105 opacity-100' }}" loading="lazy">
                            
                            {{-- Overlay Gradien dari bawah u/ tulisan tanggal --}}
                            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-gray-900/90 via-gray-900/50 to-transparent pt-8 pb-1 sm:pb-2 px-1 sm:px-2 z-10 transition-opacity {{ $isSelected ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }}">
                                <p class="text-white text-[0.55rem] sm:text-[0.65rem] font-medium text-center truncate drop-shadow-md">{{ $photo['date'] }}</p>
                            </div>

                            {{-- Overlay Gelap saat Terpilih --}}
                            @if($isSelected)
                                <div class="absolute inset-0 bg-primary-500/20 z-10"></div>
                            @endif

                            {{-- Checkmark Icon (Lebih Celas) --}}
                            @if($isSelected)
                                <div class="absolute top-1 sm:top-2 right-1 sm:right-2 bg-primary-600 text-white rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center shadow-md border focus:border-white z-20 animate-in zoom-in-50 duration-200">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('video-ready', event => {
            const url = event.detail[0].url;
            // Pancing download otomatis via javascript tag a
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Kenangan_Presensi_Saya.mp4';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            // Opsional: confetti atau visual feedback
        });
    </script>
</x-filament-panels::page>
