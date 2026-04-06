<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = ['kehadiran_siswas', 'kehadiran_guru_tus'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->boolean('is_dinas_luar')->default(false)->after('photo');
                $table->string('lokasi_dinas_luar')->nullable()->after('is_dinas_luar');
            });
        }
    }

    public function down(): void
    {
        $tables = ['kehadiran_siswas', 'kehadiran_guru_tus'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['is_dinas_luar', 'lokasi_dinas_luar']);
            });
        }
    }
};
