<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="BaknusAttend – Sistem Presensi Digital SMK Bakti Nusantara 666">
    <title>BaknusAttend – Login</title>
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
            --indigo-50: #eef2ff;
            --indigo-100: #e0e7ff;
            --indigo-400: #818cf8;
            --indigo-500: #6366f1;
            --indigo-600: #4f46e5;
            --indigo-700: #4338ca;
            --indigo-900: #312e81;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
            --font: 'Plus Jakarta Sans', -apple-system, sans-serif;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-2xl: 24px;
            --shadow-indigo: 0 8px 32px rgba(99, 102, 241, 0.35);
            --shadow-card: 0 4px 24px rgba(0, 0, 0, 0.06), 0 1px 4px rgba(0, 0, 0, 0.04);
        }

        html,
        body {
            height: 100%;
            font-family: var(--font);
            -webkit-font-smoothing: antialiased;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: var(--slate-50);
        }

        /* =========================================================
           LEFT PANEL — cinematic gradient + image overlay
           ========================================================= */
        .lp {
            width: 56%;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 52px 56px;
            background:
                linear-gradient(160deg,
                    rgba(10, 14, 54, 0.93) 0%,
                    rgba(15, 22, 72, 0.90) 55%,
                    rgba(37, 14, 118, 0.88) 100%),
                url("{{ asset('images/BA.png') }}") center / cover no-repeat;
        }

        /* Fine grid overlay */
        .lp-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        /* Animated ambient blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
        }

        .blob-1 {
            width: 420px;
            height: 420px;
            top: -80px;
            right: -100px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.38), transparent 70%);
            animation: floatA 9s ease-in-out infinite;
        }

        .blob-2 {
            width: 340px;
            height: 340px;
            bottom: -50px;
            left: -70px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.28), transparent 70%);
            animation: floatB 11s ease-in-out infinite;
        }

        .blob-3 {
            width: 200px;
            height: 200px;
            top: 42%;
            left: 30%;
            background: radial-gradient(circle, rgba(165, 180, 252, 0.12), transparent 70%);
            animation: floatA 14s ease-in-out infinite;
        }

        @keyframes floatA {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-28px) scale(1.04); }
        }

        @keyframes floatB {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(24px) scale(1.03); }
        }

        /* Brand */
        .lp-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            z-index: 10;
        }

        .lp-brand-logo {
            height: 52px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 0 12px rgba(165, 180, 252, 0.4));
        }

        .lp-brand-name {
            font-size: 1.2rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .lp-brand-name span {
            color: #a5b4fc;
        }

        /* Scroller / spacer */
        .lp-spacer { flex: 1; }

        /* Hero content */
        .lp-hero {
            position: relative;
            z-index: 10;
            padding-bottom: 4px;
        }

        .lp-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(99, 102, 241, 0.18);
            border: 1px solid rgba(165, 180, 252, 0.32);
            backdrop-filter: blur(8px);
            color: #c7d2fe;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 7px 16px;
            border-radius: 999px;
            margin-bottom: 28px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .badge-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 6px #4ade80;
            animation: pulse 1.8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .55; transform: scale(1.35); }
        }

        .lp-headline {
            font-size: clamp(2rem, 3.2vw, 3.2rem);
            font-weight: 900;
            color: #fff;
            line-height: 1.14;
            letter-spacing: -0.03em;
            margin-bottom: 22px;
        }

        .lp-headline span {
            background: linear-gradient(100deg, #c7d2fe, #818cf8 60%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .lp-desc {
            color: #94a3b8;
            font-size: 0.9375rem;
            line-height: 1.75;
            max-width: 380px;
            margin-bottom: 40px;
        }

        /* Stats row */
        .lp-stats {
            display: flex;
            align-items: center;
        }

        .stat-item { display: flex; flex-direction: column; }

        .stat-val {
            font-size: 1.875rem;
            font-weight: 900;
            color: #fff;
            line-height: 1;
            letter-spacing: -0.03em;
        }

        .stat-lbl {
            font-size: 0.62rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 5px;
            font-weight: 700;
        }

        .stat-div {
            width: 1px;
            height: 36px;
            background: rgba(255, 255, 255, 0.1);
            margin: 0 28px;
        }

        /* =========================================================
           RIGHT PANEL — clean white card
           ========================================================= */
        .rp {
            width: 44%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 52px;
            position: relative;
        }

        .rp::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle at top right, var(--indigo-50), transparent 65%);
            pointer-events: none;
        }

        .rp::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle at bottom left, #f0fdf4, transparent 65%);
            pointer-events: none;
        }

        .form-card {
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 10;
        }

        /* Icon badge */
        .form-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--indigo-600), var(--indigo-400));
            box-shadow: var(--shadow-indigo);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 28px;
        }

        .form-icon svg {
            width: 28px;
            height: 28px;
            color: #fff;
        }

        .form-eyebrow {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--indigo-500);
            margin-bottom: 8px;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 900;
            color: var(--slate-900);
            letter-spacing: -0.03em;
            margin-bottom: 8px;
            line-height: 1.1;
        }

        .form-subtitle {
            font-size: 0.875rem;
            color: var(--slate-500);
            line-height: 1.65;
            margin-bottom: 36px;
        }

        /* Alert */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: var(--radius-lg);
            padding: 14px 16px;
            margin-bottom: 24px;
            color: #dc2626;
            font-size: 0.85rem;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form fields */
        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--slate-700);
            letter-spacing: 0.02em;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--slate-400);
            pointer-events: none;
            transition: color 0.2s;
        }

        .field input {
            width: 100%;
            padding: 13px 14px 13px 42px;
            border-radius: var(--radius-lg);
            border: 1.5px solid var(--slate-200);
            background: var(--slate-50);
            font-size: 0.9375rem;
            font-family: var(--font);
            color: var(--slate-900);
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            outline: none;
            font-weight: 500;
        }

        .field input::placeholder {
            color: #b0bacb;
            font-weight: 400;
        }

        .field input:focus {
            border-color: var(--indigo-500);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        }

        .field input:focus ~ .input-icon,
        .input-wrap:focus-within .input-icon {
            color: var(--indigo-500);
        }

        /* Remember me */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            color: var(--slate-500);
            cursor: pointer;
            user-select: none;
        }

        .remember-label input[type=checkbox] {
            width: 17px;
            height: 17px;
            accent-color: var(--indigo-500);
            border-radius: 4px;
            cursor: pointer;
        }

        /* Submit button */
        .btn-submit {
            position: relative;
            width: 100%;
            padding: 15px 24px;
            border-radius: var(--radius-lg);
            border: none;
            cursor: pointer;
            font-family: var(--font);
            font-size: 0.9375rem;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(135deg, var(--indigo-600), var(--indigo-500));
            box-shadow: var(--shadow-indigo);
            transition: transform 0.15s, box-shadow 0.15s;
            letter-spacing: 0.02em;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent);
            pointer-events: none;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 40px rgba(99, 102, 241, 0.45);
        }

        .btn-submit:active {
            transform: translateY(0) scale(0.98);
            box-shadow: var(--shadow-indigo);
        }

        /* Footer */
        .form-footer {
            margin-top: 36px;
            padding-top: 20px;
            border-top: 1px solid var(--slate-100);
            text-align: center;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #c1cad6;
        }

        /* =========================================================
           RESPONSIVE
           ========================================================= */
        @media (max-width: 900px) {
            .lp { display: none !important; }
            .rp {
                width: 100% !important;
                padding: 40px 24px !important;
            }
        }

        @media (max-width: 480px) {
            .form-title { font-size: 1.6rem !important; }
        }
    </style>
</head>

<body>

    {{-- ======= LEFT PANEL ======= --}}
    <div class="lp">
        <div class="lp-grid"></div>
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>

        {{-- Brand --}}
        <div class="lp-brand">
            <img src="{{ asset('images/logo_BG.png') }}" alt="Logo BaknusAttend" class="lp-brand-logo">
            <span class="lp-brand-name">Baknus<span>Attend</span></span>
        </div>

        <div class="lp-spacer"></div>

        {{-- Hero --}}
        <div class="lp-hero">
            <div class="lp-badge">
                <div class="badge-dot"></div>
                Sistem Presensi Aktif
            </div>

            <h1 class="lp-headline">
                Hadir Tepat Waktu,<br>
                <span>Setiap Hari.</span>
            </h1>

            <p class="lp-desc">
                Sistem absensi digital terintegrasi Mailcow untuk seluruh civitas akademika
                SMK Bakti Nusantara 666. Akurat, cepat, dan real-time.
            </p>

            <div class="lp-stats">
                <div class="stat-item">
                    <span class="stat-val">100%</span>
                    <span class="stat-lbl">Digital</span>
                </div>
                <div class="stat-div"></div>
                <div class="stat-item">
                    <span class="stat-val">3</span>
                    <span class="stat-lbl">Peran User</span>
                </div>
                <div class="stat-div"></div>
                <div class="stat-item">
                    <span class="stat-val">RFID</span>
                    <span class="stat-lbl">Terintegrasi</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ======= RIGHT PANEL ======= --}}
    <div class="rp">
        <div class="form-card">

            {{-- Icon --}}
            <div class="form-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 21V9l9-6 9 6v12"/><path d="M9 21V12h6v9"/>
                </svg>
            </div>

            <p class="form-eyebrow">Portal Masuk</p>
            <h1 class="form-title">Masuk ke Akun Anda</h1>
            <p class="form-subtitle">Gunakan email Mailcow sekolah Anda untuk melanjutkan ke dashboard.</p>

            {{-- Error --}}
            @if($errors->any())
                <div class="alert" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;width:18px;height:18px;margin-top:1px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login.post') }}" id="loginForm">
                @csrf

                <div class="field">
                    <label for="email">Username atau Email</label>
                    <div class="input-wrap">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <input
                            type="text"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="NIS / email@smkbaknus.sch.id"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="field">
                    <label for="password">Kata Sandi</label>
                    <div class="input-wrap">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Password Mailcow Anda"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>

                <div class="remember-row">
                    <label class="remember-label" for="remember">
                        <input type="checkbox" name="remember" id="remember">
                        Ingat saya
                    </label>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    Masuk Sekarang &nbsp;→
                </button>
            </form>

            <div class="form-footer">
                &copy; {{ date('Y') }} &nbsp;·&nbsp; IT Dept. SMK Bakti Nusantara 666
            </div>
        </div>
    </div>

</body>
</html>