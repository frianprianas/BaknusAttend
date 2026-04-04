<div class="flex items-center gap-2 p-1 rounded-lg border w-full max-w-[140px] {{ $isMasuk ? 'bg-success-50/50 border-success-100' : 'bg-amber-50/50 border-amber-100' }}">
    @php
        $data = $getSessionData($getRecord());
    @endphp

    @if($data)
        @php
            $jam = \Illuminate\Support\Carbon::parse($data->waktu_tap)->format('H:i');
            $photoUrl = $data->photo ? asset('storage/' . $data->photo) : url('/images/user-placeholder.png');
        @endphp
        
        <button 
            type="button"
            wire:click="mountTableAction('view_photo', {id: '{{ $data->id }}'})"
            class="flex-shrink-0 group relative overflow-hidden rounded-lg shadow-sm hover:scale-105 transition-transform"
        >
            <img src="{{ $photoUrl }}" class="w-10 h-10 object-cover ring-2 ring-white" />
            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                <x-heroicon-m-magnifying-glass-plus class="w-4 h-4 text-white" />
            </div>
        </button>
        
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
