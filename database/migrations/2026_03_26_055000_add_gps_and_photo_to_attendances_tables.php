<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = ['kehadiran_siswas', 'kehadiran_guru_tus'];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('lat', 10, 8)->nullable()->after('status');
                $table->decimal('long', 11, 8)->nullable()->after('lat');
                $table->string('photo')->nullable()->after('long');
            });
        }
    }

    public function down(): void
    {
        $tables = ['kehadiran_siswas', 'kehadiran_guru_tus'];
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['lat', 'long', 'photo']);
            });
        }
    }
};
