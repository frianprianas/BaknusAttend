<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BaknusAttend – Sistem Presensi Digital SMK Bakti Nusantara 666">
    <title>BaknusAttend – Sistem Presensi Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --indigo-500: #6366f1;
            --indigo-600: #4f46e5;
            --indigo-400: #818cf8;
            --font: 'Plus Jakarta Sans', -apple-system, sans-serif;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font);
            min-height: 100vh;
            background: #020817;
            color: #e2e8f0;
            -webkit-font-smoothing: antialiased;
        }

        /* ========== NAV ========== */
        .nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 48px;
            background: rgba(2,8,23,.8);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .nav-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .nav-brand-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: linear-gradient(135deg, var(--indigo-600), var(--indigo-400));
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(99,102,241,.45);
        }
        .nav-brand-name {
            font-size: 1.05rem; font-weight: 800; color: #fff; letter-spacing: -.01em;
        }
        .nav-brand-name span { color: #a5b4fc; }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .nav-btn {
            padding: 8px 20px; border-radius: 10px; font-family: var(--font);
            font-size: .85rem; font-weight: 700; text-decoration: none;
            transition: all .15s; cursor: pointer; border: none;
        }
        .nav-btn-ghost {
            background: rgba(255,255,255,.06); color: #cbd5e1;
            border: 1px solid rgba(255,255,255,.1);
        }
        .nav-btn-ghost:hover { background: rgba(255,255,255,.12); color: #fff; }
        .nav-btn-primary {
            background: linear-gradient(135deg, var(--indigo-600), var(--indigo-500));
            color: #fff;
            box-shadow: 0 4px 14px rgba(99,102,241,.40);
        }
        .nav-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(99,102,241,.50); }

        /* ========== HERO ========== */
        .hero {
            min-height: 100vh;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center;
            padding: 120px 24px 80px;
            position: relative; overflow: hidden;
        }

        /* background mesh */
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 50% -10%, rgba(99,102,241,.28), transparent),
                radial-gradient(ellipse 60% 40% at 80% 80%, rgba(139,92,246,.15), transparent),
                radial-gradient(ellipse 50% 50% at 10% 90%, rgba(56,189,248,.1), transparent);
        }

        /* fine grid */
        .hero::after {
            content: '';
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.02) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        .hero-content { position: relative; z-index: 10; max-width: 760px; }

        .hero-pill {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(99,102,241,.14); border: 1px solid rgba(165,180,252,.25);
            backdrop-filter: blur(8px); color: #c7d2fe;
            font-size: .72rem; font-weight: 700; padding: 7px 18px;
            border-radius: 999px; margin-bottom: 32px;
            letter-spacing: .06em; text-transform: uppercase;
        }
        .pill-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #4ade80; box-shadow: 0 0 7px #4ade80;
            animation: pulse 1.8s ease-in-out infinite;
        }
        @keyframes pulse {
            0%,100% { opacity:1; transform:scale(1); }
            50%      { opacity:.5; transform:scale(1.35); }
        }

        .hero-headline {
            font-size: clamp(2.4rem, 6vw, 4.5rem);
            font-weight: 900; color: #fff; line-height: 1.08;
            letter-spacing: -0.04em; margin-bottom: 24px;
        }
        .hero-headline span {
            background: linear-gradient(100deg, #c7d2fe 20%, #818cf8 60%, #a78bfa 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc {
            font-size: 1.05rem; color: #94a3b8; line-height: 1.75;
            max-width: 520px; margin: 0 auto 44px;
        }

        .hero-cta { display: flex; align-items: center; justify-content: center; gap: 14px; flex-wrap: wrap; }
        .cta-primary {
            padding: 15px 32px; border-radius: 14px;
            background: linear-gradient(135deg, var(--indigo-600), var(--indigo-500));
            color: #fff; font-weight: 800; font-size: .9375rem;
            text-decoration: none; letter-spacing: .02em;
            box-shadow: 0 8px 28px rgba(99,102,241,.45);
            transition: transform .15s, box-shadow .15s;
            display: inline-flex; align-items: center; gap: 8px;
            position: relative; overflow: hidden;
        }
        .cta-primary::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,.12), transparent);
        }
        .cta-primary:hover { transform: translateY(-2px); box-shadow: 0 14px 40px rgba(99,102,241,.55); }

        .cta-secondary {
            padding: 15px 32px; border-radius: 14px;
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12);
            color: #cbd5e1; font-weight: 700; font-size: .9375rem;
            text-decoration: none; letter-spacing: .02em;
            transition: all .15s; backdrop-filter: blur(8px);
        }
        .cta-secondary:hover { background: rgba(255,255,255,.1); color: #fff; transform: translateY(-1px); }

        /* ========== STATS STRIP ========== */
        .stats-strip {
            position: relative; z-index: 10;
            display: flex; align-items: center; justify-content: center;
            gap: 0; margin-top: 72px;
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
            border-radius: 20px; padding: 24px 40px; backdrop-filter: blur(12px);
            flex-wrap: wrap;
        }
        .stat-block { text-align: center; padding: 0 32px; }
        .stat-block + .stat-block {
            border-left: 1px solid rgba(255,255,255,.08);
        }
        .stat-num {
            font-size: 2rem; font-weight: 900; color: #fff;
            letter-spacing: -0.03em; line-height: 1;
        }
        .stat-lbl {
            font-size: .65rem; font-weight: 700; color: #475569;
            text-transform: uppercase; letter-spacing: .1em; margin-top: 6px;
        }

        /* ========== FEATURES ========== */
        .features {
            padding: 100px 24px;
            max-width: 1100px; margin: 0 auto;
        }
        .section-label {
            text-align: center; font-size: .7rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: .14em; color: var(--indigo-400);
            margin-bottom: 14px;
        }
        .section-title {
            text-align: center; font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 900; color: #fff; letter-spacing: -0.03em;
            margin-bottom: 12px; line-height: 1.15;
        }
        .section-sub {
            text-align: center; font-size: .9375rem; color: #64748b;
            max-width: 460px; margin: 0 auto 60px; line-height: 1.7;
        }

        .features-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
        }

        .feat-card {
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
            border-radius: 20px; padding: 28px 24px;
            transition: transform .2s, box-shadow .2s, border-color .2s;
        }
        .feat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(0,0,0,.25);
            border-color: rgba(99,102,241,.3);
        }
        .feat-icon {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 18px; font-size: 1.5rem;
        }
        .feat-title { font-size: 1rem; font-weight: 800; color: #f1f5f9; margin-bottom: 8px; }
        .feat-desc  { font-size: .875rem; color: #64748b; line-height: 1.65; }

        /* ========== FOOTER ========== */
        .footer {
            border-top: 1px solid rgba(255,255,255,.06);
            padding: 32px 48px;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
        }
        .footer-brand { font-size: .875rem; font-weight: 700; color: #475569; }
        .footer-brand span { color: #6366f1; }
        .footer-copy { font-size: .8rem; color: #334155; }

        /* ========== RESPONSIVE ========== */
        @media(max-width: 900px) {
            .nav       { padding: 14px 20px; }
            .features-grid { grid-template-columns: 1fr; }
            .stats-strip { flex-direction: column; gap: 20px; }
            .stat-block + .stat-block { border-left: none; border-top: 1px solid rgba(255,255,255,.08); padding-top: 20px; }
            .footer { flex-direction: column; text-align: center; padding: 24px 20px; }
        }
        @media(max-width: 640px) {
            .nav-brand-name { display: none; }
            .hero { padding: 100px 20px 60px; }
        }
    </style>
</head>
<body>

    {{-- ===== NAVBAR ===== --}}
    <nav class="nav">
        <a href="/" class="nav-brand">
            <div class="nav-brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.8" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V9l9-6 9 6v12"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 21V12h6v9"/>
                </svg>
            </div>
            <span class="nav-brand-name">Baknus<span>Attend</span></span>
        </a>

        @if(Route::has('login'))
            <div class="nav-actions">
                @auth
                    <a href="{{ url('/dashboard') }}" class="nav-btn nav-btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="nav-btn nav-btn-ghost">Masuk</a>
                    @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="nav-btn nav-btn-primary">Daftar</a>
                    @endif
                @endauth
            </div>
        @endif
    </nav>

    {{-- ===== HERO ===== --}}
    <section class="hero">
        <div class="hero-content">
            <div class="hero-pill">
                <div class="pill-dot"></div>
                Sistem Presensi Digital Aktif
            </div>

            <h1 class="hero-headline">
                Absensi Cerdas untuk<br>
                <span>SMK Bakti Nusantara 666</span>
            </h1>

            <p class="hero-desc">
                Platform manajemen presensi digital terintegrasi yang akurat, real-time, dan mudah
                digunakan untuk seluruh civitas akademika.
            </p>

            <div class="hero-cta">
                @auth
                    <a href="{{ url('/dashboard') }}" class="cta-primary">
                        Buka Dashboard &nbsp;→
                    </a>
                @else
                    <a href="{{ route('login') }}" class="cta-primary">
                        Mulai Sekarang &nbsp;→
                    </a>
                    <a href="#fitur" class="cta-secondary">Lihat Fitur</a>
                @endauth
            </div>

            {{-- Stats --}}
            <div class="stats-strip">
                <div class="stat-block">
                    <div class="stat-num">100%</div>
                    <div class="stat-lbl">Berbasis Digital</div>
                </div>
                <div class="stat-block">
                    <div class="stat-num">3</div>
                    <div class="stat-lbl">Peran Pengguna</div>
                </div>
                <div class="stat-block">
                    <div class="stat-num">RFID</div>
                    <div class="stat-lbl">Terintegrasi</div>
                </div>
                <div class="stat-block">
                    <div class="stat-num">GPS</div>
                    <div class="stat-lbl">Verifikasi Lokasi</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== FEATURES ===== --}}
    <section id="fitur" class="features">
        <p class="section-label">Fitur Unggulan</p>
        <h2 class="section-title">Semua yang Anda Butuhkan</h2>
        <p class="section-sub">Dirancang khusus untuk kebutuhan presensi sekolah modern yang akurat dan efisien.</p>

        <div class="features-grid">
            <div class="feat-card">
                <div class="feat-icon" style="background:rgba(99,102,241,.15);">📍</div>
                <div class="feat-title">Verifikasi GPS</div>
                <div class="feat-desc">Memastikan absensi dilakukan dari lokasi yang tepat dengan akurasi tinggi secara real-time.</div>
            </div>
            <div class="feat-card">
                <div class="feat-icon" style="background:rgba(16,185,129,.15);">📷</div>
                <div class="feat-title">Deteksi Wajah</div>
                <div class="feat-desc">Validasi foto selfie menggunakan AI untuk memastikan keabsahan identitas peserta didik.</div>
            </div>
            <div class="feat-card">
                <div class="feat-icon" style="background:rgba(245,158,11,.15);">📋</div>
                <div class="feat-title">Izin & Sakit Digital</div>
                <div class="feat-desc">Pengajuan izin atau sakit secara online lengkap dengan bukti lampiran dan status persetujuan.</div>
            </div>
            <div class="feat-card">
                <div class="feat-icon" style="background:rgba(56,189,248,.15);">📅</div>
                <div class="feat-title">Kalender Kehadiran</div>
                <div class="feat-desc">Visualisasi riwayat presensi bulanan dalam tampilan kalender yang informatif dan intuitif.</div>
            </div>
            <div class="feat-card">
                <div class="feat-icon" style="background:rgba(168,85,247,.15);">🏷️</div>
                <div class="feat-title">Integrasi RFID</div>
                <div class="feat-desc">Mendukung perangkat RFID untuk presensi otomatis tanpa sentuh langsung di gerbang sekolah.</div>
            </div>
            <div class="feat-card">
                <div class="feat-icon" style="background:rgba(239,68,68,.15);">📊</div>
                <div class="feat-title">Laporan Real-time</div>
                <div class="feat-desc">Dashboard admin dengan rekap data kehadiran yang bisa diexport dan dimonitor kapan saja.</div>
            </div>
        </div>
    </section>

    {{-- ===== FOOTER ===== --}}
    <footer class="footer">
        <div class="footer-brand">Baknus<span>Attend</span> &nbsp;·&nbsp; SMK Bakti Nusantara 666</div>
        <div class="footer-copy">&copy; {{ date('Y') }} IT Dept. SMK Bakti Nusantara 666. All rights reserved.</div>
    </footer>

</body>
</html>
