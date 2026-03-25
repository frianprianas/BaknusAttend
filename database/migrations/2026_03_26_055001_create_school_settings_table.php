<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('lat', 10, 8)->default(-6.91474440); // Contoh Bandung
            $table->decimal('long', 11, 8)->default(107.60981110);
            $table->integer('radius')->default(30); // in meters
            $table->timestamps();
        });

        // Seed initial setting
        \Illuminate\Support\Facades\DB::table('school_settings')->insert([
            'name' => 'Lokasi Utama SMK Baknus 666',
            'lat' => -6.91474440,
            'long' => 107.60981110,
            'radius' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
