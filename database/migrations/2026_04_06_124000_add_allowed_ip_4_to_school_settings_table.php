<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->string('allowed_ip_4')->nullable()->after('allowed_ip_3');
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn('allowed_ip_4');
        });
    }
};
