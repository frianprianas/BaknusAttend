<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BaknusAttend – Login</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
        }

        /* LEFT PANEL */
        .left {
            width: 55%;
            background: linear-gradient(150deg, #0d1b3e 0%, #0f2044 60%, #1a105e 100%);
            display: flex;
            flex-direction: column;
            padding: 52px;
            position: relative;
            overflow: hidden;
        }

        @media(max-width:900px) {
            .left {
                display: none;
            }

            .right {
                width: 100%;
            }
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }

        .b1 {
            width: 360px;
            height: 360px;
            top: -60px;
            right: -80px;
            background: radial-gradient(circle, rgba(99, 102, 241, .35), transparent 70%);
            animation: fa 8s ease-in-out infinite;
        }

        .b2 {
            width: 300px;
            height: 300px;
            bottom: -40px;
            left: -60px;
            background: radial-gradient(circle, rgba(129, 140, 248, .25), transparent 70%);
            animation: fb 10s ease-in-out infinite;
        }

        @keyframes fa {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-20px)
            }
        }

        @keyframes fb {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(20px)
            }
        }

        .grid-bg {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, .04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .04) 1px, transparent 1px);
            background-size: 44px 44px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 10;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .1);
            border: 1px solid rgba(255, 255, 255, .2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-name {
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .brand-name span {
            color: #a5b4fc;
        }

        .hero {
            margin-top: auto;
            position: relative;
            z-index: 10;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(99, 102, 241, .18);
            border: 1px solid rgba(165, 180, 252, .3);
            color: #c7d2fe;
            font-size: .75rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 24px;
        }

        .dot {
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
                transform: scale(1)
            }

            50% {
                opacity: .5;
                transform: scale(1.3)
            }
        }

        h1 {
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 16px;
        }

        h1 span {
            background: linear-gradient(90deg, #a5b4fc, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .desc {
            color: #94a3b8;
            font-size: .9rem;
            line-height: 1.7;
            max-width: 360px;
            margin-bottom: 32px;
        }

        .stats {
            display: flex;
            align-items: center;
            gap: 0;
        }

        .stat {
            display: flex;
            flex-direction: column;
        }

        .sv {
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
        }

        .sl {
            font-size: .6rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .1em;
            margin-top: 3px;
        }

        .sdiv {
            width: 1px;
            height: 32px;
            background: rgba(255, 255, 255, .1);
            margin: 0 24px;
        }

        /* RIGHT PANEL */
        .right {
            width: 45%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 52px;
            position: relative;
        }

        .right::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 260px;
            height: 260px;
            pointer-events: none;
            background: radial-gradient(circle at top right, #eef2ff, transparent 65%);
        }

        .form-box {
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 10;
        }

        .subtitle {
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: #6366f1;
            margin-bottom: 8px;
        }

        .title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -.02em;
            margin-bottom: 8px;
        }

        .tagline {
            font-size: .875rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        /* Error */
        .alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            color: #dc2626;
            font-size: .875rem;
        }

        /* Fields */
        .field {
            margin-bottom: 18px;
        }

        .field label {
            display: block;
            font-size: .75rem;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }

        .field input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1.5px solid #d1d5db;
            background: #f9fafb;
            font-size: .9375rem;
            color: #111827;
            font-family: inherit;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }

        .field input:focus {
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .12);
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            font-size: .875rem;
            color: #6b7280;
        }

        .remember input[type=checkbox] {
            width: 16px;
            height: 16px;
            accent-color: #6366f1;
        }

        /* Button */
        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: .9375rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            box-shadow: 0 6px 24px rgba(99, 102, 241, .4);
            transition: transform .15s, box-shadow .15s;
            font-family: inherit;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, .45);
        }

        .btn:active {
            transform: scale(.98);
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            text-align: center;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: #cbd5e1;
        }
    </style>
</head>

<body>

    {{-- LEFT PANEL --}}
    <div class="left">
        <div class="grid-bg"></div>
        <div class="blob b1"></div>
        <div class="blob b2"></div>

        <div class="brand">
            <div class="brand-icon">
                <svg width="26" height="26" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M3 21V9l9-6 9 6v12" />
                    <path d="M9 21V12h6v9" />
                    <path d="M12 3v3" />
                </svg>
            </div>
            <span class="brand-name">Baknus<span>Attend</span></span>
        </div>

        <div class="hero">
            <div class="badge">
                <div class="dot"></div>Sistem Presensi Aktif
            </div>
            <h1>Hadir Tepat<br>Waktu, <span>Setiap Hari.</span></h1>
            <p class="desc">Sistem absensi digital terintegrasi Mailcow untuk seluruh civitas akademika SMK Bakti
                Nusantara 666. Akurat, cepat, dan real-time.</p>
            <div class="stats">
                <div class="stat"><span class="sv">100%</span><span class="sl">Digital</span></div>
                <div class="sdiv"></div>
                <div class="stat"><span class="sv">3</span><span class="sl">Peran User</span></div>
                <div class="sdiv"></div>
                <div class="stat"><span class="sv">RFID</span><span class="sl">Terintegrasi</span></div>
            </div>
        </div>
    </div>

    {{-- RIGHT PANEL --}}
    <div class="right">
        <div class="form-box">
            <p class="subtitle">Portal Masuk</p>
            <h2 class="title">Masuk ke Akun Anda</h2>
            <p class="tagline">Gunakan email Mailcow sekolah Anda untuk melanjutkan.</p>

            @if($errors->any())
                <div class="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="field">
                    <label for="email">Alamat Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        placeholder="nama@smk.baktinusantara666.sch.id" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Kata Sandi</label>
                    <input type="password" id="password" name="password" placeholder="Password Mailcow Anda" required>
                </div>
                <div class="remember">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Ingat saya</label>
                </div>
                <button type="submit" class="btn">Masuk Sekarang →</button>
            </form>

            <div class="footer">
                &copy; {{ date('Y') }} &nbsp;·&nbsp; IT Dept. SMK Bakti Nusantara 666
            </div>
        </div>
    </div>

</body>

</html>