<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Indeks di Tabel Students (Pencarian Kartu RFID paling krusial!)
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'rfid')) {
                $table->index('rfid');
            }
            if (Schema::hasColumn('students', 'nis')) {
                $table->index('nis');
            }
        });

        // 2. Indeks di Tabel Users (Guru/TU)
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'rfid')) {
                $table->index('rfid');
            }
            if (Schema::hasColumn('users', 'nipy')) {
                $table->index('nipy');
            }
        });

        // 3. Indeks di Tabel Kehadiran (Kecepatan tulis data harian)
        Schema::table('kehadiran_siswas', function (Blueprint $table) {
            $table->index('nis');
            $table->index('waktu_tap');
        });

        Schema::table('kehadiran_guru_tus', function (Blueprint $table) {
            $table->index('nipy');
            $table->index('waktu_tap');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['rfid']);
            $table->dropIndex(['nis']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['rfid']);
            $table->dropIndex(['nipy']);
        });

        Schema::table('kehadiran_siswas', function (Blueprint $table) {
            $table->dropIndex(['nis', 'waktu_tap']);
        });

        Schema::table('kehadiran_guru_tus', function (Blueprint $table) {
            $table->dropIndex(['nipy', 'waktu_tap']);
        });
    }
};
