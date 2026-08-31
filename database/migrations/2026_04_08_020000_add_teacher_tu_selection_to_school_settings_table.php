<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('school_settings', 'slide_selected_teacher_ids')) {
                $table->json('slide_selected_teacher_ids')->nullable();
            }
            if (!Schema::hasColumn('school_settings', 'slide_selected_tu_ids')) {
                $table->json('slide_selected_tu_ids')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn(['slide_selected_teacher_ids', 'slide_selected_tu_ids']);
        });
    }
};
