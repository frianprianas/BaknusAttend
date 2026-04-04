<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->boolean('is_ip_validation_active')->default(false)->after('radius');
            $table->string('allowed_ip_1')->nullable()->after('is_ip_validation_active');
            $table->string('allowed_ip_2')->nullable()->after('allowed_ip_1');
            $table->string('allowed_ip_3')->nullable()->after('allowed_ip_2');
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn(['is_ip_validation_active', 'allowed_ip_1', 'allowed_ip_2', 'allowed_ip_3']);
        });
    }
};
