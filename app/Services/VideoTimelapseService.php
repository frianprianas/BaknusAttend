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
            // Kita gunakan disk 'public' langsung, jadi path tidak perlu 'public/'
            // Path di DB biasanya: 'absensi-selfie/nama_file.jpg'
            $sourcePath = $photo;
            
            // Cek di disk public
            if (!Storage::disk('public')->exists($sourcePath)) {
                Log::warning("File timelapse skip: {$sourcePath} tidak ditemukan di disk public.");
                continue;
            }

            // Gunakan Storage::put & Storage::get daripada copy() fisik agar aman di Docker
            $tempFileName = sprintf("img_%03d.jpg", $index);
            $targetPath = $tempDir . '/' . $tempFileName;
            
            try {
                // Ambil konten dari disk public, simpan ke folder temp (disk default/local)
                Storage::put($targetPath, Storage::disk('public')->get($sourcePath));
                
                // Format file untuk FFmpeg concat (duration 0.6s per image)
                $filesTxt .= "file '" . $tempFileName . "'\n";
                $filesTxt .= "duration 0.6\n";
                $index++;
            } catch (\Exception $e) {
                Log::error("Gagal menyalin file timelapse ({$sourcePath}): " . $e->getMessage());
            }
        }
        
        if ($index === 0) {
            Storage::deleteDirectory($tempDir);
            $diskRoots = config('filesystems.disks.public.root');
            throw new \Exception("File foto fisik tidak ditemukan di server. (Mencari di: {$diskRoots})");
        }
        
        // FFmpeg butuh file terakhir diduplikasi/tanpa durasi untuk penanda stop
        $filesTxt .= "file '" . sprintf("img_%03d.jpg", $index-1) . "'\n";

        Storage::put($tempDir . '/input.txt', $filesTxt);

        // 3. Jalankan FFmpeg untuk menjahit video (Teknik Image Sequence lebih stabil)
        $outputFile = 'timelapse_' . $user->id . '_' . $month . '_' . $year . '.mp4';
        $outputPath = storage_path('app/public/timelapse/' . $outputFile);
        Storage::makeDirectory('public/timelapse');
        
        if (file_exists($outputPath)) unlink($outputPath);

        // Perintah FFmpeg Baru: Ambil img_%03d.jpg secara berurutan
        $cmd = [
            'ffmpeg', '-y', 
            '-framerate', '2', // 2 frame per detik (0.5 detik per foto)
            '-i', 'img_%03d.jpg',
            '-vf', 'scale=720:720:force_original_aspect_ratio=decrease,pad=720:720:(ow-iw)/2:(oh-ih)/2,format=yuv420p',
            '-vcodec', 'libx264', 
            '-preset', 'ultrafast',
            '-crf', '28', 
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            $outputPath
        ];

        // Jalankan di DALAM folder temp agar ffmpeg mudah akses gambarnya
        $process = new Process($cmd, storage_path('app/' . $tempDir));
        $process->setTimeout(120);
        $process->run();

        $errorMsg = $process->getErrorOutput();
        $isSuccess = $process->isSuccessful() && file_exists($outputPath);

        // 4. Cleanup temp folder
        Storage::deleteDirectory($tempDir);

        if (!$isSuccess) {
            Log::error("FFmpeg Timelapse Error: " . $errorMsg);
            throw new \Exception("Gagal mengolah video. Detail: " . substr(strip_tags($errorMsg), 0, 150));
        }

        return asset('storage/timelapse/' . $outputFile);
    }
}
