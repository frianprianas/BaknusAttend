<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');
            .izin-wrap, .izin-wrap * { font-family: 'Plus Jakarta Sans', sans-serif !important; }
            .izin-wrap { background: transparent !important; border: none !important; box-shadow: none !important; padding: 0 !important; }

            /* Status card */
            .izin-status-card {
                border-radius: 20px; padding: 28px 24px;
                display: flex; flex-direction: column; align-items: center;
                gap: 16px; text-align: center;
            }
            .izin-icon-wrap {
                width: 72px; height: 72px; border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                font-size: 2rem;
            }
            .izin-status-title { font-size: 1.2rem; font-weight: 900; }
            .izin-status-date  { font-size: .8rem; font-weight: 600; color: #64748b; margin-top: 3px; }
            .izin-badge {
                display: inline-flex; align-items: center; gap: 6px;
                padding: 5px 14px; border-radius: 999px;
                font-size: .72rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
            }
            .badge-approved { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
            .badge-pending  { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }

            /* Alasan box */
            .izin-alasan {
                width: 100%; background: #f8fafc; border: 1.5px dashed #cbd5e1;
                border-radius: 14px; padding: 14px 18px;
                font-size: .875rem; color: #64748b; font-style: italic; line-height: 1.6;
            }
            .dark .izin-alasan { background: rgba(30,41,59,.5); border-color: #334155; color: #94a3b8; }

            /* Warning box */
            .izin-warning {
                width: 100%; padding: 14px 18px; border-radius: 14px;
                font-size: .82rem; font-weight: 600; line-height: 1.5;
            }
            .warn-orange { background: #fff7ed; border: 1px solid #fed7aa; color: #c2410c; }
            .warn-orange strong { color: #ea580c; display: block; margin-bottom: 3px; }
            .dark .warn-orange { background: rgba(124,45,18,.2); border-color: rgba(253,186,116,.3); color: #fb923c; }

            /* Cancel button */
            .btn-cancel {
                background: none; border: 1.5px solid #fca5a5; color: #dc2626;
                padding: 9px 20px; border-radius: 10px; font-size: .8rem; font-weight: 700;
                cursor: pointer; transition: all .15s; font-family: 'Plus Jakarta Sans', sans-serif !important;
            }
            .btn-cancel:hover { background: #fef2f2; border-color: #f87171; }

            /* Lampiran link */
            .izin-lampiran {
                display: inline-flex; align-items: center; gap: 6px;
                font-size: .8rem; font-weight: 600; color: #6366f1; text-decoration: none;
                padding: 6px 14px; border-radius: 8px; border: 1px solid #c7d2fe;
                background: #eef2ff; transition: all .15s;
            }
            .izin-lampiran:hover { background: #e0e7ff; border-color: #a5b4fc; }

            /* Form area */
            .izin-form-box { display: flex; flex-direction: column; align-items: center; padding: 4px 0; }
            .izin-form-title { font-size: 1.2rem; font-weight: 900; color: #0f172a; margin: 0 0 6px; }
            .dark .izin-form-title { color: #f1f5f9; }
            .izin-form-sub { font-size: .875rem; color: #64748b; margin: 0 0 28px; text-align: center; }

            /* Submit button */
            .btn-izin-submit {
                width: 100%; padding: 14px 24px; border-radius: 14px;
                border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
                font-size: .9375rem !important; font-weight: 800 !important;
                color: #fff !important; text-transform: uppercase; letter-spacing: .04em;
                background: linear-gradient(135deg, #f97316, #fb923c) !important;
                box-shadow: 0 8px 24px rgba(249,115,22,.35) !important;
                transition: transform .15s, box-shadow .15s !important;
                font-family: 'Plus Jakarta Sans', sans-serif !important;
            }
            .btn-izin-submit:hover { transform: translateY(-2px) !important; box-shadow: 0 14px 36px rgba(249,115,22,.45) !important; }
            .btn-izin-submit:active { transform: scale(.98) !important; }
            .btn-izin-submit:disabled { background: #94a3b8 !important; box-shadow: none !important; transform: none !important; opacity: .7; cursor: not-allowed; }
        </style>

        @php $user = auth()->user(); @endphp

        <div class="izin-wrap">
            @if($izinHariIni)
                {{-- ===== Status Aktif ===== --}}
                <div class="izin-status-card" style="background: {{ $izinHariIni->tipe === 'Izin' ? '#eff6ff' : '#fefce8' }}; border: 1.5px dashed {{ $izinHariIni->tipe === 'Izin' ? '#bfdbfe' : '#fde68a' }};">
                    <div class="izin-icon-wrap" style="background:{{ $izinHariIni->tipe === 'Izin' ? '#dbeafe' : '#fef9c3' }}">
                        {{ $izinHariIni->tipe === 'Izin' ? '📋' : '🤒' }}
                    </div>

                    <div>
                        <h2 class="izin-status-title" style="color: {{ $izinHariIni->tipe === 'Izin' ? '#1d4ed8' : '#a16207' }};">
                            Pengajuan {{ $izinHariIni->tipe }} Aktif
                        </h2>
                        <p class="izin-status-date">{{ $izinHariIni->tanggal->format('d M Y') }}</p>
                        <div style="margin-top:10px;">
                            <span class="izin-badge {{ $izinHariIni->status === 'Disetujui' ? 'badge-approved' : 'badge-pending' }}">
                                @if($izinHariIni->status === 'Disetujui')
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                                {{ $izinHariIni->status }}
                            </span>
                        </div>
                    </div>

                    <div class="izin-alasan w-full">"{{ $izinHariIni->alasan }}"</div>

                    @if($izinHariIni->bukti)
                        <a href="{{ asset('storage/' . $izinHariIni->bukti) }}" target="_blank" class="izin-lampiran">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            Lihat Lampiran Bukti
                        </a>
                    @endif

                    <div class="izin-warning warn-orange w-full">
                        <strong>⛔ Absensi Dinonaktifkan</strong>
                        Anda tidak dapat melakukan absensi selama pengajuan ini aktif.
                    </div>

                    <button
                        type="button"
                        wire:click="batalkan"
                        wire:confirm="Batalkan pengajuan izin/sakit hari ini?"
                        class="btn-cancel"
                    >
                        ✖ Batalkan Pengajuan
                    </button>
                </div>

            @else
                {{-- ===== Form Baru ===== --}}
                <div class="izin-form-box">
                    <div style="width:60px;height:60px;border-radius:16px;background:linear-gradient(135deg,#f97316,#fb923c);box-shadow:0 8px 20px rgba(249,115,22,.35);display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.8" width="28" height="28">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>

                    <h2 class="izin-form-title">Izin / Sakit</h2>
                    <p class="izin-form-sub">Ajukan keterangan jika Anda tidak dapat hadir hari ini.</p>

                    <form wire:submit.prevent="submit" class="w-full space-y-4">
                        {{ $this->form }}

                        <div class="pt-2">
                            <button type="submit" wire:loading.attr="disabled" class="btn-izin-submit">
                                <span wire:loading.remove wire:target="submit">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    Kirim Pengajuan
                                </span>
                                <span wire:loading wire:target="submit">Mengirim...</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
