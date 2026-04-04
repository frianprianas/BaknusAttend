<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ubah kolom menjadi nullable secara resmi di database
        Schema::table('users', function (Blueprint $table) {
            $table->integer('target_hari_kerja')->nullable()->comment('Target hari kerja guru/TU per bulan')->change();
        });

        // 2. Riset data menjadi NULL
        DB::table('users')->update(['target_hari_kerja' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
