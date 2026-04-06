<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

            .cal-wrap * { font-family: 'Plus Jakarta Sans', sans-serif !important; }

            /* ---- Header ---- */
            .cal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 28px;
            }
            .cal-title {
                font-size: 1.35rem;
                font-weight: 900;
                color: #0f172a;
                letter-spacing: -0.02em;
                margin: 0 0 4px;
            }
            .dark .cal-title { color: #f1f5f9; }
            .cal-month-label {
                font-size: .72rem;
                font-weight: 700;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: .1em;
            }

            /* Nav buttons */
            .cal-nav-btn {
                width: 38px; height: 38px;
                border-radius: 10px;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                display: flex; align-items: center; justify-content: center;
                cursor: pointer; transition: all .15s; color: #475569;
            }
            .cal-nav-btn:hover { background: #e2e8f0; color: #1e293b; transform: scale(1.05); }
            .dark .cal-nav-btn { background: rgba(30,41,59,.6); border-color: #334155; color: #94a3b8; }
            .dark .cal-nav-btn:hover { background: #334155; color: #f1f5f9; }
            .cal-nav-group { display: flex; align-items: center; gap: 8px; }

            /* ---- Legend ---- */
            .cal-legend {
                display: flex; flex-wrap: wrap;
                align-items: center; gap: 16px;
                margin-bottom: 24px;
                padding: 12px 16px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
            }
            .dark .cal-legend { background: rgba(30,41,59,.4); border-color: #1e293b; }
            .legend-dot { width: 10px; height: 10px; border-radius: 4px; flex-shrink: 0; }
            .legend-item { display: flex; align-items: center; gap: 7px; font-size: .72rem; font-weight: 700; color: #64748b; letter-spacing: .04em; text-transform: uppercase; }
            .dark .legend-item { color: #94a3b8; }
            .dot-full  { background: #6366f1; box-shadow: 0 2px 6px rgba(99,102,241,.4); }
            .dot-in    { background: #38bdf8; box-shadow: 0 2px 6px rgba(56,189,248,.4); }
            .dot-orange { background: #f59e0b; box-shadow: 0 2px 6px rgba(245,158,11,.4); }
            .dot-red   { background: #ef4444; box-shadow: 0 2px 6px rgba(239,68,68,.4); }
            .dot-none  { background: #e2e8f0; }
            .dark .dot-none { background: #334155; }

            /* ---- Day headers ---- */
            .cal-day-label {
                text-align: center;
                font-size: .62rem;
                font-weight: 800;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: .08em;
                padding: 6px 0 10px;
            }

            /* ---- Day cells ---- */
            .cal-cell {
                position: relative;
                aspect-ratio: 1;
                border-radius: 14px;
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                cursor: default;
                transition: transform .2s, box-shadow .2s;
            }
            .cal-cell:hover { transform: scale(1.08); z-index: 10; }

            .cal-cell-none {
                background: #f8fafc;
                border: 1px solid #f1f5f9;
            }
            .dark .cal-cell-none { background: rgba(30,41,59,.5); border-color: #1e293b; }

            .cal-cell-full {
                background: linear-gradient(135deg, #6366f1, #4f46e5);
                box-shadow: 0 4px 16px rgba(99,102,241,.35);
            }
            .cal-cell-full:hover { box-shadow: 0 8px 24px rgba(99,102,241,.50); }

            .cal-cell-in {
                background: linear-gradient(135deg, #38bdf8, #0ea5e9);
                box-shadow: 0 4px 16px rgba(56,189,248,.35);
            }
            .cal-cell-in:hover { box-shadow: 0 8px 24px rgba(56,189,248,.50); }

            .cal-cell-orange {
                background: linear-gradient(135deg, #f59e0b, #d97706);
                box-shadow: 0 4px 16px rgba(245,158,11,.35);
            }
            .cal-cell-orange:hover { box-shadow: 0 8px 24px rgba(245,158,11,.50); }

            .cal-cell-red {
                background: linear-gradient(135deg, #ef4444, #dc2626);
                box-shadow: 0 4px 16px rgba(239,68,68,.35);
            }
            .cal-cell-red:hover { box-shadow: 0 8px 24px rgba(239,68,68,.50); }

            .cal-day-num-colored { font-size: .9rem; font-weight: 900; color: #fff; letter-spacing: -.01em; }
            .cal-day-num-plain   { font-size: .9rem; font-weight: 700; color: #94a3b8; letter-spacing: -.01em; }
            .dark .cal-day-num-plain { color: #475569; }

            .cal-pip {
                width: 5px; height: 5px; border-radius: 50%;
                background: rgba(255,255,255,.7); margin-top: 3px;
            }

            /* ---- Quote footer ---- */
            .cal-quote {
                margin-top: 24px;
                padding: 16px 20px;
                background: linear-gradient(135deg, #eef2ff, #e0e7ff);
                border: 1px solid #c7d2fe;
                border-radius: 16px;
                text-align: center;
            }
            .dark .cal-quote { background: rgba(30,27,75,.3); border-color: rgba(99,102,241,.25); }
            .cal-quote p {
                font-size: .8rem;
                color: #6366f1;
                font-weight: 600;
                font-style: italic;
                line-height: 1.65;
                margin: 0;
            }
            .dark .cal-quote p { color: #818cf8; }
        </style>

        <div class="cal-wrap flex flex-col">
            {{-- Header --}}
            <div class="cal-header">
                <div>
                    <h2 class="cal-title">Kalender Presensi</h2>
                    <p class="cal-month-label">
                        {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->translatedFormat('F Y') }}
                    </p>
                </div>
                <div class="cal-nav-group">
                    <button wire:click="previousMonth" class="cal-nav-btn" title="Bulan sebelumnya">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button wire:click="nextMonth" class="cal-nav-btn" title="Bulan berikutnya">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            {{-- Legend --}}
            <div class="cal-legend">
                <div class="legend-item"><div class="legend-dot dot-full"></div> Hadir Lengkap</div>
                <div class="legend-item"><div class="legend-dot dot-in"></div> Masuk Saja</div>
                <div class="legend-item"><div class="legend-dot dot-orange"></div> Dinas Luar</div>
                <div class="legend-item"><div class="legend-dot dot-red"></div> Izin / Sakit</div>
                <div class="legend-item"><div class="legend-dot dot-none"></div> Tanpa Data</div>
            </div>

            {{-- Grid --}}
            <div class="grid grid-cols-7 gap-1 md:gap-2">
                {{-- Day labels --}}
                @foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $d)
                    <div class="cal-day-label">{{ $d }}</div>
                @endforeach

                {{-- Empty leading cells --}}
                @for($i = 0; $i < $firstDayOfMonth; $i++)
                    <div></div>
                @endfor

                {{-- Day cells --}}
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $dataDay = $presenceData[$day] ?? null;
                        $status = $dataDay['status'] ?? 'none';
                        $tipeAbsens = $dataDay['jam_masuk'] ?? '-';
                        $keteranganDl = $dataDay['jam_pulang'] ?? '-';
                        $isIzin = $dataDay['is_izin'] ?? false;

                        $cellClass = $status === 'dark' ? 'cal-cell-full' : ($status === 'light' ? 'cal-cell-in' : ($status === 'orange' ? 'cal-cell-orange' : ($status === 'red' ? 'cal-cell-red' : 'cal-cell-none')));
                        $numClass  = in_array($status, ['dark','light','orange','red']) ? 'cal-day-num-colored' : 'cal-day-num-plain';
                    @endphp
                    <div class="cal-cell {{ $cellClass }}" 
                         @if(in_array($status, ['dark','light','orange','red']))
                         x-data="{ open: false }" 
                         @click="open = !open" 
                         @click.outside="open = false" 
                         style="cursor: pointer;"
                         @endif
                    >
                        <span class="{{ $numClass }}">{{ $day }}</span>
                        @if(in_array($status, ['dark','light','orange','red']))
                            <div class="cal-pip"></div>

                            {{-- Gelembung Pop-up Detail Jam --}}
                            <div x-cloak x-show="open" 
                                 x-transition.opacity.scale.origin.bottom
                                 class="absolute bottom-full mb-2 w-max px-3 py-2 bg-indigo-900 border border-indigo-500 rounded-xl shadow-lg z-50 text-center flex flex-col"
                                 style="display: none; min-width: 90px;">
                                @if($isIzin)
                                    <p style="font-size: 0.65rem; font-weight:700; color:#cbd5e1; margin:0 0 2px;">Status: <b style="color:#fb7185;">{{ $tipeAbsens }}</b></p>
                                    <p style="font-size: 0.65rem; font-weight:700; color:#cbd5e1; margin:0;">Alasan: <b style="color:#fff;">{{ $keteranganDl }}</b></p>
                                @else
                                    <p style="font-size: 0.65rem; font-weight:700; color:#cbd5e1; margin:0 0 2px;">Masuk: <b style="color:#fff;">{{ $tipeAbsens }}</b></p>
                                    <p style="font-size: 0.65rem; font-weight:700; color:#cbd5e1; margin:0;">Pulang: <b style="color:#fff;">{{ $keteranganDl }}</b></p>
                                @endif
                                {{-- Panah Gelembung ke bawah --}}
                                <div style="position: absolute; bottom:-5px; left:50%; transform:translateX(-50%); width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid #312e81;"></div>
                            </div>
                        @endif
                    </div>
                @endfor
            </div>

            {{-- Quote --}}
            <div class="cal-quote">
                <p>"Disiplin adalah jembatan antara tujuan dan pencapaian. Pertahankan kehadiranmu!"</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
