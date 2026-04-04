<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timelapse_musics', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('filename');
            $table->string('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timelapse_musics');
    }
};
