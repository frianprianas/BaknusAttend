<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\ProgramStudi;
use Illuminate\Support\Facades\Hash;

class SyncClassXiCsv extends Command
{
    protected $signature = 'app:sync-class-xi-csv {filename=kls_XI.csv}';
    protected $description = 'Sync student data from a CSV file to local DB';

    public function handle()
    {
        $filename = $this->argument('filename');
        $filePath = base_path($filename);

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan di path: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Membaca file CSV dari {$filePath}...");

        $file = fopen($filePath, 'r');
        if (!$file) {
            $this->error("Gagal membuka file CSV.");
            return Command::FAILURE;
        }

        // Read header
        $header = fgetcsv($file, 0, ';');
        if (!$header || count($header) < 3) {
            $this->error("Format header CSV tidak valid. Harus mengandung minimal Nama;Kelas;EMAIL");
            fclose($file);
            return Command::FAILURE;
        }

        // Map column indices
        $headerMap = array_flip(array_map('trim', $header));
        // Try case-insensitive header mapping
        $emailIdx = $headerMap['EMAIL'] ?? $headerMap['Email'] ?? $headerMap['email'] ?? null;
        $nameIdx = $headerMap['Nama'] ?? $headerMap['NAMA'] ?? $headerMap['name'] ?? null;
        $kelasIdx = $headerMap['Kelas'] ?? $headerMap['KELAS'] ?? $headerMap['kelas'] ?? null;
        $nisIdx = $headerMap['NIS'] ?? $headerMap['nis'] ?? null;
        $passwordIdx = $headerMap['PASSWORD'] ?? $headerMap['Password'] ?? $headerMap['password'] ?? null;

        if (is_null($emailIdx) || is_null($nameIdx) || is_null($kelasIdx)) {
            $this->error("Kolom yang diperlukan (Nama, Kelas, EMAIL) tidak lengkap di CSV.");
            fclose($file);
            return Command::FAILURE;
        }

        $studentCount = 0;
        $classroomCount = 0;
        $prodiCount = 0;
        $userCount = 0;

        $processedNises = [];

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            if (empty($row) || count($row) < 3) {
                continue;
            }

            $email = isset($row[$emailIdx]) ? trim($row[$emailIdx]) : '';
            $name = isset($row[$nameIdx]) ? trim($row[$nameIdx]) : '';
            $kelasName = isset($row[$kelasIdx]) ? trim($row[$kelasIdx]) : '';
            $nis = !is_null($nisIdx) && isset($row[$nisIdx]) ? trim($row[$nisIdx]) : explode('@', $email)[0];
            $password = !is_null($passwordIdx) && isset($row[$passwordIdx]) ? trim($row[$passwordIdx]) : null;

            if (empty($nis) || empty($name) || empty($kelasName) || empty($email)) {
                continue;
            }

            // Clean & normalize ProgramStudi name from ClassRoom name
            // XII PPLG 1 -> PPLG, XI AKT -> AKT, XI Animasi -> Animasi, XI DKV -> DKV
            // Ganti RPL jadi PPLG jika ada
            $kelasName = preg_replace('/\bRPL\b/i', 'PPLG', $kelasName);
            
            $prodiName = $kelasName;
            // Remove grade prefix "XI" / "X" / "XII" and spaces
            $prodiName = preg_replace('/^(X|XI|XII)\s+/i', '', $prodiName);
            // Remove trailing numbers (like " 1", " 2", etc.)
            $prodiName = preg_replace('/\s+\d+$/', '', $prodiName);
            $prodiName = trim($prodiName);
            if (strcasecmp($prodiName, 'RPL') === 0) {
                $prodiName = 'PPLG';
            }

            // 1. ProgramStudi (First or Create)
            $prodi = ProgramStudi::where('program_studi', $prodiName)->first();
            if (!$prodi) {
                $prodi = ProgramStudi::create(['program_studi' => $prodiName]);
                $prodiCount++;
            }

            // 2. ClassRoom (First or Create)
            $classRoom = ClassRoom::where('kelas', $kelasName)->first();
            if (!$classRoom) {
                $classRoom = ClassRoom::create([
                    'kelas' => $kelasName,
                    'id_prodi' => $prodi->id_prodi,
                ]);
                $classroomCount++;
            }

            // 3. User (Update or Create)
            $user = User::where('email', $email)->first();
            if (!$user) {
                // Ignore password column, generate a secure random password for new users
                $userPass = Hash::make(\Illuminate\Support\Str::random(16));
                $user = User::create([
                    'email' => $email,
                    'name' => $name,
                    'role' => 'Siswa',
                    'password' => $userPass,
                ]);
                $userCount++;
            } else {
                // If it already exists, update name and role (keep existing password untouched)
                $updateData = [
                    'name' => $name,
                    'role' => 'Siswa',
                ];
                $user->update($updateData);
            }

            // 4. Student (Update or Create)
            $student = Student::where('nis', $nis)->first();
            if (!$student) {
                Student::create([
                    'nis' => $nis,
                    'name' => $name,
                    'class_room_id' => $classRoom->id,
                ]);
                $studentCount++;
            } else {
                $student->update([
                    'name' => $name,
                    'class_room_id' => $classRoom->id,
                ]);
            }

            $processedNises[] = $nis;
        }

        fclose($file);

        $this->info("Sinkronisasi selesai!");
        $this->line("-----------------------------------");
        $this->info("Program Studi Baru : {$prodiCount}");
        $this->info("Kelas Baru         : {$classroomCount}");
        $this->info("User Baru          : {$userCount}");
        $this->info("Siswa Baru         : {$studentCount}");
        $this->info("Total Baris CSV    : " . count($processedNises));
        $this->line("-----------------------------------");

        return Command::SUCCESS;
    }
}
