<x-filament-panels::page wire:poll.10s>
    @php
        $availableClasses = $this->availableClasses;
        $viewData = $this->viewData;
        $currentClass = $viewData['currentClass'];
        $studentGrid = $viewData['studentGrid'];
        $stats = $viewData['stats'];
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

        .cinema-wrapper, .cinema-wrapper * {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }

        /* Cinema Screen Board Header */
        .cinema-screen-container {
            position: relative;
            width: 100%;
            margin-bottom: 28px;
            text-align: center;
        }

        .cinema-screen {
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%);
            border-top: 4px solid #6366f1;
            border-bottom: 2px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px 12px 60px 60px;
            padding: 16px 24px;
            box-shadow: 0 10px 30px -10px rgba(99, 102, 241, 0.25);
            backdrop-filter: blur(8px);
        }

        .dark .cinema-screen {
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.25) 0%, rgba(15, 23, 42, 0.8) 100%);
            border-top-color: #818cf8;
            box-shadow: 0 12px 35px -10px rgba(99, 102, 241, 0.4);
        }

        .screen-label {
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: #4f46e5;
        }

        .dark .screen-label {
            color: #a5b4fc;
        }

        /* Bioskop Seat Card Grid */
        .cinema-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 16px;
        }

        @media (min-width: 640px) {
            .cinema-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 18px;
            }
        }

        @media (min-width: 1024px) {
            .cinema-grid {
                grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
                gap: 20px;
            }
        }

        /* Seat Pod Card Styles */
        .seat-pod {
            position: relative;
            border-radius: 20px;
            padding: 14px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            user-select: none;
            overflow: hidden;
            border: 1.5px solid transparent;
        }

        .seat-pod:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 16px 32px -8px rgba(0, 0, 0, 0.25);
            z-index: 10;
        }

        /* Status 1: HADIR (Hijau Bioskop Glow) */
        .seat-hadir {
            background: linear-gradient(145deg, #f0fdf4 0%, #dcfce7 100%);
            border-color: #86efac;
            color: #14532d;
            box-shadow: 0 4px 15px -3px rgba(34, 197, 94, 0.3);
        }
        .dark .seat-hadir {
            background: linear-gradient(145deg, rgba(6, 78, 59, 0.85) 0%, rgba(4, 120, 87, 0.9) 100%);
            border-color: #10b981;
            color: #ecfdf5;
            box-shadow: 0 6px 20px -4px rgba(16, 185, 129, 0.4);
        }

        /* Status 2: TERLAMBAT (Kuning / Amber) */
        .seat-terlambat {
            background: linear-gradient(145deg, #fffbe6 0%, #fef3c7 100%);
            border-color: #fde047;
            color: #713f12;
            box-shadow: 0 4px 15px -3px rgba(234, 179, 8, 0.3);
        }
        .dark .seat-terlambat {
            background: linear-gradient(145deg, rgba(120, 53, 15, 0.85) 0%, rgba(180, 83, 9, 0.9) 100%);
            border-color: #f59e0b;
            color: #fef3c7;
            box-shadow: 0 6px 20px -4px rgba(245, 158, 11, 0.4);
        }

        /* Status 3: IZIN / SAKIT (Biru / Indigo) */
        .seat-izin {
            background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%);
            border-color: #93c5fd;
            color: #1e3a8a;
            box-shadow: 0 4px 15px -3px rgba(59, 130, 246, 0.3);
        }
        .dark .seat-izin {
            background: linear-gradient(145deg, rgba(30, 58, 138, 0.85) 0%, rgba(29, 78, 216, 0.9) 100%);
            border-color: #3b82f6;
            color: #eff6ff;
            box-shadow: 0 6px 20px -4px rgba(59, 130, 246, 0.4);
        }

        /* Status 4: BELUM TAP (Abu-abu / Neutral) */
        .seat-belum {
            background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
            border-color: #e2e8f0;
            color: #64748b;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .dark .seat-belum {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.85) 100%);
            border-color: #334155;
            color: #94a3b8;
        }

        /* LED Glow Indicator Pill */
        .led-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .led-hadir {
            background-color: #22c55e;
            box-shadow: 0 0 10px #22c55e, 0 0 4px #22c55e;
            animation: pulse-green 2s infinite;
        }

        .led-terlambat {
            background-color: #eab308;
            box-shadow: 0 0 10px #eab308;
            animation: pulse-yellow 2s infinite;
        }

        .led-izin {
            background-color: #3b82f6;
            box-shadow: 0 0 10px #3b82f6;
        }

        .led-belum {
            background-color: #94a3b8;
            opacity: 0.5;
        }

        @keyframes pulse-green {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.25); }
        }

        @keyframes pulse-yellow {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.25); }
        }
    </style>

    <div class="cinema-wrapper space-y-6">

        {{-- Class Selector Header Bar --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xl border border-indigo-500/20">
                    🎬
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>{{ $currentClass ? $currentClass->kelas : 'Pilih Kelas' }}</span>
                        @if($currentClass)
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                Wali: {{ $currentClass->nipy ?? 'Belum Ditentukan' }}
                            </span>
                        @endif
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-0.5">
                        <span>📅 {{ now()->translatedFormat('l, j F Y') }}</span>
                        <span>·</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">⚡ Update Otomatis (Live 10s)</span>
                    </p>
                </div>
            </div>

            {{-- Filter Select Dropdown --}}
            <div class="flex items-center gap-3">
                @if($availableClasses->count() > 1)
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400">Pilih Kelas:</label>
                        <select wire:change="selectClass($event.target.value)" class="text-xs font-bold rounded-xl border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 py-2 px-3">
                            @foreach($availableClasses as $cls)
                                <option value="{{ $cls->id }}" {{ $cls->id == $selectedClassId ? 'selected' : '' }}>
                                    {{ $cls->kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <button wire:click="$refresh" class="text-xs font-bold px-3 py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-100 rounded-xl border border-indigo-200 dark:border-indigo-800 transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh
                </button>
            </div>
        </div>

        {{-- Live Statistics Summary Banner --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
            {{-- Total Siswa --}}
            <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col justify-between">
                <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Siswa</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-black text-gray-900 dark:text-white">{{ $stats['total'] }}</span>
                    <span class="text-xs font-medium text-gray-400">Orang</span>
                </div>
            </div>

            {{-- Hadir --}}
            <div class="p-4 rounded-2xl bg-emerald-500/10 dark:bg-emerald-950/40 border border-emerald-500/30 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-extrabold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">🟢 Hadir</span>
                    <span class="led-indicator led-hadir"></span>
                </div>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ $stats['hadir'] }}</span>
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Siswa</span>
                </div>
            </div>

            {{-- Terlambat --}}
            <div class="p-4 rounded-2xl bg-amber-500/10 dark:bg-amber-950/40 border border-amber-500/30 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-extrabold text-amber-700 dark:text-amber-400 uppercase tracking-wider">🟡 Terlambat</span>
                    <span class="led-indicator led-terlambat"></span>
                </div>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-black text-amber-700 dark:text-amber-300">{{ $stats['terlambat'] }}</span>
                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400">Siswa</span>
                </div>
            </div>

            {{-- Izin / Sakit --}}
            <div class="p-4 rounded-2xl bg-blue-500/10 dark:bg-blue-950/40 border border-blue-500/30 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-extrabold text-blue-700 dark:text-blue-400 uppercase tracking-wider">🔵 Izin / Sakit</span>
                    <span class="led-indicator led-izin"></span>
                </div>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-black text-blue-700 dark:text-blue-300">{{ $stats['izin'] }}</span>
                    <span class="text-xs font-bold text-blue-600 dark:text-blue-400">Siswa</span>
                </div>
            </div>

            {{-- Belum Tap --}}
            <div class="p-4 rounded-2xl bg-slate-500/10 dark:bg-slate-800/40 border border-slate-300 dark:border-slate-700 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">⚪ Belum Tap</span>
                    <span class="led-indicator led-belum"></span>
                </div>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-black text-slate-700 dark:text-slate-300">{{ $stats['belum'] }}</span>
                    <span class="text-xs font-medium text-slate-500">Siswa</span>
                </div>
            </div>

            {{-- Persentase --}}
            <div class="p-4 rounded-2xl bg-indigo-600/10 dark:bg-indigo-950/50 border border-indigo-500/30 shadow-sm flex flex-col justify-between">
                <span class="text-[11px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">📊 Presensi</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-black text-indigo-700 dark:text-indigo-300">{{ $stats['persen'] }}%</span>
                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">Hadir</span>
                </div>
            </div>
        </div>

        {{-- Cinema Layout Projection Screen --}}
        <div class="cinema-screen-container">
            <div class="cinema-screen">
                <p class="screen-label">🍿 PROYEKSI DENAH KURSI PRESENSI KELAS (BIOSKOP VIEW) 🍿</p>
                <div class="w-1/3 h-1 mx-auto mt-2 rounded-full bg-gradient-to-r from-transparent via-indigo-500 to-transparent"></div>
            </div>
        </div>

        {{-- Cinema Seats Grid (Kotak-Kotak Bioskop) --}}
        @if($studentGrid->isEmpty())
            <div class="p-12 text-center bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800">
                <span class="text-4xl">📚</span>
                <h3 class="mt-3 text-base font-bold text-gray-800 dark:text-gray-200">Belum Ada Data Siswa</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Siswa belum terdaftar pada kelas yang dipilih ini.</p>
            </div>
        @else
            <div class="cinema-grid">
                @foreach($studentGrid as $student)
                    @php
                        $statusClass = match($student['status_code']) {
                            'HADIR' => 'seat-hadir',
                            'TERLAMBAT' => 'seat-terlambat',
                            'IZIN' => 'seat-izin',
                            default => 'seat-belum',
                        };

                        $ledClass = match($student['status_code']) {
                            'HADIR' => 'led-hadir',
                            'TERLAMBAT' => 'led-terlambat',
                            'IZIN' => 'led-izin',
                            default => 'led-belum',
                        };
                    @endphp

                    <div wire:click="openStudentModal('{{ $student['nis'] }}')" class="seat-pod {{ $statusClass }}">
                        
                        {{-- Top Header Row: Seat Number & LED Indicator --}}
                        <div class="w-full flex items-center justify-between mb-2">
                            <span class="text-[10px] font-black tracking-wider uppercase px-2 py-0.5 rounded-full bg-black/10 dark:bg-white/10">
                                {{ $student['seat_number'] }}
                            </span>
                            <span class="led-indicator {{ $ledClass }}"></span>
                        </div>

                        {{-- Student Avatar / Photo --}}
                        <div class="relative my-1">
                            @if($student['photo_url'])
                                <img src="{{ $student['photo_url'] }}" alt="{{ $student['name'] }}" class="w-12 h-12 rounded-full object-cover border-2 border-white dark:border-gray-800 shadow-sm">
                            @else
                                <div class="w-12 h-12 rounded-full bg-white/40 dark:bg-black/40 border-2 border-white/60 dark:border-gray-700/60 flex items-center justify-center font-black text-sm">
                                    {{ strtoupper(substr($student['name'], 0, 2)) }}
                                </div>
                            @endif
                        </div>

                        {{-- Student Name & NIS --}}
                        <div class="w-full mt-1.5">
                            <h4 class="text-xs font-bold truncate leading-tight" title="{{ $student['name'] }}">
                                {{ $student['name'] }}
                            </h4>
                            <p class="text-[10px] opacity-75 mt-0.5 truncate font-mono">
                                {{ $student['nis'] }}
                            </p>
                        </div>

                        {{-- Bottom Status Badge --}}
                        <div class="w-full mt-3">
                            <span class="inline-block w-full py-1 px-1.5 text-[9.5px] font-extrabold rounded-lg tracking-wide uppercase truncate shadow-xs border border-black/5 dark:border-white/10 bg-white/50 dark:bg-black/30">
                                @if($student['status_code'] === 'HADIR')
                                    ✅ {{ $student['waktu_masuk'] }}
                                @elseif($student['status_code'] === 'TERLAMBAT')
                                    ⚠️ {{ $student['waktu_masuk'] }}
                                @elseif($student['status_code'] === 'IZIN')
                                    📋 IZIN
                                @else
                                    ⚪ BELUM
                                @endif
                            </span>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

    </div>

    {{-- Student Detail Modal (Pop-up Popover) --}}
    @if($showModal && $modalStudent)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in" wire:click.self="closeModal">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 max-w-md w-full shadow-2xl relative space-y-5">
                
                {{-- Close Button --}}
                <button wire:click="closeModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                    ✕
                </button>

                {{-- Modal Header --}}
                <div class="flex items-center gap-4">
                    @if($modalStudent['photo'])
                        <img src="{{ $modalStudent['photo'] }}" alt="{{ $modalStudent['name'] }}" class="w-16 h-16 rounded-2xl object-cover border-2 border-indigo-500/30 shadow-md">
                    @else
                        <div class="w-16 h-16 rounded-2xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 flex items-center justify-center font-black text-2xl">
                            {{ strtoupper(substr($modalStudent['name'], 0, 2)) }}
                        </div>
                    @endif
                    <div>
                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                            {{ $modalStudent['kelas'] }}
                        </span>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white mt-1">{{ $modalStudent['name'] }}</h3>
                        <p class="text-xs font-mono text-gray-500 dark:text-gray-400">NIS: {{ $modalStudent['nis'] }} · RFID: {{ $modalStudent['rfid'] }}</p>
                    </div>
                </div>

                {{-- Today's Attendance Details --}}
                <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/60 space-y-3">
                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status Presensi Hari Ini</h4>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                            <span class="text-[10px] font-semibold text-gray-400 block">Waktu Masuk</span>
                            <span class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400">
                                {{ $modalStudent['waktu_masuk'] ?? '-' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                            <span class="text-[10px] font-semibold text-gray-400 block">Waktu Pulang</span>
                            <span class="text-sm font-extrabold text-orange-600 dark:text-orange-400">
                                {{ $modalStudent['waktu_pulang'] ?? '-' }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center text-xs">
                        <span class="font-medium text-gray-500 dark:text-gray-400">Keterangan:</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $modalStudent['keterangan'] }}</span>
                    </div>
                </div>

                {{-- Monthly Statistics --}}
                <div class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/20 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block">Rekap Kehadiran Bulan Ini</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Hadir {{ $modalStudent['hadir_bulan'] }} dari {{ $modalStudent['aktif_bulan'] }} hari aktif</span>
                    </div>
                    <span class="text-xl font-black text-indigo-600 dark:text-indigo-400">
                        {{ $modalStudent['persen_bulan'] }}%
                    </span>
                </div>

                {{-- Footer Button --}}
                <button wire:click="closeModal" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                    Tutup Detail
                </button>

            </div>
        </div>
    @endif
</x-filament-panels::page>
