<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('school_settings', 'slide_show_guru')) {
                $table->boolean('slide_show_guru')->default(true);
            }
            if (!Schema::hasColumn('school_settings', 'slide_show_tu')) {
                $table->boolean('slide_show_tu')->default(true);
            }
            if (!Schema::hasColumn('school_settings', 'slide_show_kelas')) {
                $table->boolean('slide_show_kelas')->default(true);
            }
            if (!Schema::hasColumn('school_settings', 'slide_min_students')) {
                $table->integer('slide_min_students')->default(6);
            }
            if (!Schema::hasColumn('school_settings', 'slide_excluded_roles')) {
                $table->string('slide_excluded_roles')->default('Test')->nullable();
            }
            if (!Schema::hasColumn('school_settings', 'slide_duration')) {
                $table->integer('slide_duration')->default(6);
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn([
                'slide_show_guru',
                'slide_show_tu',
                'slide_show_kelas',
                'slide_min_students',
                'slide_excluded_roles',
                'slide_duration',
            ]);
        });
    }
};
