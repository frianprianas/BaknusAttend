<?php

namespace App\Services;

use App\Models\KehadiranGuruTu;
use App\Models\KehadiranSiswa;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class VideoTimelapseService
{
    /**
     * Generate video timelapse dari foto kehadiran user (Masuk)
     * Limit: Max 20 Foto
     */
    public function generateForUser($user, $month = null, $year = null)
    {
        $month = $month ?? Carbon::now()->month;
        $year = $year ?? Carbon::now()->year;

        // 1. Ambil list foto (Maks 20 foto "Masuk" urut tanggal)
        $photos = [];
        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            $student = Student::where('nis', $nis)->first();
            if ($student) {
                $photos = KehadiranSiswa::where('nis', $student->nis)
                    ->whereMonth('waktu_tap', $month)
                    ->whereYear('waktu_tap', $year)
                    ->where('keterangan', 'LIKE', 'Masuk%')
                    ->whereNotNull('photo')
                    ->orderBy('waktu_tap', 'asc')
                    ->limit(20)
                    ->pluck('photo')
                    ->toArray();
            }
        } else {
            $nipy = $user->nipy ?? $user->email;
            $photos = KehadiranGuruTu::where(function($q) use ($nipy, $user) {
                    $q->where('nipy', $nipy)->orWhere('nipy', $user->email);
                })
                ->whereMonth('waktu_tap', $month)
                ->whereYear('waktu_tap', $year)
                ->where('keterangan', 'LIKE', 'Masuk%')
                ->whereNotNull('photo')
                ->orderBy('waktu_tap', 'asc')
                ->limit(20)
                ->pluck('photo')
                ->toArray();
        }

        if (count($photos) < 3) {
            throw new \Exception("Minimal 3 foto diperlukan untuk membuat video kilas balik.");
        }

        // 2. Siapkan direktori kerja sementara di storage/app/temp_timelapse
        $tempDir = 'temp_timelapse/' . $user->id . '_' . time();
        
        // Pastikan folder temp dibuat secara rekursif
        if (!Storage::exists($tempDir)) {
            Storage::makeDirectory($tempDir, 0755, true);
        }
        
        $filesTxt = "";
        $index = 0;
        foreach ($photos as $photo) {
            // Path dari DB biasanya sudah termasuk subfolder (misal: 'absensi-selfie/image.jpg')
            $sourcePath = 'public/' . $photo;
            
            // Cek di disk storage Laravel
            if (!Storage::exists($sourcePath)) {
                // Fallback: Jika di DB tidak ada folder 'public/', coba tambahkan manual
                Log::warning("File timelapse skip: {$sourcePath} tidak ditemukan.");
                continue;
            }

            // Gunakan Storage::put & Storage::get daripada copy() fisik agar aman di Docker
            $tempFileName = sprintf("img_%03d.jpg", $index);
            $targetPath = $tempDir . '/' . $tempFileName;
            
            try {
                Storage::put($targetPath, Storage::get($sourcePath));
                
                // Format file untuk FFmpeg concat (duration 0.6s per image)
                $filesTxt .= "file '" . $tempFileName . "'\n";
                $filesTxt .= "duration 0.6\n";
                $index++;
            } catch (\Exception $e) {
                Log::error("Gagal menyalin file timelapse: " . $e->getMessage());
            }
        }
        
        if ($index === 0) {
            Storage::deleteDirectory($tempDir);
            $sampleDir = implode(', ', Storage::directories('public'));
            throw new \Exception("File foto fisik tidak ditemukan di server. (Ditemukan folder: {$sampleDir})");
        }
        
        // FFmpeg butuh file terakhir diduplikasi/tanpa durasi untuk penanda stop
        $filesTxt .= "file '" . sprintf("img_%03d.jpg", $index-1) . "'\n";

        Storage::put($tempDir . '/input.txt', $filesTxt);

        // 3. Jalankan FFmpeg untuk menjahit video
        $outputFile = 'timelapse_' . $user->id . '_' . $month . '_' . $year . '.mp4';
        $outputPath = storage_path('app/public/timelapse/' . $outputFile);
        Storage::makeDirectory('public/timelapse');
        
        // Jika file sudah ada, hapus dulu agar tidak konflik
        if (file_exists($outputPath)) unlink($outputPath);

        // Perintah FFmpeg: Gabungkan foto, resize ke 720p, format MP4 H.264 standar HP
        // Gunakan 'nice' agar tidak makan CPU berlebihan di produksi
        $cmd = [
            'nice', '-n', '10', 'ffmpeg', '-y', '-f', 'concat', '-safe', '0', 
            '-i', storage_path('app/' . $tempDir . '/input.txt'),
            '-vf', 'scale=720:720:force_original_aspect_ratio=decrease,pad=720:720:(ow-iw)/2:(oh-ih)/2,format=yuv420p',
            '-vcodec', 'libx264', '-crf', '25', '-pix_fmt', 'yuv420p',
            $outputPath
        ];

        $process = new Process($cmd);
        $process->setTimeout(60);
        $process->run();

        // 4. Cleanup temp folder
        Storage::deleteDirectory($tempDir);

        if (!$process->isSuccessful()) {
            Log::error("FFmpeg Timelapse Error: " . $process->getErrorOutput());
            throw new \Exception("Gagal mengolah video. Pastikan format foto didukung.");
        }

        return asset('storage/timelapse/' . $outputFile);
    }
}
