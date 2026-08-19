<x-filament-widgets::widget>
    <!-- PRODUCTION FINAL: 2.6.0-NFC-GEOFENCE-UI-REDESIGN -->
    @script
    <script>
        window.mesinAbsenFormalFixV15 = function() {
            return {
                statusText: 'Mengecek GPS...', statusClass: 'gps-idle', isBusy: false, busyText: '', gpsLocked: false, faceApiLoaded: false,
                lat: null, long: null, clientIp: null,
                activeTab: 'selfie',
                isNfcSupported: false,
                nfcStatusText: 'Ketuk tombol di bawah lalu tempelkan Kartu ID Card ke bodi belakang HP',
                isScanningNfc: false,

                init() {
                    this.getGPS();
                    this.loadFaceApi();
                    this.getClientIp();
                    this.checkNfcSupport();
                    setInterval(() => { if(!this.gpsLocked) this.getGPS(false); }, 45000);
                    
                    // Listen event absen sukses dari server
                    window.addEventListener('kehadiran-updated', () => {
                        this.showNativePush();
                    });
                },

                checkNfcSupport() {
                    this.isNfcSupported = ('NDEFReader' in window);
                },

                async startNfcScan() {
                    if (!('NDEFReader' in window)) {
                        alert('Smartphone atau Browser Anda belum mendukung sensor Web NFC. Silakan gunakan Opsi Kamera Selfie.');
                        return;
                    }

                    // 1. Wajib Kunci Lokasi GPS Terlebih Dahulu Sebelum NFC Scan
                    if (!this.gpsLocked) {
                        this.isBusy = true;
                        this.busyText = 'Melacak lokasi GPS...';
                        try {
                            await new Promise((resolve, reject) => {
                                navigator.geolocation.getCurrentPosition((p) => {
                                    this.lat = p.coords.latitude;
                                    this.long = p.coords.longitude;
                                    this.gpsLocked = true;
                                    this.statusText = 'Posisi terkunci · ' + p.coords.latitude.toFixed(4) + ', ' + p.coords.longitude.toFixed(4);
                                    this.statusClass = 'gps-ok';
                                    resolve();
                                }, (err) => reject(err), { enableHighAccuracy: true, timeout: 8000 });
                            });
                        } catch(e) {
                            this.isBusy = false;
                            alert('Presensi Gagal: Sinyal GPS HP Anda belum terkunci. Aktifkan lokasi GPS HP Anda.');
                            return;
                        } finally {
                            this.isBusy = false;
                        }
                    }

                    if (!this.clientIp) {
                        await this.getClientIp();
                    }

                    // 2. Aktifkan Listener Sensor NFC
                    try {
                        this.isScanningNfc = true;
                        this.nfcStatusText = '📡 Sensor NFC Aktif! Silakan Tempelkan Kartu ID Card ke bodi belakang HP Anda...';

                        const ndef = new NDEFReader();
                        await ndef.scan();

                        ndef.addEventListener("reading", async ({ serialNumber }) => {
                            if (!serialNumber) {
                                alert("Gagal membaca ID Kartu NFC.");
                                this.isScanningNfc = false;
                                return;
                            }

                            const cleanUid = serialNumber.replace(/[: -]/g, '').toUpperCase();
                            this.nfcStatusText = `✅ Kartu Terbaca (${cleanUid})! Memverifikasi lokasi & presensi...`;
                            this.isBusy = true;
                            this.busyText = 'Memverifikasi Lokasi & Kartu NFC...';

                            try {
                                await this.$wire.call('submitRfidPresensi', cleanUid, this.lat, this.long, this.clientIp);
                            } catch(e) {
                                console.error(e);
                            } finally {
                                this.isBusy = false;
                                this.isScanningNfc = false;
                                this.nfcStatusText = 'Ketuk tombol di bawah lalu tempelkan Kartu ID Card ke bodi belakang HP';
                            }
                        });

                        ndef.addEventListener("readingerror", () => {
                            alert("Gagal membaca data dari Kartu. Coba dekatkan ulang kartu ke sensor NFC HP.");
                            this.isScanningNfc = false;
                            this.nfcStatusText = 'Ketuk tombol di bawah lalu tempelkan Kartu ID Card ke bodi belakang HP';
                        });

                    } catch (error) {
                        console.error("NFC Scan Error:", error);
                        this.isScanningNfc = false;
                        this.nfcStatusText = 'Gagal mengaktifkan sensor NFC';
                        alert(`Gagal mengakses Sensor NFC HP: ${error.message || error}`);
                    }
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
                async getClientIp() {
                    const providers = [
                        'https://api.ipify.org?format=json',
                        'https://api4.ipify.org?format=json',
                        'https://ipv4.seeip.org/jsonip',
                        'https://ipapi.co/json/',
                        'https://api.seeip.org/jsonip'
                    ];
                    for (const url of providers) {
                        try {
                            const controller = new AbortController();
                            const id = setTimeout(() => controller.abort(), 4000);
                            const response = await fetch(url, { signal: controller.signal });
                            clearTimeout(id);
                            const data = await response.json();
                            const ip = data.ip || data.ip_address;
                            if (ip) {
                                this.clientIp = ip.trim();
                                break;
                            }
                        } catch (e) {
                            console.warn('Gagal ambil IP dari ' + url);
                        }
                    }
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

                    if (this.gpsLocked) {
                        this.$wire.set('data.lat', this.lat);
                        this.$wire.set('data.long', this.long);
                    }

                    if (!this.clientIp) {
                        this.busyText = 'Mengecek alamat IP...';
                        await this.getClientIp();
                    }

                    if (this.clientIp) {
                        this.$wire.set('data.client_public_ip', this.clientIp);
                    }

                    const img = document.querySelector('.filepond--item canvas') || document.querySelector('.filepond--image-preview img');
                    
                    if (img && this.faceApiLoaded && window.faceapi) {
                        this.busyText = 'Memvalidasi wajah...';
                        try {
                            const det = await faceapi.detectAllFaces(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 }));
                            if (det.length === 0) {
                                this.isBusy = false;
                                alert('Wajah tidak terdeteksi. Pastikan wajah jelas dan menghadap kamera.');
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

            /* ---- Sembunyikan teks bawaan namun tetap clickable ---- */
            .fi-fo-file-upload-dropzone-label { display: none !important; }
            .filepond--label-action { text-decoration: none !important; }
            .filepond--drop-label { 
                opacity: 0 !important;
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
                width: clamp(80px, 18vw, 120px);
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
                margin-bottom: 24px;
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
                margin: 0 auto 20px; opacity: .6;
            }

            /* ---- Modern Glassmorphic Tab Container ---- */
            .absen-tab-container {
                display: flex; align-items: center; justify-content: center; gap: 8px;
                padding: 6px; background: rgba(241, 245, 249, 0.85);
                backdrop-filter: blur(10px);
                border: 1px solid #e2e8f0; border-radius: 18px;
                margin-bottom: 24px; max-width: 440px; width: 100%;
                box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            }
            .dark .absen-tab-container {
                background: rgba(30, 41, 59, 0.7);
                border-color: #334155;
            }
            .absen-tab-btn {
                flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
                padding: 10px 16px; border-radius: 14px; font-size: 0.75rem; font-weight: 800;
                letter-spacing: 0.02em; border: none; cursor: pointer; transition: all 0.25s ease;
                color: #64748b; background: transparent;
            }
            .dark .absen-tab-btn { color: #94a3b8; }
            .absen-tab-btn.active {
                background: linear-gradient(135deg, #4f46e5, #6366f1) !important;
                color: #ffffff !important;
                box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35) !important;
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

            {{-- Logo dashboard --}}
            <img src="{{ asset('images/logo_BG.png') }}" alt="BaknusAI" class="absen-ba-logo">

            {{-- Section Label --}}
            <p class="absen-section-title">PRESENSI {{ strtoupper($tipeAbsens) }}</p>
            <div class="absen-divider"></div>

            {{-- Option Tab Switcher (Modern Metallic Glass Pill) --}}
            @if($tipeAbsens !== 'Selesai' && $tipeAbsens !== 'Libur')
                <div class="absen-tab-container">
                    <button type="button" 
                            @click="activeTab = 'selfie'" 
                            :class="{ 'active': activeTab === 'selfie' }" 
                            class="absen-tab-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>📸 Kamera Selfie</span>
                    </button>
                    
                    <button type="button" 
                            @click="activeTab = 'nfc'; checkNfcSupport()" 
                            :class="{ 'active': activeTab === 'nfc' }" 
                            class="absen-tab-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span>💳 Tap Kartu NFC / RFID</span>
                    </button>
                </div>
            @endif

            {{-- Form / Done State --}}
            <form @submit.prevent="submitAbsenFinal()" class="w-full max-w-2xl relative flex flex-col items-center justify-center">
                @if($tipeAbsens === 'Selesai')
                    <div class="absen-done">
                        <div class="absen-done-icon">🚀</div>
                        <p style="font-size:1.1rem;font-weight:800;color:#16a34a;margin:0 0 6px;">Tugas Hari Ini Selesai!</p>
                        <p style="font-size:.85rem;color:#4ade80;font-weight:500;margin-bottom:16px;">Presensi masuk dan pulang sudah tercatat.</p>
                        
                        @if(in_array(auth()->user()?->role, ['Guru', 'TU']))
                            <button type="button" 
                                    wire:click="hapusAbsenPulang" 
                                    wire:confirm="Apakah Anda yakin ingin menghapus presensi pulang hari ini?"
                                    class="px-4 py-2 bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 hover:text-red-800 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 mx-auto cursor-pointer"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                Salah Pencet? Hapus Absen Pulang
                            </button>
                        @endif
                    </div>
                @elseif($tipeAbsens === 'Libur')
                    <div class="absen-done" style="background: linear-gradient(135deg, #fef2f2, #fee2e2); border-color: #fca5a5;">
                        <div class="absen-done-icon" style="background: #fee2e2; border-color: #fca5a5; box-shadow: 0 2px 6px rgba(239,68,68,0.2);">🎉</div>
                        <p style="font-size:1.1rem;font-weight:800;color:#dc2626;margin:0 0 6px;">Hari Ini Libur!</p>
                        <p style="font-size:.85rem;color:#ef4444;font-weight:500;">{{ $namaLibur }}</p>
                    </div>
                @else
                    {{-- Busy Overlay --}}
                    <div x-show="isBusy" style="display:none;" class="absen-busy-overlay">
                        <div class="text-center">
                            <div class="absen-busy-spinner"></div>
                            <p class="absen-busy-text" x-text="busyText"></p>
                        </div>
                    </div>

                    {{-- TAB 1: KAMERA SELFIE (100% UTUH DENGAN MODEL LAMA) --}}
                    <div x-show="activeTab === 'selfie'" class="w-full">
                        {{ $this->form }}

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                document.addEventListener('click', function(e) {
                                    let target = e.target.closest('.filepond--root') || e.target.closest('input[type="file"]');
                                    
                                    if (target && !target.dataset.isConfirming) {
                                        e.preventDefault();
                                        e.stopPropagation();

                                        if (confirm("Buka Kamera Depan (Selfie)?")) {
                                            target.dataset.isConfirming = "true";
                                            target.click();
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
                    </div>

                    {{-- TAB 2: TAP KARTU NFC / RFID --}}
                    <div x-show="activeTab === 'nfc'" style="display: none;" class="w-full">
                        {{-- JIKA SENSOR NFC DIDUKUNG --}}
                        <div x-show="isNfcSupported" class="w-full max-w-md mx-auto text-center p-8 bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800 rounded-3xl shadow-2xl relative overflow-hidden">
                            {{-- Ambient Background Glow --}}
                            <div class="absolute -top-12 -right-12 w-32 h-32 bg-indigo-500/20 rounded-full blur-2xl pointer-events-none"></div>
                            <div class="absolute -bottom-12 -left-12 w-32 h-32 bg-purple-500/20 rounded-full blur-2xl pointer-events-none"></div>

                            {{-- Animated NFC Credit Card Graphic --}}
                            <div class="relative w-44 h-28 mx-auto mb-6 bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 rounded-2xl p-4 shadow-xl border border-indigo-400/30 flex flex-col justify-between text-left overflow-hidden">
                                {{-- Card Shine Line --}}
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent transform -skew-x-12"></div>
                                
                                <div class="flex items-center justify-between z-10">
                                    <div class="w-8 h-6 bg-gradient-to-r from-amber-300 to-amber-500 rounded-md border border-amber-200/50 shadow-inner"></div>
                                    <span class="text-white/80 font-mono text-[10px] tracking-widest font-bold">BAKNUS ID</span>
                                </div>
                                <div class="z-10 flex items-center justify-between">
                                    <span class="text-white/90 font-mono text-xs font-semibold">•••• •••• NFC</span>
                                    <svg class="w-6 h-6 text-white/90 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                            </div>

                            <h3 class="font-extrabold text-white text-base mb-1.5 tracking-wide">Presensi Tap Kartu NFC</h3>
                            <p class="text-xs text-slate-400 font-medium leading-relaxed mb-6 px-2" x-text="nfcStatusText"></p>

                            <button type="button" 
                                    @click="startNfcScan()" 
                                    :disabled="isBusy"
                                    class="w-full py-4 px-6 bg-gradient-to-r from-indigo-500 via-indigo-600 to-purple-600 hover:from-indigo-400 hover:to-purple-500 text-white font-extrabold text-sm rounded-2xl shadow-lg shadow-indigo-500/40 transition-all active:scale-95 disabled:opacity-50 cursor-pointer">
                                <span x-show="!isScanningNfc">📡 Mulai Scan Kartu NFC</span>
                                <span x-show="isScanningNfc" style="display:none;" class="flex items-center justify-center gap-2">
                                    <span class="w-2.5 h-2.5 bg-white rounded-full animate-ping"></span>
                                    Menunggu Tap Kartu ke HP...
                                </span>
                            </button>
                        </div>

                        {{-- JIKA PERANGKAT / BROWSER BELUM DUKUNG NFC --}}
                        <div x-show="!isNfcSupported" style="display: none;" class="w-full max-w-md mx-auto p-6 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 rounded-3xl text-center shadow-sm">
                            <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/60 text-amber-600 rounded-full flex items-center justify-center text-2xl mx-auto mb-4">⚠️</div>
                            <h4 class="font-extrabold text-amber-900 dark:text-amber-200 text-sm mb-2">Perangkat / Browser Belum Mendukung Sensor NFC</h4>
                            <p class="text-xs text-amber-700 dark:text-amber-300 font-medium leading-relaxed mb-5">
                                Smartphone atau browser yang Anda gunakan saat ini belum mendukung fitur sensor Web NFC. Silakan gunakan opsi Presensi Kamera Selfie.
                            </p>
                            <button type="button" @click="activeTab = 'selfie'" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                                📸 Kembali ke Kamera Selfie
                            </button>
                        </div>
                    </div>

                    {{-- GPS Status Pill --}}
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
        // A. Fungsi Paksa Kamera Selfie
        const forceSelfieMode = () => {
            document.querySelectorAll('input[type="file"]').forEach(input => {
                if (input.getAttribute('capture') !== 'user') {
                    input.setAttribute('capture', 'user');
                    input.setAttribute('accept', 'image/jpeg, image/png;capture=user');
                }
            });
        };

        // B. Fungsi Pesan Peringatan Kamera Selfie
        const alertSelfie = (e) => {
            let el = e.target.closest('.filepond--root') || 
                     e.target.closest('.filepond--label-action') || 
                     e.target.closest('input[type="file"]');
            
            if (el && !el.dataset.hasAlerted) {
                el.dataset.hasAlerted = "true";
                alert("📣 BUKA KAMERA DEPAN?\n\nJika yang terbukan adalah kamera belakang, mohon ketuk tombol putar/switch ke KAMERA DEPAN (Selfie) ya!");
                setTimeout(() => { delete el.dataset.hasAlerted; }, 10000);
            }
        };

        const observer = new MutationObserver(forceSelfieMode);
        observer.observe(document.body, { childList: true, subtree: true });

        document.addEventListener('pointerdown', alertSelfie, true);

        setInterval(forceSelfieMode, 2000);
        forceSelfieMode();
    });
</script>
