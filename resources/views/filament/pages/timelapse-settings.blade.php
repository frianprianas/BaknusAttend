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
                            <p class="font-bold text-sm truncate" title="{{ $music['title'] }}">
                                {{ $music['title'] }}
                            </p>
                            <p class="text-[10px] text-gray-400 font-mono">
                                {{ $music['filename'] }} &bull; {{ $music['size'] }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <audio controls class="w-full h-8 scale-90 -ml-4">
                            <source src="{{ asset('timelapse_music/' . $music['filename']) }}?v={{ time() }}" type="audio/mpeg">
                        </audio>
                        
                        <div class="flex gap-2">
                            <x-filament::button 
                                color="gray" 
                                size="sm" 
                                icon="heroicon-m-pencil-square"
                                class="flex-1"
                                wire:click="editTitle({{ $music['id'] }})"
                            >
                                Edit Judul
                            </x-filament::button>

                            <x-filament::button 
                                color="danger" 
                                size="sm" 
                                icon="heroicon-m-trash"
                                outlined
                                wire:click="deleteMusic({{ $music['id'] }})"
                                wire:confirm="Hapus file ini?"
                            >
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full border-2 border-dashed rounded-2xl p-12 text-center text-gray-400">
                    <x-filament::icon icon="heroicon-o-musical-note" class="h-12 w-12 mx-auto mb-3 opacity-20" />
                    Belum ada musik diunggah.
                </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL EDIT JUDUL --}}
    <x-filament::modal id="edit-title-modal" width="md" display-classes="block">
        <x-slot name="heading">
            Edit Judul Musik
        </x-slot>

        <div class="py-4">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model="editingTitle"
                    placeholder="Masukkan judul baru..."
                />
            </x-filament::input.wrapper>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-filament::button color="gray" @click="close">
                    Batal
                </x-filament::button>
                <x-filament::button wire:click="updateTitle" color="primary">
                    Simpan Perubahan
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
