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
            $table->boolean('is_reminder_active')->default(true)->after('radius');
            $table->time('reminder_masuk')->default('08:00:00')->after('is_reminder_active');
            $table->time('reminder_pulang')->default('15:00:00')->after('reminder_masuk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn(['is_reminder_active', 'reminder_masuk', 'reminder_pulang']);
        });
    }
};
