{{-- resources/views/login.blade.php --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
    *, *::before, *::after { box-sizing: border-box; }

    body, html {
        margin: 0;
        padding: 0;
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    /* ========== LAYOUT ========== */
    .lw {
        display: flex;
        min-height: 100vh;
    }

    /* ========== LEFT PANEL ========== */
    .lw-left {
        width: 55%;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding: 56px;
        background:
            linear-gradient(155deg,
                rgba(9,  12,  50, 0.94) 0%,
                rgba(13, 20,  68, 0.91) 52%,
                rgba(32, 11, 110, 0.89) 100%),
            url("{{ asset('images/BA.png') }}") center / cover no-repeat;
    }

    /* Grid overlay */
    .lw-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
        background-size: 48px 48px;
        pointer-events: none;
    }

    /* Glowing orbs */
    .orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(90px);
        pointer-events: none;
    }
    .orb-1 {
        width: 420px; height: 420px;
        top: -90px; right: -100px;
        background: radial-gradient(circle, rgba(99,102,241,.40), transparent 68%);
        animation: floatA 9s ease-in-out infinite;
    }
    .orb-2 {
        width: 320px; height: 320px;
        bottom: -50px; left: -70px;
        background: radial-gradient(circle, rgba(139,92,246,.28), transparent 68%);
        animation: floatB 12s ease-in-out infinite;
    }
    .orb-3 {
        width: 180px; height: 180px;
        top: 46%; left: 30%;
        background: radial-gradient(circle, rgba(167,139,250,.14), transparent 70%);
        animation: floatA 15s ease-in-out infinite;
    }

    @keyframes floatA {
        0%,100% { transform: translateY(0) scale(1); }
        50%      { transform: translateY(-28px) scale(1.04); }
    }
    @keyframes floatB {
        0%,100% { transform: translateY(0) scale(1); }
        50%      { transform: translateY(24px) scale(1.03); }
    }

    /* Brand bar */
    .lw-brand {
        display: flex;
        align-items: center;
        gap: 14px;
        position: relative;
        z-index: 10;
        margin-bottom: auto;
    }
    .lw-brand-logo {
        height: 52px;
        width: auto;
        object-fit: contain;
        filter: drop-shadow(0 0 14px rgba(165,180,252,.45));
    }
    .lw-brand-text {
        font-size: 1.15rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -0.01em;
    }
    .lw-brand-text span { color: #a5b4fc; }

    /* Hero */
    .lw-hero {
        position: relative;
        z-index: 10;
        margin-top: auto;
        padding-bottom: 8px;
    }

    .lw-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(99,102,241,.18);
        border: 1px solid rgba(165,180,252,.30);
        backdrop-filter: blur(10px);
        color: #c7d2fe;
        font-size: .7rem;
        font-weight: 700;
        padding: 7px 16px;
        border-radius: 999px;
        margin-bottom: 28px;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .pill-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        background: #4ade80;
        box-shadow: 0 0 7px #4ade80;
        animation: pls 1.8s ease-in-out infinite;
    }
    @keyframes pls {
        0%,100% { opacity:1; transform: scale(1); }
        50%      { opacity:.5; transform: scale(1.35); }
    }

    .lw-headline {
        font-size: clamp(2rem, 3.1vw, 3.1rem);
        font-weight: 900;
        color: #fff;
        line-height: 1.13;
        letter-spacing: -0.03em;
        margin: 0 0 22px;
    }
    .lw-headline span {
        background: linear-gradient(100deg, #c7d2fe, #818cf8 65%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .lw-desc {
        color: #94a3b8;
        font-size: .9375rem;
        line-height: 1.75;
        max-width: 380px;
        margin: 0 0 40px;
    }

    .lw-stats { display: flex; align-items: center; }
    .stat-i  { display: flex; flex-direction: column; }
    .stat-v  { font-size: 1.85rem; font-weight: 900; color: #fff; line-height: 1; letter-spacing: -0.03em; }
    .stat-l  { font-size: .6rem; color: #64748b; text-transform: uppercase; letter-spacing: .1em; margin-top: 5px; font-weight: 700; }
    .stat-d  { width: 1px; height: 36px; background: rgba(255,255,255,.1); margin: 0 28px; }

    /* ========== RIGHT PANEL ========== */
    .lw-right {
        width: 45%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        padding: 48px 52px;
        position: relative;
    }
    .lw-right::before {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 300px; height: 300px;
        background: radial-gradient(circle at top right, #eef2ff, transparent 65%);
        pointer-events: none;
    }
    .lw-right::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0;
        width: 200px; height: 200px;
        background: radial-gradient(circle at bottom left, #f0fdf4, transparent 65%);
        pointer-events: none;
    }

    .lw-form-box {
        width: 100%;
        max-width: 400px;
        position: relative;
        z-index: 10;
    }

    /* Form header */
    .lw-icon-badge {
        width: 54px; height: 54px;
        border-radius: 14px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        box-shadow: 0 8px 28px rgba(99,102,241,.38);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 28px;
    }

    .lw-eyebrow {
        font-size: .7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .14em;
        color: #6366f1;
        margin: 0 0 8px;
    }
    .lw-title {
        font-size: 1.95rem;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.03em;
        margin: 0 0 8px;
        line-height: 1.1;
    }
    .lw-subtitle {
        font-size: .875rem;
        color: #64748b;
        line-height: 1.65;
        margin: 0 0 36px;
    }

    /* Mobile header */
    .lw-mobile-header {
        display: none;
        flex-direction: column;
        align-items: center;
        text-align: center;
        margin-bottom: 36px;
    }
    .lw-mobile-icon {
        width: 72px; height: 72px;
        border-radius: 20px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        box-shadow: 0 10px 30px rgba(99,102,241,.4);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    .lw-mobile-title {
        font-size: 1.5rem;
        font-weight: 900;
        color: #0f172a;
        margin: 0 0 4px;
        letter-spacing: -0.02em;
    }
    .lw-mobile-sub { font-size: .875rem; color: #64748b; margin: 0; }

    /* Filament overrides */
    .fi-simple-layout, .fi-simple-main, .fi-simple-page {
        background: transparent !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .fi-input-wrp {
        background-color: #f8fafc !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 14px !important;
        box-shadow: none !important;
        transition: border-color .2s, box-shadow .2s !important;
    }
    .fi-input-wrp:focus-within {
        background-color: #fff !important;
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 4px rgba(99,102,241,.11) !important;
    }
    .fi-input {
        color: #0f172a !important;
        font-size: .9375rem !important;
        font-weight: 500 !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }
    .fi-fo-field-wrp-label label {
        color: #334155 !important;
        font-size: .78rem !important;
        font-weight: 700 !important;
        letter-spacing: .02em !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }
    .fi-fo-field-wrp { margin-bottom: 0 !important; }

    /* Login button */
    .lw-btn {
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 15px 24px;
        border-radius: 14px;
        border: none;
        cursor: pointer;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: .9375rem;
        font-weight: 800;
        color: #fff;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        box-shadow: 0 8px 28px rgba(99,102,241,.40);
        transition: transform .15s, box-shadow .15s;
        letter-spacing: .02em;
        overflow: hidden;
    }
    .lw-btn::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,.13), transparent);
        pointer-events: none;
    }
    .lw-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 40px rgba(99,102,241,.48);
    }
    .lw-btn:active { transform: scale(.98); }

    .lw-help {
        margin-top: 20px;
        text-align: center;
        font-size: .8rem;
        color: #94a3b8;
    }
    .lw-help a {
        color: #6366f1;
        font-weight: 700;
        text-decoration: none;
    }
    .lw-help a:hover { color: #4f46e5; }

    .lw-footer {
        margin-top: 44px;
        padding-top: 18px;
        border-top: 1px solid #f1f5f9;
        text-align: center;
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: #c1cad6;
    }

    /* Spinner */
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Responsive */
    @media (max-width: 1024px) {
        .lw-left   { display: none; }
        .lw-right  { width: 100%; padding: 40px 24px; }
        .lw-mobile-header { display: flex !important; }
    }
    @media (min-width: 1025px) {
        .lw-mobile-header { display: none !important; }
        .lw-eyebrow, .lw-title, .lw-subtitle { display: block; }
    }
</style>

<div class="lw">

    {{-- ======= LEFT PANEL ======= --}}
    <div class="lw-left">
        <div class="lw-grid"></div>
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <div class="lw-brand">
            <img src="{{ asset('images/logo_BG.png') }}" alt="Logo" class="lw-brand-logo">
            <span class="lw-brand-text">Baknus<span>Attend</span></span>
        </div>

        <div class="lw-hero">
            <div class="lw-pill">
                <div class="pill-dot"></div>
                Sistem Presensi Aktif
            </div>
            <h1 class="lw-headline">
                Hadir Tepat Waktu,<br>
                <span>Setiap Hari.</span>
            </h1>
            <p class="lw-desc">
                Sistem absensi digital terintegrasi Mailcow untuk seluruh civitas akademika
                SMK Bakti Nusantara 666. Akurat, cepat, dan real-time.
            </p>
            <div class="lw-stats">
                <div class="stat-i"><span class="stat-v">100%</span><span class="stat-l">Digital</span></div>
                <div class="stat-d"></div>
                <div class="stat-i"><span class="stat-v">3</span><span class="stat-l">Peran User</span></div>
                <div class="stat-d"></div>
                <div class="stat-i"><span class="stat-v">RFID</span><span class="stat-l">Terintegrasi</span></div>
            </div>
        </div>
    </div>

    {{-- ======= RIGHT PANEL ======= --}}
    <div class="lw-right">
        <div class="lw-form-box">

            {{-- Mobile header --}}
            <div class="lw-mobile-header">
                <div class="lw-mobile-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:36px;height:36px;">
                        <path d="M3 21V9l9-6 9 6v12"/><path d="M9 21V12h6v9"/>
                    </svg>
                </div>
                <p class="lw-mobile-title">BaknusAttend</p>
                <p class="lw-mobile-sub">SMK Bakti Nusantara 666</p>
            </div>

            {{-- Desktop header --}}
            <div class="lw-icon-badge">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:28px;height:28px;">
                    <path d="M3 21V9l9-6 9 6v12"/><path d="M9 21V12h6v9"/>
                </svg>
            </div>

            <p class="lw-eyebrow">Portal Masuk</p>
            <h2 class="lw-title">Masuk ke Akun Anda</h2>
            <p class="lw-subtitle">Gunakan email Mailcow Anda untuk melanjutkan ke dashboard administrasi.</p>

            {{-- Form --}}
            <form wire:submit.prevent="authenticate">
                <div style="display:flex; flex-direction:column; gap:16px; margin-bottom:24px;">
                    {{ $this->form }}
                </div>

                <button type="submit" class="lw-btn"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="authenticate">Masuk Sekarang &nbsp;→</span>
                    <span wire:loading wire:target="authenticate" style="display:flex;align-items:center;gap:8px;">
                        <svg style="animation:spin 1s linear infinite;width:18px;height:18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memverifikasi...
                    </span>
                </button>
            </form>

            <p class="lw-help">
                Lupa password? <a href="#">Hubungi IT Administrator</a>
            </p>

            <div class="lw-footer">
                &copy; {{ date('Y') }} &nbsp;&middot;&nbsp; IT Dept. SMK Bakti Nusantara 666
            </div>
        </div>
    </div>

</div>

@livewire('notifications')