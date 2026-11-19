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
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->boolean('is_wa_active')->default(true)->after('wkm');
            $table->string('wa_wali_kelas')->nullable()->after('is_wa_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropColumn(['is_wa_active', 'wa_wali_kelas']);
        });
    }
};
