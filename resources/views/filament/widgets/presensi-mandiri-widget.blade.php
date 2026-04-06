<x-filament-widgets::widget>
    <!-- PRODUCTION FINAL: 2.4.0-REDESIGN -->
    @script
    <script>
        window.mesinAbsenFormalFixV15 = function() {
            return {
                statusText: 'Mengecek GPS...', statusClass: 'gps-idle', isBusy: false, busyText: '', gpsLocked: false, faceApiLoaded: false,
                lat: null, long: null,
                init() {
                    this.getGPS();
                    this.loadFaceApi();
                    setInterval(() => { if(!this.gpsLocked) this.getGPS(false); }, 45000);
                    
                    // Listen event absen sukses dari server
                    window.addEventListener('kehadiran-updated', () => {
                        this.showNativePush();
                    });
                },
                showNativePush() {
                    if ('Notification' in window && navigator.serviceWorker && Notification.permission === 'granted') {
                        navigator.serviceWorker.ready.then((reg) => {
                            reg.showNotification('Absen Berhasil! ✅', {
                                body: 'Presensi/Izin Anda telah tersimpan di BaknusAttend.',
                                icon: '/images/logo_BG.png',
                                vibrate: [200, 100, 200, 100, 200],
                                badge: '/images/logo_BG.png'
                            });
                        });
                    }
                },
                loadFaceApi() {
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js';
                    s.onload = async () => {
                        try {
                            await faceapi.nets.tinyFaceDetector.loadFromUri('https://vladmandic.github.io/face-api/model/');
                            this.faceApiLoaded = true;
                        } catch(e) { console.error("Face model load err", e); }
                    };
                    document.head.appendChild(s);
                },
                getGPS(showStatus = true) {
                    if (!navigator.geolocation) { this.statusText = 'Perangkat tidak mendukung GPS'; this.statusClass = 'gps-error'; return; }
                    if (showStatus) { this.statusText = 'Melacak sinyal GPS...'; this.statusClass = 'gps-idle'; }
                    navigator.geolocation.getCurrentPosition(
                        (p) => {
                            this.lat = p.coords.latitude;
                            this.long = p.coords.longitude;
                            this.gpsLocked = true;
                            this.statusText = 'Posisi terkunci · ' + p.coords.latitude.toFixed(4) + ', ' + p.coords.longitude.toFixed(4);
                            this.statusClass = 'gps-ok';
                        },
                        () => { this.gpsLocked = false; this.statusText = 'GPS tidak aktif atau ditolak'; this.statusClass = 'gps-error'; },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                },
                async submitAbsenFinal() {
                    // Paksa inisialisasi WebPush dari dalam interaksi pengguna untuk membypass limitasi iOS Safari
                    if (window.initWebPush && 'Notification' in window && Notification.permission !== 'denied') {
                        window.initWebPush();
                    }

                    if (this.isBusy) return;
                    this.isBusy = true;
                    this.busyText = 'Memvalidasi data...';

                    if (!this.gpsLocked) {
                        try {
                            await new Promise((resolve, reject) => {
                                navigator.geolocation.getCurrentPosition((p) => {
                                    this.lat = p.coords.latitude;
                                    this.long = p.coords.longitude;
                                    this.gpsLocked = true;
                                    resolve();
                                }, () => reject(), { timeout: 5000 });
                            });
                        } catch(e) { /* Abaikan untuk ditangani server */ }
                    }

                    // Sinkronisasi koordinat GPS ke backend Livewire TEPAT SEBELUM submit (Menghindari XHR siluman di bekgraun)
                    if (this.gpsLocked) {
                        this.$wire.set('data.lat', this.lat);
                        this.$wire.set('data.long', this.long);
                    }

                    const img = document.querySelector('.filepond--item canvas') || document.querySelector('.filepond--image-preview img');
                    
                    if (img && this.faceApiLoaded && window.faceapi) {
                        this.busyText = 'Memvalidasi wajah...';
                        try {
                            const det = await faceapi.detectAllFaces(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 }));
                            if (det.length === 0) {
                                this.isBusy = false;
                                alert('Wajah tidak terdeteksi. Pastikan wajah jelas dan menghadap kamera.');
                                
                                // Solusi: Panggil method reset di backend agar sinkronisasi FilePond 100% bersih kembali ke logo Kamera
                                this.$wire.call('resetSelfie');
                                return;
                            }
                        } catch(e) { console.error("AI Detect err", e); }
                        this.busyText = 'Sedang memindai wajah by BaknusAI...';
                    } else {
                        this.busyText = 'Mengirim data presensi...';
                    }
                    
                    try {
                        const promise = this.$wire.submit();
                        if (promise && typeof promise.then === 'function') {
                            await promise;
                        }
                    } catch(e) {
                        console.error(e);
                    } finally {
                        this.isBusy = false;
                    }
                }
            };
        }
    </script>
    @endscript

    <x-filament::section class="fi-absen-wrapper">
        <style>
            /* ---- Font ---- */
            @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

            .fi-absen-wrapper,
            .fi-absen-wrapper * { font-family: 'Plus Jakarta Sans', sans-serif !important; }

            .fi-absen-wrapper { background: transparent !important; border: none !important; box-shadow: none !important; padding: 0 !important; }
            /* .fi-fo-field-wrp-label { display: none !important; } -- Dinonaktifkan agar label Dinas Luar muncul */

            /* ---- Sembunyikan teks bawaan namun tetap clickable ---- */
            .fi-fo-file-upload-dropzone-label { display: none !important; }
            .filepond--label-action { text-decoration: none !important; }
            .filepond--drop-label { 
                opacity: 0 !important; /* HARUS opacity 0, JANGAN display none karena menghilangkan area klik kamera */
                cursor: pointer !important;
            }

            /* ---- Upload zone ---- */
            .filepond--root:not(.filepond--has-file) {
                background-color: #f1f5f9 !important;
                border: 2px dashed #94a3b8 !important;
                border-radius: 20px !important;
                min-height: 160px;
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                transition: border-color .2s, background .2s !important;
            }
            .filepond--root:not(.filepond--has-file):hover {
                border-color: #6366f1 !important;
                background-color: #eef2ff !important;
            }
            .filepond--root:not(.filepond--has-file)::before {
                content: "📷"; font-size: 3rem; margin-bottom: .5rem; opacity: .65;
                font-family: sans-serif !important;
            }
            .filepond--root:not(.filepond--has-file)::after {
                content: "KETUK UNTUK BUKA KAMERA";
                font-weight: 800 !important; font-size: .8rem !important;
                color: #64748b; letter-spacing: .06em;
                font-family: 'Plus Jakarta Sans', sans-serif !important;
            }
            .dark .filepond--root:not(.filepond--has-file) {
                background-color: rgba(30,41,59,.6) !important;
                border-color: #334155 !important;
            }
            .dark .filepond--root:not(.filepond--has-file)::after { color: #94a3b8; }

            /* ---- BA Logo (ilustrasi) – tampil di semua ukuran layar ---- */
            .absen-ba-logo {
                display: block;
                width: clamp(80px, 18vw, 120px); /* Responsif: minimum 80px, max 120px */
                height: auto;
                margin: 0 auto 18px;
                opacity: .85;
                filter: drop-shadow(0 4px 16px rgba(99,102,241,.25));
                user-select: none; pointer-events: none;
            }

            /* ---- Profile card ---- */
            .absen-profile-card {
                width: 100%; max-width: 680px;
                background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
                border: 1px solid #e2e8f0;
                border-radius: 20px;
                padding: 20px 24px;
                display: flex; align-items: center; gap: 16px;
                box-shadow: 0 2px 12px rgba(0,0,0,.06);
                margin-bottom: 28px;
            }
            .dark .absen-profile-card {
                background: linear-gradient(135deg, rgba(30,41,59,.8), rgba(15,23,42,.6));
                border-color: #1e293b;
            }
            .absen-avatar {
                width: 56px; height: 56px;
                border-radius: 14px;
                background: linear-gradient(135deg, #4f46e5, #818cf8);
                display: flex; align-items: center; justify-content: center;
                overflow: hidden; flex-shrink: 0;
                box-shadow: 0 4px 16px rgba(99,102,241,.35);
            }
            .absen-avatar img { width: 100%; height: 100%; object-fit: cover; }
            .absen-avatar span { font-size: 1.5rem; font-weight: 900; color: #fff; }
            .absen-user-name { font-size: 1rem; font-weight: 800; color: #0f172a; }
            .dark .absen-user-name { color: #f1f5f9; }
            .absen-user-meta { font-size: .72rem; color: #64748b; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; margin-top: 3px; }

            /* ---- Section title ---- */
            .absen-section-title {
                font-size: .7rem; font-weight: 800; text-transform: uppercase !important;
                letter-spacing: .12em; color: #6366f1 !important; text-align: center;
                margin-bottom: 6px;
            }
            .absen-divider {
                width: 32px; height: 3px; background: #6366f1; border-radius: 99px;
                margin: 0 auto 28px; opacity: .6;
            }

            /* ---- GPS pill ---- */
            .gps-pill {
                display: inline-flex; align-items: center; gap: 8px;
                padding: 8px 18px; border-radius: 999px;
                font-size: .72rem; font-weight: 700; letter-spacing: .04em;
                border: 1px solid; transition: all .3s;
                margin-top: 14px;
            }
            .gps-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
            .gps-idle { background: #f8fafc; border-color: #e2e8f0; color: #64748b; }
            .gps-idle .gps-dot { background: #94a3b8; animation: pulse-dot 1.5s infinite; }
            .gps-ok { background: #f0fdf4; border-color: #bbf7d0; color: #16a34a; }
            .gps-ok .gps-dot { background: #4ade80; box-shadow: 0 0 6px #4ade80; animation: pulse-dot 2s infinite; }
            .gps-error { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
            .gps-error .gps-dot { background: #f87171; }
            @keyframes pulse-dot {
                0%,100% { opacity:1; transform:scale(1); }
                50%      { opacity:.55; transform:scale(1.35); }
            }

            /* ---- Submit button ---- */
            .btn-absen-v2 {
                position: relative; width: 100%; padding: 16px 24px;
                border-radius: 16px; border: none; cursor: pointer;
                font-family: 'Plus Jakarta Sans', sans-serif !important;
                font-size: 1rem !important; font-weight: 800 !important;
                color: #fff !important; letter-spacing: .04em; text-transform: uppercase !important;
                background: linear-gradient(135deg, #4f46e5, #6366f1) !important;
                box-shadow: 0 8px 28px rgba(99,102,241,.40) !important;
                transition: transform .15s, box-shadow .15s !important;
                overflow: hidden;
            }
            .btn-absen-v2::before {
                content: ''; position: absolute; inset: 0;
                background: linear-gradient(135deg, rgba(255,255,255,.13), transparent);
                pointer-events: none;
            }
            .btn-absen-v2:hover:not(:disabled) { transform: translateY(-2px) !important; box-shadow: 0 14px 40px rgba(99,102,241,.50) !important; }
            .btn-absen-v2:active:not(:disabled) { transform: scale(.98) !important; }
            .btn-absen-v2:disabled { background: #94a3b8 !important; box-shadow: none !important; cursor: not-allowed !important; transform: none !important; opacity: .7 !important; }

            /* ---- Done state ---- */
            .absen-done {
                width: 100%; max-width: 680px;
                background: linear-gradient(135deg, #f0fdf4, #dcfce7);
                border: 1.5px dashed #86efac; border-radius: 20px;
                padding: 36px; text-align: center;
            }
            .dark .absen-done {
                background: linear-gradient(135deg, rgba(22,101,52,.2), rgba(20,83,45,.1));
                border-color: rgba(134,239,172,.3);
            }
            .absen-done-icon {
                width: 64px; height: 64px; border-radius: 50%;
                background: #dcfce7; border: 2px solid #86efac;
                display: flex; align-items: center; justify-content: center;
                font-size: 2rem; margin: 0 auto 16px;
            }

            /* ---- Busy overlay ---- */
            .absen-busy-overlay {
                position: absolute; inset: 0; z-index: 50;
                display: flex; align-items: center; justify-content: center;
                background: rgba(255,255,255,.95); backdrop-filter: blur(6px);
                border-radius: 16px;
            }
            .dark .absen-busy-overlay { background: rgba(15,23,42,.95); }
            .absen-busy-spinner {
                width: 36px; height: 36px; border-radius: 50%;
                border: 3px solid #e2e8f0; border-top-color: #6366f1;
                animation: spin 0.8s linear infinite; margin: 0 auto 14px;
            }
            @keyframes spin { to { transform:rotate(360deg); } }
            .absen-busy-text {
                font-size: .75rem; font-weight: 800; color: #6366f1;
                text-transform: uppercase; letter-spacing: .08em;
            }
        </style>

        <div x-data="window.mesinAbsenFormalFixV15()" class="flex flex-col items-center justify-center w-full">

            {{-- Profile Card --}}
            <div class="absen-profile-card">
                <a href="https://baknusmail.smkbn666.sch.id" target="_blank" class="absen-avatar group relative overflow-hidden cursor-pointer" title="Ganti Foto Profil">
                    @if($userAvatar)
                        <img src="{{ $userAvatar }}" alt="Avatar" class="transition group-hover:scale-110 group-hover:opacity-75">
                    @else
                        <span>{{ strtoupper(substr($userName ?? '?', 0, 1)) }}</span>
                    @endif
                    
                    {{-- Overlay Edit (Hanya muncul saat hover) --}}
                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                         <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                </a>
                <div>
                    <div class="absen-user-name">{{ $userName }}</div>
                    <div class="absen-user-meta">
                        {{ $userEmail }}
                        @if($userClass) &nbsp;·&nbsp; {{ $userClass }} @endif
                    </div>
                </div>
            </div>

            {{-- Logo dashboard sesuai request user --}}
            <img src="{{ asset('images/logo_BG.png') }}" alt="BaknusAI" class="absen-ba-logo">

            {{-- Section Label --}}
            <p class="absen-section-title">PRESENSI {{ strtoupper($tipeAbsens) }}</p>
            <div class="absen-divider"></div>

            {{-- Form / Done State --}}
            <form @submit.prevent="submitAbsenFinal()" class="w-full max-w-2xl relative">
                @if($tipeAbsens === 'Selesai')
                    <div class="absen-done">
                        <div class="absen-done-icon">🚀</div>
                        <p style="font-size:1.1rem;font-weight:800;color:#16a34a;margin:0 0 6px;">Tugas Hari Ini Selesai!</p>
                        <p style="font-size:.85rem;color:#4ade80;font-weight:500;">Presensi masuk dan pulang sudah tercatat.</p>
                    </div>
                @else
                    {{-- Busy Overlay --}}
                    <div x-show="isBusy" style="display:none;" class="absen-busy-overlay">
                        <div class="text-center">
                            <div class="absen-busy-spinner"></div>
                            <p class="absen-busy-text" x-text="busyText"></p>
                        </div>
                    </div>

                    {{-- Form FileUpload --}}
                    {{ $this->form }}

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Interseptor untuk tombol kamera (FilePond)
                            document.addEventListener('click', function(e) {
                                // Cari apakah yang diklik adalah bagian dari pengunggah foto
                                let target = e.target.closest('.filepond--root') || e.target.closest('input[type="file"]');
                                
                                if (target && !target.dataset.isConfirming) {
                                    // Mencegah browser langsung buka kamera
                                    e.preventDefault();
                                    e.stopPropagation();

                                    // Memunculkan pesan konfirmasi
                                    if (confirm("Buka Kamera Depan (Selfie)?")) {
                                        target.dataset.isConfirming = "true";
                                        target.click(); // Lanjutkan buka kamera
                                        
                                        // Reset tanda konfirmasi agar bisa diklik lagi nanti jika gagal
                                        setTimeout(() => { delete target.dataset.isConfirming; }, 1000);
                                    }
                                }
                            }, true);
                        });
                    </script>

                    <div class="mt-6">
                        <button
                            type="submit"
                            :disabled="isBusy"
                            class="btn-absen-v2"
                        >
                            <span x-show="!isBusy">Kirim Presensi {{ strtoupper($tipeAbsens) }}</span>
                            <span x-cloak x-show="isBusy" class="flex items-center justify-center gap-2" style="display: none;">
                                <svg style="animation:spin .8s linear infinite;width:18px;height:18px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"></circle>
                                    <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" style="opacity:.75"></path>
                                </svg>
                                <span x-text="busyText || 'Mengirim...'"></span>
                            </span>
                        </button>
                    </div>

                    {{-- GPS Status --}}
                    <div class="flex items-center justify-center mt-4">
                        <div class="gps-pill" :class="statusClass">
                            <div class="gps-dot"></div>
                            <span x-text="statusText"></span>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const forceSelfieMode = () => {
            document.querySelectorAll('input[type="file"]').forEach(input => {
                if (input.getAttribute('capture') !== 'user') {
                    input.setAttribute('capture', 'user');
                    input.setAttribute('accept', 'image/*');
                }
            });
        };

        // Pantau jika ada tombol kamera baru yang muncul
        const observer = new MutationObserver(forceSelfieMode);
        observer.observe(document.body, { childList: true, subtree: true });

        // Cadangan: Cek tiap 2 detik
        setInterval(forceSelfieMode, 2000);
    });
</script>
