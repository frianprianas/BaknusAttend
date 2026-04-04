<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit" icon="heroicon-m-plus-circle">
                Unggah Musik
            </x-filament::button>
        </div>
    </form>

    <div class="mt-8">
        <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
            <x-filament::icon icon="heroicon-o-list-bullet" class="h-5 w-5" />
            Koleksi Musik Saat Ini
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($musicFiles as $music)
                <div class="bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-xl p-4 shadow-sm flex flex-col gap-3">
                    <div class="flex items-start justify-between">
                        <div class="overflow-hidden">
                            <p class="font-semibold text-sm truncate" title="{{ $music['name'] }}">
                                {{ str_replace('_', ' ', pathinfo($music['name'], PATHINFO_FILENAME)) }}
                            </p>
                            <p class="text-xs text-gray-400 capitalize">
                                MP3 &bull; {{ $music['size'] }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <audio controls class="w-full h-8 scale-90 -ml-4">
                            <source src="{{ $music['url'] }}?v={{ time() }}" type="audio/mpeg">
                            Browser anda tidak mendukung player audio.
                        </audio>
                        
                        <x-filament::button 
                            color="danger" 
                            size="sm" 
                            icon="heroicon-m-trash"
                            outlined
                            wire:click="deleteMusic('{{ $music['name'] }}')"
                            wire:confirm="Hapus file ini? User tidak akan bisa memakainya lagi sebagai backsound."
                        >
                            Hapus Permanen
                        </x-filament::button>
                    </div>
                </div>
            @empty
                <div class="col-span-full border-2 border-dashed rounded-2xl p-12 text-center text-gray-400">
                    <x-filament::icon icon="heroicon-o-musical-note" class="h-12 w-12 mx-auto mb-3 opacity-20" />
                    Belum ada musik diunggah. Silakan upload file MP3 untuk memulai.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
