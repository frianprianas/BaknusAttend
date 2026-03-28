<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('izin_guru_tus', function (Blueprint $table) {
            $table->id();
            $table->string('nipy'); // identifier Guru/TU
            $table->date('tanggal'); // tanggal izin/sakit berlaku
            $table->enum('tipe', ['Izin', 'Sakit']); // tipe permintaan
            $table->text('alasan')->nullable(); // alasan izin/sakit
            $table->string('bukti')->nullable(); // path file bukti (surat, dll)
            $table->enum('status', ['Diajukan', 'Disetujui', 'Ditolak', 'Dibatalkan'])->default('Diajukan');
            $table->timestamps();

            $table->index(['nipy', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izin_guru_tus');
    }
};
