<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClassRoom;
use App\Models\User;

class SetupWaliKelas extends Command
{
    protected $signature = 'app:setup-wali-kelas {--wa= : Nomor WA untuk diisi ke wali kelas} {--auto : Otomatis pasangkan guru ke kelas yang belum memiliki wali kelas}';
    protected $description = 'Otomatis pasangkan guru ke kelas dan atur nomor WhatsApp Wali Kelas';

    public function handle()
    {
        $classes = ClassRoom::orderBy('kelas')->get();
        if ($classes->isEmpty()) {
            $this->warn('Belum ada kelas di database.');
            return 0;
        }

        $teachers = User::whereIn('role', ['Guru', 'TU'])
            ->where('role', '!=', 'Test')
            ->orderBy('name')
            ->get();

        if ($teachers->isEmpty()) {
            $this->error('Belum ada data Guru/TU di database.');
            return 1;
        }

        $auto = $this->option('auto');
        $wa = $this->option('wa');

        // Jika tidak ada argumen CLI, jalankan secara otomatis atau tanya
        if (!$auto && !$this->input->isInteractive()) {
            $auto = true;
        } elseif (!$auto) {
            $auto = $this->confirm('Apakah Anda ingin otomatis memasangkan Dewan Guru sebagai Wali Kelas untuk kelas-kelas yang masih kosong?', true);
        }

        $teacherIndex = 0;
        $totalTeachers = $teachers->count();
        $updated = 0;

        foreach ($classes as $class) {
            if ($auto && (empty($class->nipy) || $class->nipy === '-')) {
                $teacher = $teachers[$teacherIndex % $totalTeachers];
                $class->nipy = $teacher->nipy ?: $teacher->email;
                $teacherIndex++;
            }

            if (!empty($wa)) {
                $class->wa_wali_kelas = $wa;
            }

            $class->is_wa_active = true;
            $class->save();
            $updated++;
        }

        $this->info("Berhasil memperbarui data {$updated} kelas!");

        // Tampilkan tabel hasil rekap
        $rows = [];
        foreach (ClassRoom::orderBy('kelas')->get() as $c) {
            $u = User::where('nipy', $c->nipy)->orWhere('email', $c->nipy)->first();
            $rows[] = [
                'ID'         => $c->id,
                'Kelas'      => $c->kelas,
                'Wali Kelas' => $u ? $u->name : ($c->nipy ?: '(Belum Diset)'),
                'No. WA'     => $c->wa_wali_kelas ?: '-',
                'WA Aktif'   => $c->is_wa_active ? '✅ Aktif' : '❌ Nonaktif',
            ];
        }
        $this->table(['ID', 'Kelas', 'Wali Kelas', 'No. WA', 'Status WA'], $rows);

        $this->info("Sekarang Anda bisa menjalankan pengiriman: php artisan app:send-wali-kelas-attendance-wa --force");
        return 0;
    }
}
