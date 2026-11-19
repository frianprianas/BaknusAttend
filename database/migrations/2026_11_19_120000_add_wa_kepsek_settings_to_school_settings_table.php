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
            $table->boolean('is_wa_kepsek_active')->default(true)->after('reminder_pulang');
            $table->time('wa_kepsek_time')->default('10:30:00')->after('is_wa_kepsek_active');
            $table->text('wa_kepsek_numbers')->nullable()->after('wa_kepsek_time');
            $table->string('fonnte_token')->nullable()->after('wa_kepsek_numbers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn([
                'is_wa_kepsek_active',
                'wa_kepsek_time',
                'wa_kepsek_numbers',
                'fonnte_token',
            ]);
        });
    }
};
