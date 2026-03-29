<div class="flex justify-center mt-4">
    <x-filament::button 
        type="submit" 
        size="lg" 
        wire:loading.attr="disabled"
        class="w-full md:w-auto"
    >
        <span wire:loading.remove>
            {{ $label }}
        </span>
        <span wire:loading flex items-center gap-2>
             <x-filament::loading-indicator class="h-5 w-5" />
             Sedang memproses...
        </span>
    </x-filament::button>
</div>
