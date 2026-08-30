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
            --slide-duration: {{ $slideDuration ?? 6 }}s;
        }

        html, body {
            height: 100%;
            font-family: var(--font);
            background: #050811;
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
            background: radial-gradient(circle at top center, #1e1b4b 0%, #0f172a 55%, #03050c 100%);
            display: flex;
            flex-direction: column;
            padding: 16px 24px 20px;
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
            filter: drop-shadow(0 0 12px rgba(129, 140, 248, 0.6));
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
            background: linear-gradient(90deg, #3b82f6, #38bdf8);
            width: 0%;
            z-index: 999;
            transition: width var(--slide-duration) linear;
        }

        /* Proyeksi Layar Papan Tulis Bioskop */
        .screen-header-box {
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.25) 0%, rgba(15, 23, 42, 0.95) 100%);
            border-top: 4px solid #818cf8;
            border-bottom: 1.5px solid rgba(129, 140, 248, 0.3);
            border-radius: 12px 12px 40px 40px;
            padding: 10px 20px;
            text-align: center;
            box-shadow: 0 12px 35px -10px rgba(99, 102, 241, 0.4);
            margin: 10px auto 12px;
            max-width: 680px;
            width: 100%;
        }

        .screen-title {
            font-size: 0.95rem;
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #a5b4fc;
        }

        /* Interactive Slide Tabs Bar */
        .slide-tabs-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            padding: 4px 10px 10px;
            margin-bottom: 8px;
            width: 100%;
            scrollbar-width: none;
        }
        .slide-tabs-nav::-webkit-scrollbar { display: none; }

        .slide-tab-pill {
            padding: 7px 16px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 800;
            background: rgba(30, 41, 59, 0.65);
            color: #94a3b8;
            border: 1px solid rgba(255, 255, 255, 0.12);
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }

        .slide-tab-pill:hover {
            background: rgba(99, 102, 241, 0.35);
            color: #fff;
        }

        .slide-tab-pill.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            border-color: #60a5fa;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.55);
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
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 18px;
            width: 100%;
            padding: 4px;
        }

        @media (min-width: 1024px) {
            .cinema-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 20px;
            }
        }

        /* Seat Pod Card */
        .seat-pod {
            border-radius: 24px;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .seat-pod:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.65);
            z-index: 10;
        }

        /* =========================================================
           HIGH CONTRAST STATUS COLOR SCHEME
           ========================================================= */

        /* 1. HADIR (Electric Blue Background) */
        .seat-hadir {
            background: linear-gradient(145deg, #1d4ed8 0%, #2563eb 100%) !important;
            border: 2px solid #60a5fa !important;
            color: #ffffff !important;
            box-shadow: 0 8px 25px -4px rgba(37, 99, 235, 0.6) !important;
        }

        /* 2. TERLAMBAT (Amber / Gold Background) */
        .seat-terlambat {
            background: linear-gradient(145deg, #b45309 0%, #d97706 100%) !important;
            border: 2px solid #fcd34d !important;
            color: #ffffff !important;
            box-shadow: 0 8px 25px -4px rgba(217, 119, 6, 0.6) !important;
        }

        /* 3. IZIN / SAKIT (Purple Background) */
        .seat-izin {
            background: linear-gradient(145deg, #6b21a8 0%, #7e22ce 100%) !important;
            border: 2px solid #c084fc !important;
            color: #ffffff !important;
            box-shadow: 0 8px 25px -4px rgba(126, 34, 206, 0.6) !important;
        }

        /* 4. BELUM TAP (Dark Muted Slate Gray Background) */
        .seat-belum {
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%) !important;
            border: 1.5px solid #334155 !important;
            color: #94a3b8 !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
        }

        /* SUPER ENLARGED AVATAR BOX (96PX X 96PX) */
        .seat-avatar-box {
            width: 96px !important;
            height: 96px !important;
            min-width: 96px !important;
            min-height: 96px !important;
            max-width: 96px !important;
            max-height: 96px !important;
            border-radius: 50% !important;
            overflow: hidden !important;
            margin: 8px auto 10px !important;
            border: 3.5px solid rgba(255, 255, 255, 0.8) !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.45) !important;
            background: rgba(15, 23, 42, 0.7) !important;
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
            font-size: 1.4rem !important;
            color: #ffffff !important;
            background: rgba(99, 102, 241, 0.4) !important;
        }

        /* LED Lamp */
        .led-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .led-hadir { background: #60a5fa; box-shadow: 0 0 12px #60a5fa; }
        .led-terlambat { background: #fde047; box-shadow: 0 0 12px #fde047; }
        .led-izin { background: #e879f9; box-shadow: 0 0 12px #e879f9; }
        .led-belum { background: #64748b; opacity: 0.5; }

        /* Floating Login Button at Top Right */
        .btn-floating-login {
            position: fixed;
            top: 16px;
            right: 24px;
            z-index: 50;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            padding: 10px 22px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.5);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-floating-login:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.7);
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
        .form-eyebrow { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #2563eb; margin-bottom: 6px; }
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
        .field-group input:focus { border-color: #2563eb; background: #fff; box-shadow: 0 0 0 3.5px rgba(37, 99, 235, 0.15); }

        .btn-login-submit {
            width: 100%; padding: 14px; border-radius: 14px; border: none; cursor: pointer;
            background: linear-gradient(135deg, #1d4ed8, #2563eb); color: #fff;
            font-weight: 800; font-size: 0.9rem; font-family: var(--font);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35); transition: transform 0.15s;
        }
        .btn-login-submit:active { transform: scale(0.98); }

        /* Slide Arrow Navigation */
        .arrow-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 30;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.3rem;
            user-select: none;
        }
        .arrow-nav-btn:hover {
            background: #2563eb;
            border-color: #60a5fa;
            transform: translateY(-50%) scale(1.1);
        }
        .arrow-left { left: 12px; }
        .arrow-right { right: 12px; }
    </style>
</head>

<body>

    {{-- Progress Bar Timer --}}
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
                <span style="color:#34d399;font-weight:800;">⚡ Live Auto-Slide ({{ $slideDuration }}s)</span>
            </div>
        </div>

        {{-- Proyeksi Header Screen --}}
        <div class="screen-header-box">
            <p class="screen-title" id="screenTitleText">PRESENSI BAKNUSATTEND</p>
        </div>

        {{-- Interactive Slide Tabs Navigation --}}
        <div class="slide-tabs-nav" id="slideTabsNav">
            @php $slideIndexCounter = 0; @endphp
            @if($showGuru && count($teacherGrid) > 0)
                <div class="slide-tab-pill active" onclick="goToSlide({{ $slideIndexCounter++ }})">👨‍🏫 Dewan Guru</div>
            @endif
            @if($showTu && count($tuGrid) > 0)
                <div class="slide-tab-pill {{ !$showGuru ? 'active' : '' }}" onclick="goToSlide({{ $slideIndexCounter++ }})">💼 Staff TU</div>
            @endif
            @if($showKelas)
                @foreach($classSlides as $idx => $cs)
                    <div class="slide-tab-pill {{ (!$showGuru && !$showTu && $idx === 0) ? 'active' : '' }}" onclick="goToSlide({{ $slideIndexCounter++ }})">🎓 {{ $cs['kelas'] }} ({{ $cs['total'] }})</div>
                @endforeach
            @endif
        </div>

        {{-- Arrow Nav Buttons --}}
        <div class="arrow-nav-btn arrow-left" onclick="prevSlide()">❮</div>
        <div class="arrow-nav-btn arrow-right" onclick="nextSlide()">❯</div>

        {{-- Slides Container --}}
        <div class="slides-container" id="slidesContainer">

            @php $firstSlideActive = true; @endphp

            {{-- SLIDE 1: DEWAN GURU --}}
            @if($showGuru && count($teacherGrid) > 0)
                <div class="slide-item {{ $firstSlideActive ? 'active' : '' }}" data-title="DEWAN GURU — PRESENSI GURU">
                    @php $firstSlideActive = false; @endphp
                    <div class="cinema-grid">
                        @foreach($teacherGrid as $t)
                            @php
                                $statusClass = match($t['status_code']) { 'HADIR' => 'seat-hadir', 'TERLAMBAT' => 'seat-terlambat', 'IZIN' => 'seat-izin', default => 'seat-belum' };
                                $ledClass = match($t['status_code']) { 'HADIR' => 'led-hadir', 'TERLAMBAT' => 'led-terlambat', 'IZIN' => 'led-izin', default => 'led-belum' };
                            @endphp
                            <div class="seat-pod {{ $statusClass }}">
                                <div style="width:100%;display:flex;align-items:center;justify-content:space-between;margin-bottom:2px;">
                                    <span style="font-size:9.5px;font-weight:900;padding:2px 7px;border-radius:99px;background:rgba(0,0,0,0.35);color:#fff;">{{ $t['seat_number'] }}</span>
                                    <span class="led-dot {{ $ledClass }}"></span>
                                </div>

                                <div class="seat-avatar-box">
                                    <img src="{{ $t['avatar_url'] }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" class="seat-avatar-img" alt="{{ $t['name'] }}">
                                    <div class="seat-initial-fallback" style="display:none;">{{ strtoupper(substr($t['name'], 0, 2)) }}</div>
                                </div>

                                <div style="width:100%;margin-top:4px;">
                                    <h4 style="font-size:0.85rem;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $t['name'] }}">{{ $t['name'] }}</h4>
                                    <p style="font-size:10.5px;opacity:0.85;margin-top:1px;">Guru</p>
                                </div>

                                <div style="width:100%;margin-top:8px;">
                                    @if($t['status_code'] === 'HADIR' || $t['status_code'] === 'TERLAMBAT')
                                        <div style="background:rgba(0,0,0,0.35);padding:6px 4px;border-radius:12px;font-size:9.5px;line-height:1.35;text-align:center;border:1px solid rgba(255,255,255,0.25);">
                                            <div style="font-weight:900;">
                                                {{ $t['status_code'] === 'HADIR' ? '✅ HADIR' : '⚠️ TERLAMBAT' }} ({{ $t['tap_jam'] ?? $t['waktu_tap'] }})
                                            </div>
                                            <div style="font-size:9px;opacity:0.95;margin-top:2px;display:flex;align-items:center;justify-content:center;gap:3px;font-weight:700;">
                                                <span>{{ $t['tap_metode'] ?? '💳 RFID' }}</span>
                                                @if(!empty($t['tap_gps']))
                                                    <span>·</span>
                                                    <span title="GPS: {{ $t['tap_gps'] }}">📍 GPS</span>
                                                @endif
                                            </div>
                                            @if(!empty($t['tap_gps']))
                                                <div style="font-size:8px;opacity:0.85;margin-top:1px;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="GPS: {{ $t['tap_gps'] }}">
                                                    {{ $t['tap_gps'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($t['status_code'] === 'IZIN')
                                        <div style="background:rgba(0,0,0,0.35);padding:7px;border-radius:12px;font-size:10px;font-weight:900;border:1px solid rgba(255,255,255,0.2);">
                                            📋 IZIN / SAKIT
                                        </div>
                                    @else
                                        <div style="background:rgba(0,0,0,0.25);padding:7px;border-radius:12px;font-size:10px;font-weight:700;color:#94a3b8;border:1px solid rgba(255,255,255,0.08);">
                                            ⚪ BELUM TAP
                                        </div>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- SLIDE 2: STAFF TATA USAHA (TU) --}}
            @if($showTu && count($tuGrid) > 0)
                <div class="slide-item {{ $firstSlideActive ? 'active' : '' }}" data-title="STAFF TATA USAHA (TU) — PRESENSI STAF">
                    @php $firstSlideActive = false; @endphp
                    <div class="cinema-grid">
                        @foreach($tuGrid as $tu)
                            @php
                                $statusClass = match($tu['status_code']) { 'HADIR' => 'seat-hadir', 'TERLAMBAT' => 'seat-terlambat', 'IZIN' => 'seat-izin', default => 'seat-belum' };
                                $ledClass = match($tu['status_code']) { 'HADIR' => 'led-hadir', 'TERLAMBAT' => 'led-terlambat', 'IZIN' => 'led-izin', default => 'led-belum' };
                            @endphp
                            <div class="seat-pod {{ $statusClass }}">
                                <div style="width:100%;display:flex;align-items:center;justify-content:space-between;margin-bottom:2px;">
                                    <span style="font-size:9.5px;font-weight:900;padding:2px 7px;border-radius:99px;background:rgba(0,0,0,0.35);color:#fff;">{{ $tu['seat_number'] }}</span>
                                    <span class="led-dot {{ $ledClass }}"></span>
                                </div>

                                <div class="seat-avatar-box">
                                    <img src="{{ $tu['avatar_url'] }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" class="seat-avatar-img" alt="{{ $tu['name'] }}">
                                    <div class="seat-initial-fallback" style="display:none;">{{ strtoupper(substr($tu['name'], 0, 2)) }}</div>
                                </div>

                                <div style="width:100%;margin-top:4px;">
                                    <h4 style="font-size:0.85rem;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $tu['name'] }}">{{ $tu['name'] }}</h4>
                                    <p style="font-size:10.5px;opacity:0.85;margin-top:1px;">Staff TU</p>
                                </div>

                                <div style="width:100%;margin-top:8px;">
                                    @if($tu['status_code'] === 'HADIR' || $tu['status_code'] === 'TERLAMBAT')
                                        <div style="background:rgba(0,0,0,0.35);padding:6px 4px;border-radius:12px;font-size:9.5px;line-height:1.35;text-align:center;border:1px solid rgba(255,255,255,0.25);">
                                            <div style="font-weight:900;">
                                                {{ $tu['status_code'] === 'HADIR' ? '✅ HADIR' : '⚠️ TERLAMBAT' }} ({{ $tu['tap_jam'] ?? $tu['waktu_tap'] }})
                                            </div>
                                            <div style="font-size:9px;opacity:0.95;margin-top:2px;display:flex;align-items:center;justify-content:center;gap:3px;font-weight:700;">
                                                <span>{{ $tu['tap_metode'] ?? '💳 RFID' }}</span>
                                                @if(!empty($tu['tap_gps']))
                                                    <span>·</span>
                                                    <span title="GPS: {{ $tu['tap_gps'] }}">📍 GPS</span>
                                                @endif
                                            </div>
                                            @if(!empty($tu['tap_gps']))
                                                <div style="font-size:8px;opacity:0.85;margin-top:1px;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="GPS: {{ $tu['tap_gps'] }}">
                                                    {{ $tu['tap_gps'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($tu['status_code'] === 'IZIN')
                                        <div style="background:rgba(0,0,0,0.35);padding:7px;border-radius:12px;font-size:10px;font-weight:900;border:1px solid rgba(255,255,255,0.2);">
                                            📋 IZIN / SAKIT
                                        </div>
                                    @else
                                        <div style="background:rgba(0,0,0,0.25);padding:7px;border-radius:12px;font-size:10px;font-weight:700;color:#94a3b8;border:1px solid rgba(255,255,255,0.08);">
                                            ⚪ BELUM TAP
                                        </div>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- SLIDE KELAS SISWA --}}
            @if($showKelas)
                @foreach($classSlides as $cs)
                    <div class="slide-item {{ $firstSlideActive ? 'active' : '' }}" data-title="DAFTAR SISWA KELAS {{ $cs['kelas'] }} ({{ $cs['total'] }} SISWA)">
                        @php $firstSlideActive = false; @endphp
                        <div class="cinema-grid">
                            @foreach($cs['student_grid'] as $st)
                                @php
                                    $statusClass = match($st['status_code']) { 'HADIR' => 'seat-hadir', 'TERLAMBAT' => 'seat-terlambat', 'IZIN' => 'seat-izin', default => 'seat-belum' };
                                    $ledClass = match($st['status_code']) { 'HADIR' => 'led-hadir', 'TERLAMBAT' => 'led-terlambat', 'IZIN' => 'led-izin', default => 'led-belum' };
                                @endphp
                                <div class="seat-pod {{ $statusClass }}">
                                    <div style="width:100%;display:flex;align-items:center;justify-content:space-between;margin-bottom:2px;">
                                        <span style="font-size:9.5px;font-weight:900;padding:2px 7px;border-radius:99px;background:rgba(0,0,0,0.35);color:#fff;">{{ $st['seat_number'] }}</span>
                                        <span class="led-dot {{ $ledClass }}"></span>
                                    </div>

                                    <div class="seat-avatar-box">
                                        <img src="{{ $st['avatar_url'] }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" class="seat-avatar-img" alt="{{ $st['name'] }}">
                                        <div class="seat-initial-fallback" style="display:none;">{{ strtoupper(substr($st['name'], 0, 2)) }}</div>
                                    </div>

                                    <div style="width:100%;margin-top:4px;">
                                        <h4 style="font-size:0.85rem;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $st['name'] }}">{{ $st['name'] }}</h4>
                                        <p style="font-size:10.5px;opacity:0.85;margin-top:1px;font-family:monospace;">{{ $st['code'] }}</p>
                                    </div>

                                    <div style="width:100%;margin-top:8px;">
                                        @if($st['status_code'] === 'HADIR' || $st['status_code'] === 'TERLAMBAT')
                                            <div style="background:rgba(0,0,0,0.35);padding:6px 4px;border-radius:12px;font-size:9.5px;line-height:1.35;text-align:center;border:1px solid rgba(255,255,255,0.25);">
                                                <div style="font-weight:900;">
                                                    {{ $st['status_code'] === 'HADIR' ? '✅ HADIR' : '⚠️ TERLAMBAT' }} ({{ $st['tap_jam'] ?? $st['waktu_tap'] }})
                                                </div>
                                                <div style="font-size:9px;opacity:0.95;margin-top:2px;display:flex;align-items:center;justify-content:center;gap:3px;font-weight:700;">
                                                    <span>{{ $st['tap_metode'] ?? '💳 RFID' }}</span>
                                                    @if(!empty($st['tap_gps']))
                                                        <span>·</span>
                                                        <span title="GPS: {{ $st['tap_gps'] }}">📍 GPS</span>
                                                    @endif
                                                </div>
                                                @if(!empty($st['tap_gps']))
                                                    <div style="font-size:8px;opacity:0.85;margin-top:1px;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="GPS: {{ $st['tap_gps'] }}">
                                                        {{ $st['tap_gps'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($st['status_code'] === 'IZIN')
                                            <div style="background:rgba(0,0,0,0.35);padding:7px;border-radius:12px;font-size:10px;font-weight:900;border:1px solid rgba(255,255,255,0.2);">
                                                📋 IZIN / SAKIT
                                            </div>
                                        @else
                                            <div style="background:rgba(0,0,0,0.25);padding:7px;border-radius:12px;font-size:10px;font-weight:700;color:#94a3b8;border:1px solid rgba(255,255,255,0.08);">
                                                ⚪ BELUM TAP
                                            </div>
                                        @endif
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif

        </div>

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

    {{-- Slideshow Auto-Slide Script & Nav Controller --}}
    <script>
        let currentIndex = 0;
        let slideTimer = null;
        const slideDurationMs = {{ ($slideDuration ?? 6) * 1000 }};

        document.addEventListener('DOMContentLoaded', function () {
            const slides = document.querySelectorAll('.slide-item');
            if (!slides.length) return;

            // Set initial title & start auto slide
            goToSlide(0);
            startAutoTimer();
        });

        function updateProgress() {
            const progressBar = document.getElementById('slideProgressBar');
            if (!progressBar) return;
            progressBar.style.transition = 'none';
            progressBar.style.width = '0%';
            setTimeout(() => {
                progressBar.style.transition = `width ${slideDurationMs}ms linear`;
                progressBar.style.width = '100%';
            }, 50);
        }

        function goToSlide(index) {
            const slides = document.querySelectorAll('.slide-item');
            const tabs = document.querySelectorAll('.slide-tab-pill');
            const titleText = document.getElementById('screenTitleText');
            if (!slides.length) return;

            // Normalize index loop
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;

            slides[currentIndex].classList.remove('active');
            if (tabs[currentIndex]) tabs[currentIndex].classList.remove('active');

            currentIndex = index;

            slides[currentIndex].classList.add('active');
            if (tabs[currentIndex]) {
                tabs[currentIndex].classList.add('active');
                tabs[currentIndex].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }

            const title = slides[currentIndex].getAttribute('data-title') || 'PRESENSI BAKNUSATTEND';
            if (titleText) titleText.textContent = title;

            updateProgress();
            startAutoTimer();
        }

        function nextSlide() {
            const slides = document.querySelectorAll('.slide-item');
            if (!slides.length) return;
            goToSlide((currentIndex + 1) % slides.length);
        }

        function prevSlide() {
            const slides = document.querySelectorAll('.slide-item');
            if (!slides.length) return;
            goToSlide((currentIndex - 1 + slides.length) % slides.length);
        }

        function startAutoTimer() {
            if (slideTimer) clearInterval(slideTimer);
            slideTimer = setInterval(nextSlide, slideDurationMs);
        }

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