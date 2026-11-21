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
        Schema::table('school_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('school_settings', 'wa_delay_min_minutes')) {
                $table->integer('wa_delay_min_minutes')->default(2)->after('wa_wali_kelas_time')->nullable();
            }
            if (!Schema::hasColumn('school_settings', 'wa_delay_max_minutes')) {
                $table->integer('wa_delay_max_minutes')->default(5)->after('wa_delay_min_minutes')->nullable();
            }
        });

        // Konversi data yang sudah ada dari detik ke menit
        $settings = DB::table('school_settings')->get();
        foreach ($settings as $s) {
            $minSec = $s->wa_delay_min_seconds ?? 15;
            $maxSec = $s->wa_delay_max_seconds ?? 40;
            $minMin = max(1, (int) round($minSec / 60));
            $maxMin = max(2, (int) round($maxSec / 60));
            DB::table('school_settings')->where('id', $s->id)->update([
                'wa_delay_min_minutes' => $minMin,
                'wa_delay_max_minutes' => $maxMin,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            if (Schema::hasColumn('school_settings', 'wa_delay_min_minutes')) {
                $table->dropColumn('wa_delay_min_minutes');
            }
            if (Schema::hasColumn('school_settings', 'wa_delay_max_minutes')) {
                $table->dropColumn('wa_delay_max_minutes');
            }
        });
    }
};
