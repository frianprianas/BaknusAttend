<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bluetooth_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique()->comment('ID unik alat, contoh: WEMOS_GERBANG_01');
            $table->string('device_name')->comment('Nama lokasi/alat, contoh: Gerbang Depan');
            $table->string('secret_key')->comment('Kunci rahasia HMAC-SHA256 bersama ESP32');
            $table->decimal('latitude', 10, 8)->nullable()->comment('Titik Latitude lokasi alat');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Titik Longitude lokasi alat');
            $table->unsignedInteger('radius_meters')->default(50)->comment('Radius toleransi jarak dalam meter');
            $table->boolean('is_active')->default(true)->comment('Status aktif alat');
            $table->timestamps();
        });

        // Insert data default perangkat Wemos D1 R32 Gerbang Depan
        \Illuminate\Support\Facades\DB::table('bluetooth_devices')->insertOrIgnore([
            'device_id'     => 'WEMOS_GERBANG_01',
            'device_name'   => 'Gerbang Depan',
            'secret_key'    => 'kunciRahasiaBaknus2026!#',
            'radius_meters' => 50,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bluetooth_devices');
    }
};
