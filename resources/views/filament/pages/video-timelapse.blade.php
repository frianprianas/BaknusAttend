<x-filament-panels::page>
    <div class="space-y-6 relative" x-data="{ generating: @entangle('isGenerating') }">
        
        {{-- Layar Loading Penuh "BaknusAI" --}}
        <div 
            x-show="generating" 
            x-transition.opacity.duration.500ms
            class="fixed inset-0 z-[9999] bg-slate-900/95 backdrop-blur-md flex flex-col items-center justify-center pointer-events-auto"
            style="display: none;"
        >
            <div class="relative w-24 h-24 mb-8">
                <!-- Outer Spin -->
                <div class="absolute inset-0 rounded-full border-t-4 border-indigo-500 animate-spin"></div>
                <!-- Inner Pulse Glow -->
                <div class="absolute inset-3 rounded-full bg-indigo-500/30 animate-pulse blur-md"></div>
                <!-- Icon Camera/AI -->
                <div class="absolute inset-0 flex items-center justify-center text-indigo-400">
                    <svg class="w-10 h-10 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                </div>
            </div>

            <h3 class="text-3xl font-black text-white mb-3 tracking-tight drop-shadow-lg">Merangkai Kenangan...</h3>
            <p class="text-indigo-300 font-medium animate-pulse text-lg">Memproses foto menjadi video kilas balik Anda</p>
            
            <div class="mt-12 flex items-center gap-3 bg-black/40 px-5 py-2.5 rounded-full border border-indigo-500/20 shadow-[0_0_15px_rgba(99,102,241,0.2)]">
                <span class="text-sm text-gray-400">Ditenagai oleh</span>
                <span class="text-sm text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400 font-black italic tracking-widest">BAKNUS-AI ✨</span>
            </div>
        </div>

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

            @if(count($musicList) > 0)
                <div class="mb-8 p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                    <h3 class="text-sm font-bold text-indigo-900 dark:text-indigo-300 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                        Pilih Backsound Musik (Opsional)
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($musicList as $music)
                            @php
                                $isMusicSelected = $selectedMusic === $music['file'];
                            @endphp
                            <div class="flex flex-col gap-2 p-3 rounded-lg border-2 cursor-pointer transition-all {{ $isMusicSelected ? 'border-indigo-500 bg-white dark:bg-gray-800 shadow-md' : 'border-transparent bg-white/60 dark:bg-gray-800/60 hover:bg-white dark:hover:bg-gray-800 hover:border-gray-300' }}"
                                wire:click="selectMusic('{{ $music['file'] }}')">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate pr-2">{{ $music['title'] }}</span>
                                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 {{ $isMusicSelected ? 'border-indigo-500 bg-indigo-500 text-white' : 'border-gray-400' }}">
                                        @if($isMusicSelected)
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        @endif
                                    </div>
                                </div>
                                <!-- Stop event propagation to prevent triggering wire:click on the parent container when playing audio -->
                                <div x-data x-on:click.stop>
                                    <audio controls class="w-full h-8 max-w-full">
                                        <source src="{{ $music['url'] }}" type="audio/mpeg">
                                        Browser tidak mendukung audio.
                                    </audio>
                                </div>
                            </div>
                        @endforeach
                        
                        {{-- Opsi Tanpa Musik --}}
                        <div class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all {{ !$selectedMusic ? 'border-gray-500 bg-white dark:bg-gray-800 shadow-md' : 'border-transparent bg-white/60 dark:bg-gray-800/60 hover:bg-white dark:hover:bg-gray-800 hover:border-gray-300' }}"
                            wire:click="selectMusic(null)">
                            <div class="w-10 h-8 flex items-center justify-center bg-gray-200 dark:bg-gray-700 rounded-md text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tanpa Musik</span>
                            <div class="ml-auto w-5 h-5 rounded-full border-2 flex items-center justify-center {{ !$selectedMusic ? 'border-gray-600 bg-gray-600 text-white' : 'border-gray-400' }}">
                                @if(!$selectedMusic)
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(empty($recentPhotos))
                <div class="p-8 text-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 dark:bg-gray-800/50 dark:border-gray-700">
                    <div class="mx-auto w-12 h-12 text-gray-400 mb-2">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">Belum ada foto presensi fisik yang tersimpan untuk membuat video.</p>
                </div>
            @else
                <div 
                    class="gap-2 sm:gap-3" 
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));"
                >
                    @foreach($recentPhotos as $photo)
                        @php
                            $pathAsli = $photo['path'];
                            $isSelected = in_array($pathAsli, $selectedPhotos);
                            // Amankan path pembacaan gambar UI
                            $imgSrc = str_starts_with($pathAsli, 'absensi-selfie') ? asset('storage/' . $pathAsli) : asset('storage/absensi-selfie/' . $pathAsli);
                        @endphp
                        
                        <div 
                            wire:click="togglePhoto('{{ $pathAsli }}')"
                            class="relative group rounded-md sm:rounded-xl overflow-hidden cursor-pointer transition-all duration-200 border-2 sm:border-[3px] {{ $isSelected ? 'border-primary-500 scale-[1.05] shadow-md z-10 ring-2 ring-primary-500/30' : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600' }}"
                        >
                            <img src="{{ $imgSrc }}" alt="Presensi" class="w-full aspect-square object-cover bg-gray-100 dark:bg-gray-800 transition-transform duration-300 {{ $isSelected ? 'scale-110 opacity-60' : 'group-hover:scale-105 opacity-100' }}" loading="lazy">
                            
                            {{-- Overlay Gradien dari bawah u/ tulisan tanggal --}}
                            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-gray-950 via-gray-900/60 to-transparent pt-6 pb-1 px-1 z-10 transition-opacity {{ $isSelected ? 'opacity-100' : 'opacity-80' }}">
                                <p class="text-white text-[10px] leading-tight font-bold text-center truncate drop-shadow-md">{{ \Carbon\Carbon::parse($photo['date'])->format('d/m') }}</p>
                            </div>

                            {{-- Menggelapkan latar belakang penuh jika dipilih --}}
                            @if($isSelected)
                                <div class="absolute inset-0 bg-primary-600/20 mix-blend-multiply z-10 pointer-events-none"></div>
                            @endif

                            {{-- Checkmark Icon (Sangat Jelas, Di tengah foto) --}}
                            @if($isSelected)
                                <div class="absolute inset-0 flex items-center justify-center z-20 pointer-events-none animate-in zoom-in-50 duration-200">
                                    <div class="bg-primary-600 border-2 border-white text-white rounded-full w-8 h-8 flex items-center justify-center shadow-xl">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
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
