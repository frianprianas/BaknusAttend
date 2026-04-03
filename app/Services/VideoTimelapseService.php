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

        // 2. Siapkan direktori kerja di folder TEMP sistem operasi (Dijamin bisa ditulis di Docker)
        $osTempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'baknus_timelapse_' . $user->id . '_' . time();
        if (!file_exists($osTempDir)) {
            mkdir($osTempDir, 0777, true);
        }
        
        $filesForCleanup = [];
        $index = 0;
        foreach ($photos as $photo) {
            $sourcePath = 'public/' . $photo;
            if (!Storage::disk('public')->exists($sourcePath)) continue;

            $tempFileName = sprintf("img_%03d.jpg", $index);
            $targetPath = $osTempDir . DIRECTORY_SEPARATOR . $tempFileName;
            
            try {
                // Tulis langsung ke local filesystem OS
                file_put_contents($targetPath, Storage::disk('public')->get($sourcePath));
                $filesForCleanup[] = $targetPath;
                $index++;
            } catch (\Exception $e) {
                Log::error("Gagal tulis file temp timelapse: " . $e->getMessage());
            }
        }
        
        if ($index === 0) {
            rmdir($osTempDir);
            throw new \Exception("File foto fisik tidak ditemukan di server produksi.");
        }

        // 3. Jalankan FFmpeg menggunakan Image Sequence di folder TEMP OS
        $outputFileName = 'timelapse_' . $user->id . '_' . $month . '_' . $year . '.mp4';
        $finalPublicDir = storage_path('app/public/timelapse');
        if (!file_exists($finalPublicDir)) mkdir($finalPublicDir, 0777, true);
        
        $outputPath = $finalPublicDir . DIRECTORY_SEPARATOR . $outputFileName;
        if (file_exists($outputPath)) unlink($outputPath);

        // Gunakan perintah FFmpeg Image Sequence (Paling Stabil)
        $cmd = [
            'ffmpeg', '-y', 
            '-framerate', '2',
            '-i', $osTempDir . DIRECTORY_SEPARATOR . 'img_%03d.jpg',
            '-vf', 'scale=720:720:force_original_aspect_ratio=decrease,pad=720:720:(ow-iw)/2:(oh-ih)/2,format=yuv420p',
            '-vcodec', 'libx264', 
            '-preset', 'ultrafast',
            '-crf', '28', 
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            $outputPath
        ];

        $process = new Process($cmd);
        $process->setTimeout(120);
        $process->run();

        $errorMsg = $process->getErrorOutput();
        $isSuccess = $process->isSuccessful() && file_exists($outputPath);

        // 4. Cleanup Sempurna: Hapus semua file & folder temp
        foreach ($filesForCleanup as $f) {
            if (file_exists($f)) unlink($f);
        }
        if (file_exists($osTempDir)) rmdir($osTempDir);

        if (!$isSuccess) {
            Log::error("FFmpeg Timelapse Error: " . $errorMsg);
            throw new \Exception("Gagal mengolah video. Detail: " . substr(strip_tags($errorMsg), -150));
        }

        return asset('storage/timelapse/' . $outputFileName);
    }
}
