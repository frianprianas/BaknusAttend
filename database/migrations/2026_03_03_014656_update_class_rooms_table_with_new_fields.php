<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            // Rename 'name' to 'kelas'
            $table->renameColumn('name', 'kelas');

            // Add other fields
            $table->unsignedBigInteger('id_prodi')->nullable()->after('id');
            $table->foreign('id_prodi')->references('id_prodi')->on('program_studis')->onDelete('set null');

            $table->string('nipy')->nullable();
            $table->string('km')->nullable();
            $table->string('wkm')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropForeign(['id_prodi']);
            $table->dropColumn(['id_prodi', 'nipy', 'km', 'wkm']);
            $table->renameColumn('kelas', 'name');
        });
    }
};
