{{-- resources/views/login.blade.php --}}
<div class="min-h-screen flex" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        .fi-simple-layout {
            background: transparent !important;
        }

        /* ===== LEFT PANEL DECORATIVE BLOBS ===== */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(5deg);
            }
        }

        @keyframes floatDelay {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(20px) rotate(-5deg);
            }
        }

        .blob-1 {
            animation: float 7s ease-in-out infinite;
        }

        .blob-2 {
            animation: floatDelay 9s ease-in-out infinite;
        }

        .blob-3 {
            animation: float 11s ease-in-out infinite;
        }

        /* ===== INPUT OVERRIDES – high contrast for light screens ===== */
        .fi-simple-layout .fi-input-wrp,
        .fi-simple-layout .fi-fo-field-wrp .fi-input-wrp {
            background-color: #f9fafb !important;
            border: 1.5px solid #d1d5db !important;
            border-radius: 0.75rem !important;
            box-shadow: none !important;
            transition: border-color .2s, box-shadow .2s !important;
        }

        .fi-simple-layout .fi-input-wrp:focus-within {
            background-color: #ffffff !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .12) !important;
        }

        .fi-simple-layout .fi-input {
            color: #111827 !important;
            font-size: .9375rem !important;
            font-weight: 500 !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }

        .fi-simple-layout .fi-fo-field-wrp-label label {
            color: #374151 !important;
            font-size: .8125rem !important;
            font-weight: 700 !important;
            letter-spacing: .02em !important;
            text-transform: uppercase !important;
        }

        /* Remove Filament's default card bg so our white panel shows */
        .fi-simple-layout .fi-simple-main,
        .fi-simple-layout .fi-simple-page {
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
        }

        /* Remove the default section gap */
        .fi-fo-field-wrp {
            margin-bottom: 0 !important;
        }
    </style>

    {{-- ================================================================== --}}
    {{-- LEFT PANEL – Brand / Visuals --}}
    {{-- ================================================================== --}}
    <div class="hidden lg:flex lg:w-[55%] relative flex-col overflow-hidden"
        style="background: linear-gradient(135deg, #0a1628 0%, #0f2044 50%, #1a1070 100%);">

        {{-- Decorative animated blobs --}}
        <div class="blob-1 absolute top-[8%] right-[12%] w-72 h-72 rounded-full opacity-20"
            style="background: radial-gradient(circle, #6366f1, transparent 70%);"></div>
        <div class="blob-2 absolute bottom-[15%] left-[8%] w-96 h-96 rounded-full opacity-15"
            style="background: radial-gradient(circle, #818cf8, transparent 70%);"></div>
        <div class="blob-3 absolute top-[40%] left-[35%] w-56 h-56 rounded-full opacity-10"
            style="background: radial-gradient(circle, #a5b4fc, transparent 70%);"></div>

        {{-- Subtle grid overlay --}}
        <div class="absolute inset-0 opacity-[0.03]"
            style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;">
        </div>

        {{-- Content --}}
        <div class="relative z-10 flex flex-col h-full p-14">
            {{-- Logo & Name --}}
            <div class="flex items-center gap-3 mb-auto">
                <div
                    class="w-11 h-11 rounded-xl flex items-center justify-center overflow-hidden bg-white/10 backdrop-blur border border-white/20">
                    <img src="/images/BD_logo.png" alt="Logo" class="w-9 h-9 object-contain"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <span style="display:none"
                        class="w-full h-full items-center justify-center text-white font-bold text-sm">BN</span>
                </div>
                <span class="text-white font-bold tracking-wide text-lg">Baknus<span
                        class="text-indigo-300">Attend</span></span>
            </div>

            {{-- Headline --}}
            <div class="mt-auto mb-16">
                <div
                    class="inline-flex items-center gap-2 bg-indigo-500/20 border border-indigo-400/30 text-indigo-200 text-xs font-semibold px-3 py-1.5 rounded-full mb-6 backdrop-blur-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                    Sistem Presensi Digital
                </div>

                <h1 class="text-5xl font-extrabold text-white leading-[1.15] tracking-tight mb-6">
                    Hadir Tepat<br />
                    <span class="text-transparent bg-clip-text"
                        style="background-image: linear-gradient(90deg, #a5b4fc, #818cf8);">Waktu, Setiap Hari.</span>
                </h1>

                <p class="text-slate-400 text-base leading-relaxed max-w-sm">
                    Sistem absensi terintegrasi Mailcow untuk seluruh civitas akademika SMK Bakti Nusantara 666. Akurat,
                    cepat, dan real-time.
                </p>

                {{-- Stats --}}
                <div class="flex items-center gap-10 mt-10">
                    <div>
                        <div class="text-3xl font-extrabold text-white">100%</div>
                        <div class="text-slate-400 text-xs mt-1 uppercase tracking-widest">Digital</div>
                    </div>
                    <div class="w-px h-10 bg-white/10"></div>
                    <div>
                        <div class="text-3xl font-extrabold text-white">3</div>
                        <div class="text-slate-400 text-xs mt-1 uppercase tracking-widest">Peran Pengguna</div>
                    </div>
                    <div class="w-px h-10 bg-white/10"></div>
                    <div>
                        <div class="text-3xl font-extrabold text-white">RFID</div>
                        <div class="text-slate-400 text-xs mt-1 uppercase tracking-widest">Terintegrasi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- RIGHT PANEL – Login Form --}}
    {{-- ================================================================== --}}
    <div class="w-full lg:w-[45%] flex items-center justify-center bg-white px-6 sm:px-12 lg:px-16 py-10 relative">

        {{-- Subtle top-right accent --}}
        <div class="absolute top-0 right-0 w-64 h-64 opacity-40 pointer-events-none"
            style="background: radial-gradient(circle at top right, #eef2ff, transparent 70%);"></div>

        <div class="w-full max-w-[400px] relative z-10">

            {{-- Mobile Logo --}}
            <div class="flex lg:hidden flex-col items-center mb-10">
                <img src="/images/BD_logo.png" alt="Logo" class="h-16 w-auto mb-3 drop-shadow"
                    onerror="this.src='https://tailwindui.com/plus/img/logos/mark.svg?color=indigo&shade=600'">
                <h1 class="text-2xl font-bold text-slate-900">BaknusAttend</h1>
                <p class="text-sm text-slate-500 mt-1">SMK Bakti Nusantara 666</p>
            </div>

            {{-- Desktop Greeting --}}
            <div class="hidden lg:block mb-10">
                <p class="text-xs font-bold uppercase tracking-widest text-indigo-500 mb-2">Selamat Datang</p>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-2">Masuk ke Akun Anda</h2>
                <p class="text-slate-500 text-sm leading-relaxed">Gunakan email Mailcow Anda untuk melanjutkan ke
                    dashboard.</p>
            </div>

            {{-- Filament Form --}}
            <form wire:submit.prevent="authenticate" class="space-y-5">
                <div class="space-y-4">
                    {{ $this->form }}
                </div>

                {{-- Login Button --}}
                <div class="pt-3">
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl text-sm font-bold text-white transition-all duration-200 active:scale-[.98] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        style="background: linear-gradient(135deg, #4f46e5, #6366f1); box-shadow: 0 4px 20px rgba(99,102,241,.35);">

                        <span wire:loading.remove>
                            Masuk Sekarang
                        </span>
                        <svg wire:loading.remove class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>

                        <span wire:loading class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Memverifikasi...
                        </span>
                    </button>
                </div>
            </form>

            {{-- Help Text --}}
            <p class="mt-6 text-center text-[0.8rem] text-slate-400">
                Lupa password?
                <a href="#" class="font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">Hubungi IT
                    Administrator</a>
            </p>

            {{-- Footer --}}
            <div class="mt-14 pt-5 border-t border-slate-100 text-center">
                <p class="text-[0.65rem] uppercase tracking-[.12em] font-bold text-slate-300">
                    &copy; {{ date('Y') }} &nbsp;·&nbsp; IT Departement SMK Bakti Nusantara 666
                </p>
            </div>
        </div>
    </div>

    @livewire('notifications')
</div>