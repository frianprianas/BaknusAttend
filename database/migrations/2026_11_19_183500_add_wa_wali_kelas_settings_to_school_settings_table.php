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
        Schema::table('school_settings', function (Blueprint $table) {
            $table->boolean('is_wa_wali_kelas_active')->default(true)->after('fonnte_token');
            $table->time('wa_wali_kelas_time')->default('08:00:00')->after('is_wa_wali_kelas_active');
            $table->integer('wa_delay_min_seconds')->default(15)->after('wa_wali_kelas_time');
            $table->integer('wa_delay_max_seconds')->default(40)->after('wa_delay_min_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn([
                'is_wa_wali_kelas_active',
                'wa_wali_kelas_time',
                'wa_delay_min_seconds',
                'wa_delay_max_seconds',
            ]);
        });
    }
};
