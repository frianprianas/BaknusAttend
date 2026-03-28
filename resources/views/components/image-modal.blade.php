<div class="flex justify-center">
    @if($imageUrl)
        <img src="{{ $imageUrl }}" alt="Foto Presensi" class="rounded-xl shadow-lg max-w-full h-auto max-h-[70vh]">
    @else
        <div class="text-center p-6 bg-gray-100 rounded-xl text-gray-500">
            <x-heroicon-o-camera class="w-12 h-12 mx-auto mb-2 text-gray-400"/>
            <p>Foto tidak tersedia (Hanya tap RFID)</p>
        </div>
    @endif
</div>
