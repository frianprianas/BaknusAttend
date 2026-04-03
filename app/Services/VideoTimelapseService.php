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
     * Limit: Max 60 Foto (Mencakup 2 bulan atau Pagi+Sore)
     */
    public function generateForUser($user, $month = null, $year = null)
    {
        $month = $month ?? Carbon::now()->month;
        $year = $year ?? Carbon::now()->year;

        // 1. Ambil list foto (Maks 60 foto)
        $photos = [];
        if ($user->role === 'Siswa') {
            $nis = $user->nipy ?? $user->email;
            $student = Student::where('nis', $nis)->first();
            if ($student) {
                // Ambil semua foto di bulan tersebut (bisa Masuk atau Pulang agar lebih banyak)
                $photos = KehadiranSiswa::where('nis', $student->nis)
                    ->whereMonth('waktu_tap', $month)
                    ->whereYear('waktu_tap', $year)
                    ->whereNotNull('photo')
                    ->orderBy('waktu_tap', 'asc')
                    ->limit(60)
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
                ->whereNotNull('photo')
                ->orderBy('waktu_tap', 'asc')
                ->limit(60)
                ->pluck('photo')
                ->toArray();
        }

        if (count($photos) < 3) {
            throw new \Exception("Minimal 3 foto diperlukan di database untuk membuat video.");
        }

        // 2. Siapkan direktori kerja di TEMP
        $osTempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'baknus_timelapse_' . $user->id . '_' . time();
        if (!file_exists($osTempDir)) {
            mkdir($osTempDir, 0777, true);
        }
        
        $filesForCleanup = [];
        $index = 0;
        foreach ($photos as $photo) {
            $possiblePaths = [$photo, 'absensi-selfie/' . $photo];
            $foundPath = null;
            foreach ($possiblePaths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    $foundPath = $path;
                    break;
                }
            }

            if (!$foundPath) continue;

            $tempFileName = sprintf("img_%03d.jpg", $index);
            $targetPath = $osTempDir . DIRECTORY_SEPARATOR . $tempFileName;
            
            try {
                file_put_contents($targetPath, Storage::disk('public')->get($foundPath));
                $filesForCleanup[] = $targetPath;
                $index++;
            } catch (\Exception $e) {
                Log::error("Gagal tulis file temp: " . $e->getMessage());
            }
        }
        
        // Cek lagi setelah filter fisik
        if ($index < 3) {
            if (file_exists($osTempDir)) rmdir($osTempDir);
            throw new \Exception("Foto fisik tidak ditemukan (minimal 3 foto diperlukan).");
        }

        // 3. Hitung Framerate agar durasi video pas (~10 detik)
        // Jika foto sedikit, lambatkan (framerate rendah). Jika banyak, percepat.
        $framerate = $index / 10; 
        if ($framerate < 0.8) $framerate = 0.8; // Max 1.2s per foto
        if ($framerate > 5) $framerate = 5;     // Max 5 foto per detik

        $outputFileName = 'timelapse_' . $user->id . '_' . $month . '_' . $year . '.mp4';
        $finalPublicDir = storage_path('app/public/timelapse');
        if (!file_exists($finalPublicDir)) mkdir($finalPublicDir, 0777, true);
        
        $outputPath = $finalPublicDir . DIRECTORY_SEPARATOR . $outputFileName;
        if (file_exists($outputPath)) unlink($outputPath);

        $cmd = [
            'ffmpeg', '-y', 
            '-framerate', (string)$framerate,
            '-i', $osTempDir . DIRECTORY_SEPARATOR . 'img_%03d.jpg',
            '-vf', 'scale=720:720:force_original_aspect_ratio=decrease,pad=720:720:(ow-iw)/2:(oh-ih)/2,format=yuv420p',
            '-vcodec', 'libx264', 
            '-preset', 'ultrafast',
            '-crf', '25', 
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            $outputPath
        ];

        $process = new Process($cmd);
        $process->setTimeout(180);
        $process->run();

        $errorMsg = $process->getErrorOutput();
        $isSuccess = $process->isSuccessful() && file_exists($outputPath);

        // 4. Cleanup
        foreach ($filesForCleanup as $f) {
            if (file_exists($f)) unlink($f);
        }
        if (file_exists($osTempDir)) rmdir($osTempDir);

        if (!$isSuccess) {
            Log::error("FFmpeg Error: " . $errorMsg);
            throw new \Exception("Gagal menghasilkan file video.");
        }

        return asset('storage/timelapse/' . $outputFileName);
    }
}
