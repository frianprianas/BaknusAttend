<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\ProgramStudi;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SyncClassXRfid extends Command
{
    protected $signature = 'app:sync-class-x-rfid {filename=kls_X_rfid.csv} {--force : Timpa RFID jika sudah ada}';
    protected $description = 'Sinkronisasi data siswa kelas X beserta kode RFID dan kelas dari file CSV ke database';

    public function handle()
    {
        $filename = $this->argument('filename');
        $force = $this->option('force');
        $filePath = base_path($filename);

        if (!file_exists($filePath)) {
            $this->error("File CSV tidak ditemukan di path: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("=======================================================");
        $this->info(" SINKRONISASI DATA SISWA & RFID KELAS X");
        $this->info(" File Sumber: {$filePath}");
        $this->info(" Mode Force : " . ($force ? "YA (Timpa RFID)" : "TIDAK (Pertahankan yang sudah ada)"));
        $this->info("=======================================================\n");

        $file = fopen($filePath, 'r');
        if (!$file) {
            $this->error("Gagal membuka file CSV.");
            return Command::FAILURE;
        }

        // Baca baris pertama (Header)
        $firstLine = fgets($file);
        if (!$firstLine) {
            $this->error("File CSV kosong.");
            fclose($file);
            return Command::FAILURE;
        }

        // Deteksi delimiter (; atau ,)
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        $header = str_getcsv(trim($firstLine), $delimiter);

        $headerMap = array_flip(array_map(function($h) {
            return strtoupper(trim($h));
        }, $header));

        $nisIdx    = $headerMap['NIS'] ?? null;
        $nameIdx   = $headerMap['NAMA'] ?? null;
        $kelasIdx  = $headerMap['KELAS'] ?? null;
        $emailIdx  = $headerMap['EMAIL'] ?? null;
        $rfidIdx   = $headerMap['RFID'] ?? null;
        $statusIdx = $headerMap['STATUS'] ?? null;

        if (is_null($nisIdx) || is_null($nameIdx) || is_null($kelasIdx)) {
            $this->error("Header CSV wajib memiliki kolom minimal: NIS, Nama, Kelas.");
            fclose($file);
            return Command::FAILURE;
        }

        $prodiCreated = 0;
        $classCreated = 0;
        $studentCreated = 0;
        $studentUpdated = 0;
        $rfidUpdated = 0;
        $userCreated = 0;
        $userUpdated = 0;
        $totalRows = 0;

        while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
            if (empty($row) || count($row) < 3) continue;

            $nis      = isset($row[$nisIdx]) ? trim($row[$nisIdx]) : '';
            $name     = isset($row[$nameIdx]) ? trim($row[$nameIdx]) : '';
            $kelas    = isset($row[$kelasIdx]) ? trim($row[$kelasIdx]) : '';
            $email    = !is_null($emailIdx) && isset($row[$emailIdx]) ? trim($row[$emailIdx]) : '';
            $rfid     = !is_null($rfidIdx) && isset($row[$rfidIdx]) ? trim($row[$rfidIdx]) : '';
            $status   = !is_null($statusIdx) && isset($row[$statusIdx]) ? trim($row[$statusIdx]) : '';

            if (empty($nis) && empty($name)) continue;

            if (empty($email) && !empty($nis)) {
                $email = "{$nis}@smk.baktinusantara666.sch.id";
            }

            $rfidValue = (!empty($rfid) && $status !== 'Belum Terdaftar') ? $rfid : null;
            $totalRows++;

            // 1. ProgramStudi
            $prodiName = $kelas;
            $prodiName = preg_replace('/^(X|XI|XII)\s+/i', '', $prodiName);
            $prodiName = preg_replace('/\s+\d+$/', '', $prodiName);
            $prodiName = trim($prodiName);
            if (strcasecmp($prodiName, 'RPL') === 0) {
                $prodiName = 'PPLG';
            }

            $prodi = ProgramStudi::where('program_studi', $prodiName)->first();
            if (!$prodi) {
                $prodi = ProgramStudi::create(['program_studi' => $prodiName]);
                $prodiCreated++;
            }

            // 2. ClassRoom
            $classRoom = ClassRoom::where('kelas', $kelas)->first();
            if (!$classRoom) {
                $classRoom = ClassRoom::create([
                    'kelas' => $kelas,
                    'id_prodi' => $prodi->id_prodi,
                ]);
                $classCreated++;
            }

            // 3. Student (Find by NIS or Name)
            $student = null;
            if (!empty($nis)) {
                $student = Student::where('nis', $nis)->first();
            }
            if (!$student && !empty($name)) {
                $student = Student::where('name', $name)->first();
            }

            if (!$student) {
                $newStudentData = [
                    'nis' => $nis ?: null,
                    'name' => $name,
                    'class_room_id' => $classRoom->id,
                    'rfid' => $rfidValue,
                ];
                Student::create($newStudentData);
                $studentCreated++;
                if (!empty($rfidValue)) $rfidUpdated++;
            } else {
                $updateData = [
                    'name' => $name,
                    'class_room_id' => $classRoom->id,
                ];
                if (!empty($nis)) {
                    $updateData['nis'] = $nis;
                }
                if ($force || empty($student->rfid)) {
                    if (!empty($rfidValue)) {
                        $updateData['rfid'] = $rfidValue;
                        $rfidUpdated++;
                    }
                }
                $student->update($updateData);
                $studentUpdated++;
            }

            // 4. User (Student Account)
            if (!empty($email)) {
                $user = User::where('email', $email)->first();
                if (!$user) {
                    $userData = [
                        'name' => $name,
                        'email' => $email,
                        'nipy' => $nis ?: null,
                        'role' => 'Siswa',
                        'password' => Hash::make(Str::random(16)),
                        'rfid' => $rfidValue,
                    ];
                    User::create($userData);
                    $userCreated++;
                } else {
                    $userUpdate = [
                        'name' => $name,
                        'role' => 'Siswa',
                    ];
                    if (!empty($nis)) {
                        $userUpdate['nipy'] = $nis;
                    }
                    if ($force || empty($user->rfid)) {
                        if (!empty($rfidValue)) {
                            $userUpdate['rfid'] = $rfidValue;
                        }
                    }
                    $user->update($userUpdate);
                    $userUpdated++;
                }
            }
        }

        fclose($file);

        $this->info("-------------------------------------------------------");
        $this->info(" SINKRONISASI SELESAI!");
        $this->info("-------------------------------------------------------");
        $this->line(" Total Baris Data     : {$totalRows}");
        $this->line(" Program Studi Baru   : {$prodiCreated}");
        $this->line(" Kelas Baru           : {$classCreated}");
        $this->line(" Siswa Baru Ditambah  : {$studentCreated}");
        $this->line(" Siswa Diperbarui     : {$studentUpdated}");
        $this->line(" Kartu RFID Terpasang : {$rfidUpdated}");
        $this->line(" Akun User Baru       : {$userCreated}");
        $this->line(" Akun User Diperbarui : {$userUpdated}");
        $this->info("-------------------------------------------------------");

        return Command::SUCCESS;
    }
}
