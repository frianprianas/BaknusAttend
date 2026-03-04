<div class="min-h-screen flex font-sans bg-white dark:bg-slate-900">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .fi-simple-layout {
            background: transparent !important;
        }

        /* Modern Input Styling - Sangat Jelas di Mode Terang */
        .fi-input-wrp {
            border-radius: 0.75rem !important;
            padding: 0.25rem !important;
            background-color: #f8fafc !important;
            /* Latar abu sangat muda */
            border: 1px solid #cbd5e1 !important;
            /* Border jelas */
            box-shadow: none !important;
            transition: all 0.2s ease-in-out !important;
        }

        .dark .fi-input-wrp {
            background-color: #0f172a !important;
            border-color: #334155 !important;
        }

        .fi-input-wrp:focus-within {
            background-color: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important;
        }

        .dark .fi-input-wrp:focus-within {
            background-color: #1e293b !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2) !important;
        }

        .fi-input {
            color: #0f172a !important;
            /* Teks sangat gelap hampir hitam */
            font-size: 1rem !important;
            font-weight: 500 !important;
        }

        .dark .fi-input {
            color: #f8fafc !important;
        }

        .fi-fo-field-wrp-label label {
            color: #334155 !important;
            /* Slate-700 untuk kontras tinggi di mode terang */
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            margin-bottom: 0.5rem !important;
        }

        .dark .fi-fo-field-wrp-label label {
            color: #cbd5e1 !important;
        }

        /* Left Side Graphic */
        .mesh-bg {
            background-color: #0f172a;
            background-image:
                radial-gradient(at 0% 0%, hsla(253, 16%, 7%, 1) 0, transparent 50%),
                radial-gradient(at 50% 0%, hsla(225, 39%, 30%, 1) 0, transparent 50%),
                radial-gradient(at 100% 0%, hsla(339, 49%, 30%, 1) 0, transparent 50%);
        }
    </style>

    <!-- LEFT PANEL: Branding & Visuals -->
    <div class="hidden lg:flex lg:w-1/2 mesh-bg relative flex-col justify-between p-12 text-white overflow-hidden">
        <!-- Abstract Shapes -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute -top-[20%] -left-[10%] w-[70%] h-[70%] rounded-full bg-blue-600/20 blur-[100px]"></div>
            <div class="absolute bottom-[10%] -right-[20%] w-[60%] h-[60%] rounded-full bg-purple-600/20 blur-[100px]">
            </div>
        </div>

        <div class="relative z-10 w-full animate-fade-in">
            <div class="flex items-center gap-3 mb-10">
                <img src="/images/BD_logo.png" alt="Logo" class="h-12 w-auto drop-shadow-lg"
                    onerror="this.src='https://tailwindui.com/plus/img/logos/mark.svg?color=white'">
                <span class="text-2xl font-bold tracking-wide">Baknus<span class="text-blue-400">Attend</span></span>
            </div>
        </div>

        <div class="relative z-10 max-w-lg mb-10">
            <h1 class="text-5xl font-extrabold mb-6 leading-tight tracking-tight">Sistem Presensi<br />Modern & Terpadu.
            </h1>
            <p class="text-lg text-slate-300 font-medium leading-relaxed mb-8">
                Integrasi langsung dengan Mailcow untuk akses cerdas, akurat, dan real-time bagi Guru, Staff, dan Siswa
                SMK Bakti Nusantara 666.
            </p>
            <div class="flex items-center gap-4">
                <div class="flex -space-x-3">
                    <div
                        class="w-10 h-10 rounded-full bg-slate-800 border-2 border-slate-900 flex items-center justify-center text-xs font-bold text-slate-300 z-30">
                        G</div>
                    <div
                        class="w-10 h-10 rounded-full bg-slate-800 border-2 border-slate-900 flex items-center justify-center text-xs font-bold text-slate-300 z-20">
                        T</div>
                    <div
                        class="w-10 h-10 rounded-full bg-slate-800 border-2 border-slate-900 flex items-center justify-center text-xs font-bold text-slate-300 z-10">
                        S</div>
                </div>
                <div class="text-sm text-slate-400 font-medium">Diakses oleh semua civitas akademika.</div>
            </div>
        </div>

        <div class="relative z-10 text-sm text-slate-500 font-medium font-mono">
            &copy; {{ date('Y') }} IT Departement SMK BN 666
        </div>
    </div>

    <!-- RIGHT PANEL: Login Form -->
    <div
        class="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 lg:p-24 relative bg-white dark:bg-slate-900 transition-colors">

        <div class="w-full max-w-md">
            <!-- Mobile Header -->
            <div class="lg:hidden mb-10 flex flex-col items-center">
                <img src="/images/BD_logo.png" alt="Logo" class="h-16 w-auto mb-4"
                    onerror="this.src='https://tailwindui.com/plus/img/logos/mark.svg?color=blue&shade=600'">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">BaknusAttend</h1>
                <p class="text-sm text-slate-500 font-medium">SMK Bakti Nusantara 666</p>
            </div>

            <!-- Desktop Header -->
            <div class="hidden lg:block mb-8">
                <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2 tracking-tight">Selamat Datang 👋
                </h2>
                <p class="text-slate-500 dark:text-slate-400 text-[0.95rem]">Silakan masuk menggunakan akun Mailcow
                    Anda.</p>
            </div>

            <!-- Formulir Login -->
            <form wire:submit.prevent="authenticate" class="space-y-6">
                <!-- Filament Livewire Form -->
                <div class="space-y-4">
                    {{ $this->form }}
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-md text-[0.95rem] font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-[0.98] outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">

                        <span wire:loading.remove>Masuk Sekarang</span>

                        <!-- Panah (Sembunyi saat loading) -->
                        <svg wire:loading.remove class="w-5 h-5 ml-2 transition-transform" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>

                        <!-- Animasi Loading -->
                        <span wire:loading class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Otentikasi...
                        </span>
                    </button>
                </div>
            </form>

            <!-- Bantuan / Footer -->
            <div class="mt-8 text-center sm:text-left text-[0.85rem] text-slate-500 dark:text-slate-400">
                Kendala akses? <a href="#"
                    class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400">Hubungi Administrator
                    IT</a>.
            </div>

            <div
                class="lg:hidden mt-12 pt-6 border-t border-slate-100 dark:border-slate-800 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} IT SMK BN666
            </div>
        </div>
    </div>

    @livewire('notifications')
</div>