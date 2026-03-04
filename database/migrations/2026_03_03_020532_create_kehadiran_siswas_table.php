<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kehadiran_siswas', function (Blueprint $table) {
            $table->id();
            $table->string('nis');
            $table->string('rfid_uid')->nullable();
            $table->dateTime('waktu_tap');
            $table->string('status'); // Hadir, Izin, Sakit, Alpa, Terlambat dll
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kehadiran_siswas');
    }
};
