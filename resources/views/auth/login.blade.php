<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="BaknusAttend – Sistem Presensi Digital SMK Bakti Nusantara 666">
    <title>BaknusAttend – Login & Presensi Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --font: 'Plus Jakarta Sans', -apple-system, sans-serif;
        }

        html, body {
            height: 100%;
            font-family: var(--font);
            background: #070a13;
            color: #f8fafc;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* =========================================================
           CINEMATIC SLIDESHOW WRAPPER (LAYAR 10 INCH KE ATAS / >= 768PX)
           ========================================================= */
        .cinema-viewport {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at top center, #1e1b4b 0%, #0f172a 60%, #050811 100%);
            display: flex;
            flex-direction: column;
            padding: 20px 28px;
            z-index: 1;
        }

        /* Top Bar */
        .cinema-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 10;
        }

        .cinema-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cinema-brand img {
            height: 38px;
            width: auto;
            filter: drop-shadow(0 0 10px rgba(129, 140, 248, 0.5));
        }

        .cinema-brand-title {
            font-size: 1.15rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            color: #fff;
        }

        .cinema-brand-title span {
            color: #818cf8;
        }

        /* Slide Timer Progress Bar */
        .slide-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #38bdf8);
            width: 0%;
            z-index: 999;
            transition: width 6s linear;
        }

        /* Proyeksi Layar Papan Tulis Bioskop */
        .screen-header-box {
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.25) 0%, rgba(15, 23, 42, 0.9) 100%);
            border-top: 4px solid #818cf8;
            border-bottom: 1.5px solid rgba(129, 140, 248, 0.3);
            border-radius: 12px 12px 50px 50px;
            padding: 12px 24px;
            text-align: center;
            box-shadow: 0 12px 35px -10px rgba(99, 102, 241, 0.4);
            margin: 12px auto 20px;
            max-width: 650px;
            width: 100%;
        }

        .screen-title {
            font-size: 0.95rem;
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #a5b4fc;
        }

        /* Slides Container */
        .slides-container {
            flex: 1;
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .slide-item {
            position: absolute;
            inset: 0;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
            transform: scale(0.98);
            overflow-y: auto;
            padding-bottom: 60px;
        }

        .slide-item.active {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        /* Grid Bioskop Kotak-Kotak */
        .cinema-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 14px;
            width: 100%;
            padding: 4px;
        }

        @media (min-width: 1024px) {
            .cinema-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 16px;
            }
        }

        /* Seat Pod Card */
        .seat-pod {
            border-radius: 18px;
            padding: 12px 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1.5px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .seat-pod:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.5);
        }

        .seat-hadir {
            background: linear-gradient(145deg, rgba(6, 78, 59, 0.85) 0%, rgba(4, 120, 87, 0.9) 100%);
            border-color: #10b981;
            color: #ecfdf5;
            box-shadow: 0 4px 15px -3px rgba(16, 185, 129, 0.4);
        }

        .seat-terlambat {
            background: linear-gradient(145deg, rgba(120, 53, 15, 0.85) 0%, rgba(180, 83, 9, 0.9) 100%);
            border-color: #f59e0b;
            color: #fef3c7;
            box-shadow: 0 4px 15px -3px rgba(245, 158, 11, 0.4);
        }

        .seat-izin {
            background: linear-gradient(145deg, rgba(30, 58, 138, 0.85) 0%, rgba(29, 78, 216, 0.9) 100%);
            border-color: #3b82f6;
            color: #eff6ff;
            box-shadow: 0 4px 15px -3px rgba(59, 130, 246, 0.4);
        }

        .seat-belum {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.75) 0%, rgba(15, 23, 42, 0.9) 100%);
            border-color: #334155;
            color: #94a3b8;
        }

        /* STRICT AVATAR CONTAINER FIX FOR OVERFLOWING PHOTOS */
        .seat-avatar-box {
            width: 52px !important;
            height: 52px !important;
            min-width: 52px !important;
            min-height: 52px !important;
            max-width: 52px !important;
            max-height: 52px !important;
            border-radius: 50% !important;
            overflow: hidden !important;
            margin: 4px auto 6px !important;
            border: 2px solid rgba(255, 255, 255, 0.4) !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3) !important;
            background: rgba(15, 23, 42, 0.6) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: relative !important;
            flex-shrink: 0 !important;
        }

        .seat-avatar-img {
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            max-height: 100% !important;
            object-fit: cover !important;
            border-radius: 50% !important;
            display: block !important;
        }

        .seat-initial-fallback {
            width: 100% !important;
            height: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-weight: 900 !important;
            font-size: 0.9rem !important;
            color: #ffffff !important;
            background: rgba(99, 102, 241, 0.4) !important;
        }

        /* LED Lamp */
        .led-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .led-hadir { background: #22c55e; box-shadow: 0 0 10px #22c55e; }
        .led-terlambat { background: #eab308; box-shadow: 0 0 10px #eab308; }
        .led-izin { background: #3b82f6; box-shadow: 0 0 10px #3b82f6; }
        .led-belum { background: #64748b; opacity: 0.5; }

        /* Floating Login Button at Top Right */
        .btn-floating-login {
            position: fixed;
            top: 18px;
            right: 24px;
            z-index: 50;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff;
            padding: 9px 20px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.82rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-floating-login:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.7);
        }

        /* Login Modal Drawer on Desktop */
        .login-drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 24px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .login-drawer-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        .login-drawer-card {
            background: #ffffff;
            color: #0f172a;
            width: 100%;
            max-width: 400px;
            border-radius: 28px;
            padding: 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            transform: translateX(40px);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-drawer-overlay.open .login-drawer-card {
            transform: translateX(0);
        }

        .drawer-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #f1f5f9;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #64748b;
            transition: all 0.2s;
        }

        .drawer-close-btn:hover { background: #e2e8f0; color: #0f172a; }

        /* =========================================================
           LAYAR KECIL / MOBILE (< 768PX) — LOGIN FORM DEFAULT
           ========================================================= */
        .mobile-login-view {
            display: none;
            width: 100%;
            min-height: 100vh;
            background: #f8fafc;
            color: #0f172a;
            padding: 32px 24px;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        @media (max-width: 767px) {
            .cinema-viewport { display: none !important; }
            .btn-floating-login { display: none !important; }
            .login-drawer-overlay { display: none !important; }
            .mobile-login-view { display: flex !important; }
        }

        /* Form elements shared */
        .form-eyebrow { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #6366f1; margin-bottom: 6px; }
        .form-title { font-size: 1.75rem; font-weight: 900; color: #0f172a; margin-bottom: 6px; line-height: 1.15; }
        .form-subtitle { font-size: 0.85rem; color: #64748b; margin-bottom: 28px; line-height: 1.5; }
        
        .alert-error {
            background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
            padding: 12px 14px; border-radius: 12px; font-size: 0.82rem; font-weight: 600;
            margin-bottom: 20px;
        }

        .field-group { margin-bottom: 18px; }
        .field-group label { display: block; font-size: 0.76rem; font-weight: 700; color: #334155; margin-bottom: 6px; }
        .field-group input {
            width: 100%; padding: 12px 14px; border-radius: 14px; border: 1.5px solid #e2e8f0;
            background: #f8fafc; font-size: 0.9rem; font-family: var(--font); color: #0f172a; outline: none;
            transition: all 0.2s;
        }
        .field-group input:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3.5px rgba(99, 102, 241, 0.15); }

        .btn-login-submit {
            width: 100%; padding: 14px; border-radius: 14px; border: none; cursor: pointer;
            background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff;
            font-weight: 800; font-size: 0.9rem; font-family: var(--font);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35); transition: transform 0.15s;
        }
        .btn-login-submit:active { transform: scale(0.98); }

        /* Bottom Slide Dots */
        .slide-dots-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            z-index: 20;
            margin-top: 8px;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            width: 28px;
            border-radius: 99px;
            background: #818cf8;
            box-shadow: 0 0 10px #818cf8;
        }
    </style>
</head>

<body>

    {{-- Progress Bar Timer (6s) --}}
    <div class="slide-progress-bar" id="slideProgressBar"></div>

    {{-- Floating Login Button (Pojok Kanan Atas - Layar Besar) --}}
    <button class="btn-floating-login" onclick="toggleLoginDrawer(true)">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
        <span>Masuk Sistem</span>
    </button>

    {{-- =========================================================
       DESKTOP / TABLET (10 INCH+) — CINEMATIC SLIDESHOW VIEW
       ========================================================= --}}
    <div class="cinema-viewport">
        
        {{-- Top Bar --}}
        <div class="cinema-topbar">
            <div class="cinema-brand">
                <img src="{{ asset('images/logo_BG.png') }}" alt="Logo BaknusAttend">
                <span class="cinema-brand-title">Baknus<span>Attend</span></span>
            </div>

            <div style="display:flex;align-items:center;gap:12px;font-size:0.8rem;font-weight:600;color:#94a3b8;">
                <span>📅 {{ now()->translatedFormat('l, j F Y') }}</span>
                <span>·</span>
                <span style="color:#34d399;font-weight:800;">⚡ Live Auto-Slide (6s)</span>
            </div>
        </div>

        {{-- Proyeksi Header Screen --}}
        <div class="screen-header-box">
            <p class="screen-title" id="screenTitleText">DEWAN GURU — PRESENSI GURU</p>
        </div>

        {{-- Slides Container --}}
        <div class="slides-container" id="slidesContainer">

            {{-- SLIDE 1: DEWAN GURU --}}
            <div class="slide-item active" data-title="DEWAN GURU — PRESENSI GURU">
                <div class="cinema-grid">
                    @foreach($teacherGrid as $t)
                        @php
                            $statusClass = match($t['status_code']) { 'HADIR' => 'seat-hadir', 'TERLAMBAT' => 'seat-terlambat', 'IZIN' => 'seat-izin', default => 'seat-belum' };
                            $ledClass = match($t['status_code']) { 'HADIR' => 'led-hadir', 'TERLAMBAT' => 'led-terlambat', 'IZIN' => 'led-izin', default => 'led-belum' };
                        @endphp
                        <div class="seat-pod {{ $statusClass }}">
                            <div style="width:100%;display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                <span style="font-size:9px;font-weight:900;padding:2px 6px;border-radius:99px;background:rgba(0,0,0,0.3);color:#fff;">{{ $t['seat_number'] }}</span>
                                <span class="led-dot {{ $ledClass }}"></span>
                            </div>

                            {{-- Avatar Container (Strict 52px circular box) --}}
                            <div class="seat-avatar-box">
                                <img src="{{ $t['avatar_url'] }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" class="seat-avatar-img" alt="{{ $t['name'] }}">
                                <div class="seat-initial-fallback" style="display:none;">{{ strtoupper(substr($t['name'], 0, 2)) }}</div>
                            </div>

                            <div style="width:100%;margin-top:2px;">
                                <h4 style="font-size:0.75rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $t['name'] }}">{{ $t['name'] }}</h4>
                                <p style="font-size:9.5px;opacity:0.75;margin-top:2px;">Guru</p>
                            </div>
                            <div style="width:100%;margin-top:8px;">
                                <span style="display:block;padding:3px 2px;font-size:9px;font-weight:800;border-radius:8px;text-transform:uppercase;background:rgba(0,0,0,0.25);">
                                    {{ $t['status_code'] === 'HADIR' ? '✅ ' . $t['waktu_tap'] : ($t['status_code'] === 'TERLAMBAT' ? '⚠️ ' . $t['waktu_tap'] : ($t['status_code'] === 'IZIN' ? '📋 IZIN' : '⚪ BELUM')) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- SLIDE 2: STAFF TATA USAHA (TU) --}}
            <div class="slide-item" data-title="STAFF TATA USAHA (TU) — PRESENSI STAF">
                <div class="cinema-grid">
                    @foreach($tuGrid as $tu)
                        @php
                            $statusClass = match($tu['status_code']) { 'HADIR' => 'seat-hadir', 'TERLAMBAT' => 'seat-terlambat', 'IZIN' => 'seat-izin', default => 'seat-belum' };
                            $ledClass = match($tu['status_code']) { 'HADIR' => 'led-hadir', 'TERLAMBAT' => 'led-terlambat', 'IZIN' => 'led-izin', default => 'led-belum' };
                        @endphp
                        <div class="seat-pod {{ $statusClass }}">
                            <div style="width:100%;display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                <span style="font-size:9px;font-weight:900;padding:2px 6px;border-radius:99px;background:rgba(0,0,0,0.3);color:#fff;">{{ $tu['seat_number'] }}</span>
                                <span class="led-dot {{ $ledClass }}"></span>
                            </div>

                            <div class="seat-avatar-box">
                                <img src="{{ $tu['avatar_url'] }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" class="seat-avatar-img" alt="{{ $tu['name'] }}">
                                <div class="seat-initial-fallback" style="display:none;">{{ strtoupper(substr($tu['name'], 0, 2)) }}</div>
                            </div>

                            <div style="width:100%;margin-top:2px;">
                                <h4 style="font-size:0.75rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $tu['name'] }}">{{ $tu['name'] }}</h4>
                                <p style="font-size:9.5px;opacity:0.75;margin-top:2px;">Staff TU</p>
                            </div>
                            <div style="width:100%;margin-top:8px;">
                                <span style="display:block;padding:3px 2px;font-size:9px;font-weight:800;border-radius:8px;text-transform:uppercase;background:rgba(0,0,0,0.25);">
                                    {{ $tu['status_code'] === 'HADIR' ? '✅ ' . $tu['waktu_tap'] : ($tu['status_code'] === 'TERLAMBAT' ? '⚠️ ' . $tu['waktu_tap'] : ($tu['status_code'] === 'IZIN' ? '📋 IZIN' : '⚪ BELUM')) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- SLIDE 3+: KELAS SISWA (HANYA KELAS SISWA > 6 ORANG) --}}
            @foreach($classSlides as $cs)
                <div class="slide-item" data-title="DAFTAR SISWA KELAS {{ $cs['kelas'] }} ({{ $cs['total'] }} SISWA)">
                    <div class="cinema-grid">
                        @foreach($cs['student_grid'] as $st)
                            @php
                                $statusClass = match($st['status_code']) { 'HADIR' => 'seat-hadir', 'TERLAMBAT' => 'seat-terlambat', 'IZIN' => 'seat-izin', default => 'seat-belum' };
                                $ledClass = match($st['status_code']) { 'HADIR' => 'led-hadir', 'TERLAMBAT' => 'led-terlambat', 'IZIN' => 'led-izin', default => 'led-belum' };
                            @endphp
                            <div class="seat-pod {{ $statusClass }}">
                                <div style="width:100%;display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                    <span style="font-size:9px;font-weight:900;padding:2px 6px;border-radius:99px;background:rgba(0,0,0,0.3);color:#fff;">{{ $st['seat_number'] }}</span>
                                    <span class="led-dot {{ $ledClass }}"></span>
                                </div>

                                <div class="seat-avatar-box">
                                    <img src="{{ $st['avatar_url'] }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" class="seat-avatar-img" alt="{{ $st['name'] }}">
                                    <div class="seat-initial-fallback" style="display:none;">{{ strtoupper(substr($st['name'], 0, 2)) }}</div>
                                </div>

                                <div style="width:100%;margin-top:2px;">
                                    <h4 style="font-size:0.75rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $st['name'] }}">{{ $st['name'] }}</h4>
                                    <p style="font-size:9.5px;opacity:0.75;margin-top:2px;font-family:monospace;">{{ $st['nis'] }}</p>
                                </div>
                                <div style="width:100%;margin-top:8px;">
                                    <span style="display:block;padding:3px 2px;font-size:9px;font-weight:800;border-radius:8px;text-transform:uppercase;background:rgba(0,0,0,0.25);">
                                        {{ $st['status_code'] === 'HADIR' ? '✅ ' . $st['waktu_tap'] : ($st['status_code'] === 'TERLAMBAT' ? '⚠️ ' . $st['waktu_tap'] : ($st['status_code'] === 'IZIN' ? '📋 IZIN' : '⚪ BELUM')) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

        </div>

        {{-- Dots Pagination Indicator --}}
        <div class="slide-dots-bar" id="slideDotsBar"></div>

    </div>

    {{-- =========================================================
       LOGIN DRAWER OVERLAY (DESKTOP MODAL LOGIN FORM)
       ========================================================= --}}
    <div class="login-drawer-overlay {{ $errors->any() ? 'open' : '' }}" id="loginDrawerOverlay" onclick="if(event.target===this) toggleLoginDrawer(false)">
        <div class="login-drawer-card">
            <button class="drawer-close-btn" onclick="toggleLoginDrawer(false)">✕</button>

            <p class="form-eyebrow">Portal Masuk</p>
            <h2 class="form-title">Masuk ke Akun</h2>
            <p class="form-subtitle">Gunakan email Mailcow sekolah Anda untuk melanjutkan.</p>

            @if($errors->any())
                <div class="alert-error">
                    ⚠️ {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="field-group">
                    <label for="email">Username atau Email</label>
                    <input type="text" name="email" value="{{ old('email') }}" placeholder="NIS / email@smkbaknus.sch.id" required autofocus>
                </div>

                <div class="field-group">
                    <label for="password">Kata Sandi</label>
                    <input type="password" name="password" placeholder="Password Mailcow Anda" required>
                </div>

                <button type="submit" class="btn-login-submit">
                    Masuk Sekarang &nbsp;→
                </button>
            </form>
        </div>
    </div>

    {{-- =========================================================
       LAYAR KECIL / MOBILE (< 768PX) — FORM LOGIN DEFAULT
       ========================================================= --}}
    <div class="mobile-login-view">
        <div style="width:100%;max-width:380px;">
            <div style="text-align:center;margin-bottom:24px;">
                <img src="{{ asset('images/logo_BG.png') }}" alt="Logo" style="height:48px;margin:0 auto 8px;">
                <h1 style="font-size:1.5rem;font-weight:900;color:#0f172a;">BaknusAttend</h1>
                <p style="font-size:0.8rem;color:#64748b;">Sistem Presensi Digital SMK Bakti Nusantara 666</p>
            </div>

            @if($errors->any())
                <div class="alert-error">
                    ⚠️ {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" style="background:#fff;padding:28px;border-radius:20px;border:1px solid #e2e8f0;box-shadow:0 4px 16px rgba(0,0,0,0.05);">
                @csrf
                <div class="field-group">
                    <label>Username / Email</label>
                    <input type="text" name="email" value="{{ old('email') }}" placeholder="NIS / email@smkbaknus.sch.id" required autofocus>
                </div>

                <div class="field-group">
                    <label>Kata Sandi</label>
                    <input type="password" name="password" placeholder="Password Anda" required>
                </div>

                <button type="submit" class="btn-login-submit" style="margin-top:8px;">
                    Masuk Sekarang &nbsp;→
                </button>
            </form>
        </div>
    </div>

    {{-- Slideshow Auto-Slide Script (6 Detik) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const slides = document.querySelectorAll('.slide-item');
            const dotsBar = document.getElementById('slideDotsBar');
            const titleText = document.getElementById('screenTitleText');
            const progressBar = document.getElementById('slideProgressBar');
            
            if (!slides.length) return;

            let currentIndex = 0;
            let slideInterval = null;

            // Generate dots
            slides.forEach((slide, idx) => {
                const dot = document.createElement('div');
                dot.className = 'dot' + (idx === 0 ? ' active' : '');
                dot.addEventListener('click', () => goToSlide(idx));
                dotsBar.appendChild(dot);
            });

            const dots = dotsBar.querySelectorAll('.dot');

            function updateProgress() {
                progressBar.style.transition = 'none';
                progressBar.style.width = '0%';
                setTimeout(() => {
                    progressBar.style.transition = 'width 6s linear';
                    progressBar.style.width = '100%';
                }, 50);
            }

            function goToSlide(index) {
                slides[currentIndex].classList.remove('active');
                if (dots[currentIndex]) dots[currentIndex].classList.remove('active');

                currentIndex = index;

                slides[currentIndex].classList.add('active');
                if (dots[currentIndex]) dots[currentIndex].classList.add('active');

                const title = slides[currentIndex].getAttribute('data-title') || 'DAFTAR SISWA';
                titleText.textContent = title;

                updateProgress();
            }

            function nextSlide() {
                const nextIdx = (currentIndex + 1) % slides.length;
                goToSlide(nextIdx);
            }

            // Set initial title & start 6-second auto slide
            if (slides[0]) {
                titleText.textContent = slides[0].getAttribute('data-title') || 'DAFTAR SISWA';
            }
            updateProgress();
            slideInterval = setInterval(nextSlide, 6000);
        });

        function toggleLoginDrawer(open) {
            const drawer = document.getElementById('loginDrawerOverlay');
            if (open) {
                drawer.classList.add('open');
            } else {
                drawer.classList.remove('open');
            }
        }
    </script>
</body>

</html>