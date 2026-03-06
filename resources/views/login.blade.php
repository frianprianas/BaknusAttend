{{-- resources/views/login.blade.php --}}
<style>
    * {
        box-sizing: border-box;
    }

    body,
    html {
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    .login-wrapper {
        display: flex;
        min-height: 100vh;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    /* ======================= LEFT PANEL ======================= */
    .login-left {
        width: 55%;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding: 56px;
        background: linear-gradient(150deg, #0d1b3e 0%, #0f2044 60%, #1a105e 100%);
    }

    .login-left-grid {
        position: absolute;
        inset: 0;
        background-image: linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
        background-size: 44px 44px;
    }

    .blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        pointer-events: none;
    }

    .blob-1 {
        width: 380px;
        height: 380px;
        top: -60px;
        right: -80px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.35), transparent 70%);
        animation: floatA 8s ease-in-out infinite;
    }

    .blob-2 {
        width: 320px;
        height: 320px;
        bottom: -40px;
        left: -60px;
        background: radial-gradient(circle, rgba(129, 140, 248, 0.25), transparent 70%);
        animation: floatB 10s ease-in-out infinite;
    }

    .blob-3 {
        width: 220px;
        height: 220px;
        top: 45%;
        left: 28%;
        background: radial-gradient(circle, rgba(165, 180, 252, 0.15), transparent 70%);
        animation: floatA 13s ease-in-out infinite;
    }

    @keyframes floatA {

        0%,
        100% {
            transform: translateY(0) rotate(0deg);
        }

        50% {
            transform: translateY(-22px) rotate(4deg);
        }
    }

    @keyframes floatB {

        0%,
        100% {
            transform: translateY(0) rotate(0deg);
        }

        50% {
            transform: translateY(20px) rotate(-4deg);
        }
    }

    .login-logo-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        z-index: 10;
        margin-bottom: auto;
    }

    .login-logo-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        backdrop-filter: blur(8px);
    }

    .login-logo-box img {
        width: 34px;
        height: 34px;
        object-fit: contain;
    }

    .login-brand-name {
        color: #fff;
        font-weight: 700;
        font-size: 1.125rem;
        letter-spacing: 0.02em;
    }

    .login-brand-name span {
        color: #a5b4fc;
    }

    .login-left-content {
        position: relative;
        z-index: 10;
        margin-top: auto;
        padding-bottom: 24px;
    }

    .login-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(99, 102, 241, 0.18);
        border: 1px solid rgba(165, 180, 252, 0.3);
        color: #c7d2fe;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 999px;
        margin-bottom: 24px;
        backdrop-filter: blur(6px);
    }

    .badge-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #4ade80;
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: .5;
            transform: scale(1.3);
        }
    }

    .login-headline {
        font-size: clamp(2rem, 3.5vw, 3rem);
        font-weight: 800;
        color: #fff;
        line-height: 1.18;
        letter-spacing: -0.02em;
        margin: 0 0 20px 0;
    }

    .login-headline span {
        background: linear-gradient(90deg, #a5b4fc, #818cf8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .login-desc {
        color: #94a3b8;
        font-size: 0.9375rem;
        line-height: 1.7;
        max-width: 360px;
        margin: 0 0 36px 0;
    }

    .login-stats {
        display: flex;
        align-items: center;
        gap: 0;
    }

    .stat-item {
        display: flex;
        flex-direction: column;
    }

    .stat-val {
        font-size: 1.75rem;
        font-weight: 800;
        color: #fff;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.65rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-top: 4px;
    }

    .stat-divider {
        width: 1px;
        height: 36px;
        background: rgba(255, 255, 255, 0.1);
        margin: 0 28px;
    }

    /* ======================= RIGHT PANEL ======================= */
    .login-right {
        width: 45%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        padding: 40px 48px;
        position: relative;
    }

    .login-right::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 280px;
        height: 280px;
        pointer-events: none;
        background: radial-gradient(circle at top right, #eef2ff, transparent 65%);
    }

    .login-form-box {
        width: 100%;
        max-width: 400px;
        position: relative;
        z-index: 10;
    }

    .login-greeting-small {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #6366f1;
        margin: 0 0 8px 0;
    }

    .login-title {
        font-size: 1.875rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
        margin: 0 0 8px 0;
        line-height: 1.2;
    }

    .login-subtitle {
        font-size: 0.875rem;
        color: #64748b;
        line-height: 1.65;
        margin: 0 0 36px 0;
    }

    /* Filament Input Overrides */
    .fi-simple-layout,
    .fi-simple-main,
    .fi-simple-page {
        background: transparent !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    .fi-input-wrp {
        background-color: #f8fafc !important;
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 12px !important;
        box-shadow: none !important;
        transition: border-color .2s, box-shadow .2s !important;
    }

    .fi-input-wrp:focus-within {
        background-color: #fff !important;
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, .12) !important;
    }

    .fi-input {
        color: #0f172a !important;
        font-size: 0.9375rem !important;
        font-weight: 500 !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }

    .fi-fo-field-wrp-label label {
        color: #1e293b !important;
        font-size: 0.8rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.02em !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }

    .fi-fo-field-wrp {
        margin-bottom: 0 !important;
    }

    /* Login Button */
    .login-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 24px;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.9375rem;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        box-shadow: 0 6px 24px rgba(99, 102, 241, 0.4);
        transition: transform .15s, box-shadow .15s;
    }

    .login-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 30px rgba(99, 102, 241, 0.45);
    }

    .login-btn:active {
        transform: scale(0.98);
    }

    .login-help {
        margin-top: 20px;
        text-align: center;
        font-size: 0.8rem;
        color: #94a3b8;
    }

    .login-help a {
        color: #6366f1;
        font-weight: 600;
        text-decoration: none;
    }

    .login-help a:hover {
        color: #4f46e5;
    }

    .login-footer {
        margin-top: 48px;
        padding-top: 20px;
        border-top: 1px solid #f1f5f9;
        text-align: center;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #cbd5e1;
    }

    /* ======================= RESPONSIVE ======================= */
    @media (max-width: 1024px) {
        .login-left {
            display: none;
        }

        .login-right {
            width: 100%;
            padding: 40px 24px;
        }

        .login-mobile-header {
            display: flex !important;
            flex-direction: column;
            align-items: center;
            margin-bottom: 36px;
        }
    }

    @media (min-width: 1024px) {
        .login-mobile-header {
            display: none !important;
        }
    }

    .login-mobile-header {
        display: none;
        text-align: center;
    }

    .login-mobile-header img {
        height: 64px;
        width: auto;
        margin-bottom: 12px;
    }

    .login-mobile-header h1 {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 4px;
    }

    .login-mobile-header p {
        font-size: 0.875rem;
        color: #64748b;
        margin: 0;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<div class="login-wrapper">

    {{-- ======= LEFT PANEL ======= --}}
    <div class="login-left">
        <div class="login-left-grid"></div>
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>

        {{-- Logo bar --}}
        <div class="login-logo-bar">
            <div class="login-logo-box">
                {{-- SVG ikon gedung - tanpa file gambar eksternal --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white"
                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:26px;height:26px;">
                    <path d="M3 21V9l9-6 9 6v12" />
                    <path d="M9 21V12h6v9" />
                    <path d="M12 3v3" />
                </svg>
            </div>
            <span class="login-brand-name">Baknus<span>Attend</span></span>
        </div>

        {{-- Main content --}}
        <div class="login-left-content">
            <div class="login-badge">
                <div class="badge-dot"></div>
                Sistem Presensi Aktif
            </div>

            <h1 class="login-headline">
                Hadir Tepat Waktu,<br>
                <span>Setiap Hari.</span>
            </h1>

            <p class="login-desc">
                Sistem absensi digital terintegrasi Mailcow untuk seluruh civitas akademika SMK Bakti Nusantara 666.
                Akurat, cepat, dan real-time.
            </p>

            <div class="login-stats">
                <div class="stat-item">
                    <span class="stat-val">100%</span>
                    <span class="stat-label">Digital</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-val">3</span>
                    <span class="stat-label">Peran User</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-val">RFID</span>
                    <span class="stat-label">Terintegrasi</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ======= RIGHT PANEL ======= --}}
    <div class="login-right">
        <div class="login-form-box">

            {{-- Mobile header --}}
            <div class="login-mobile-header">
                <div
                    style="width:72px;height:72px;border-radius:20px;background:linear-gradient(135deg,#4f46e5,#6366f1);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white"
                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        style="width:36px;height:36px;">
                        <path d="M3 21V9l9-6 9 6v12" />
                        <path d="M9 21V12h6v9" />
                        <path d="M12 3v3" />
                    </svg>
                </div>
                <h1>BaknusAttend</h1>
                <p>SMK Bakti Nusantara 666</p>
            </div>

            {{-- Desktop greeting --}}
            <div style="display:none" id="desktopGreeting">
                <p class="login-greeting-small">Portal Masuk</p>
                <h2 class="login-title">Masuk ke Akun Anda</h2>
                <p class="login-subtitle">Gunakan email Mailcow Anda untuk melanjutkan ke dashboard administrasi.</p>
            </div>

            <p class="login-greeting-small">Portal Masuk</p>
            <h2 class="login-title">Masuk ke Akun Anda</h2>
            <p class="login-subtitle">Gunakan email Mailcow Anda untuk melanjutkan ke dashboard administrasi.</p>

            {{-- Form --}}
            <form wire:submit.prevent="authenticate">
                <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px;">
                    {{ $this->form }}
                </div>

                <button type="submit" class="login-btn" wire:loading.attr="disabled"
                    wire:loading.class="login-btn-loading">
                    <span wire:loading.remove wire:target="authenticate">Masuk Sekarang &nbsp;→</span>
                    <span wire:loading wire:target="authenticate" style="display:flex;align-items:center;gap:8px;">
                        <svg style="animation:spin 1s linear infinite;width:18px;height:18px;"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path style="opacity:.75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Memverifikasi...
                    </span>
                </button>
            </form>

            <p class="login-help">
                Lupa password? <a href="#">Hubungi IT Administrator</a>
            </p>

            <div class="login-footer">
                &copy; {{ date('Y') }} &nbsp;&middot;&nbsp; IT Dept. SMK Bakti Nusantara 666
            </div>
        </div>
    </div>

</div>

@livewire('notifications')