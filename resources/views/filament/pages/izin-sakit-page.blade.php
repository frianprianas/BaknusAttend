<x-filament-panels::page>
    @php $user = auth()->user(); @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');
        .izin-page, .izin-page * { font-family: 'Plus Jakarta Sans', sans-serif !important; }

        /* Shared card base */
        .ip-card {
            border-radius: 24px; padding: 36px 32px;
            display: flex; flex-direction: column; align-items: center;
            gap: 20px; text-align: center;
        }

        /* Icon badge */
        .ip-icon {
            width: 80px; height: 80px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.4rem;
        }

        /* Title */
        .ip-title {
            font-size: 1.5rem; font-weight: 900; color: #0f172a;
            letter-spacing: -.02em; margin: 0 0 4px;
        }
        .dark .ip-title { color: #f1f5f9; }
        .ip-date { font-size: .82rem; font-weight: 600; color: #64748b; }

        /* Status badge */
        .ip-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 16px; border-radius: 999px;
            font-size: .72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
        }
        .ip-badge-ok { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .ip-badge-wait { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }

        /* Alasan / quote */
        .ip-quote {
            width: 100%; background: #f8fafc; border: 1.5px dashed #cbd5e1;
            border-radius: 16px; padding: 16px 20px;
            font-size: .9rem; color: #475569; font-style: italic; line-height: 1.65;
        }
        .dark .ip-quote { background: rgba(30,41,59,.5); border-color: #334155; color: #94a3b8; }

        /* Attachment */
        .ip-attachment {
            display: inline-flex; align-items: center; gap: 7px;
            font-size: .82rem; font-weight: 700; color: #6366f1; text-decoration: none;
            padding: 8px 16px; border-radius: 10px;
            border: 1px solid #c7d2fe; background: #eef2ff;
            transition: all .15s;
        }
        .ip-attachment:hover { background: #e0e7ff; border-color: #a5b4fc; }

        /* Warning / info banners */
        .ip-banner {
            width: 100%; padding: 16px 20px; border-radius: 16px;
            font-size: .875rem; line-height: 1.55;
        }
        .ip-banner-red { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .ip-banner-red strong { color: #dc2626; display: block; margin-bottom: 4px; font-size: .9rem; }
        .dark .ip-banner-red { background: rgba(127,29,29,.2); border-color: rgba(248,113,113,.3); color: #f87171; }

        .ip-banner-blue { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
        .ip-banner-blue strong { color: #2563eb; }
        .dark .ip-banner-blue { background: rgba(30,58,138,.2); border-color: rgba(147,197,253,.3); color: #93c5fd; }

        /* Cancel button */
        .ip-btn-cancel {
            background: #fff; border: 1.5px solid #fca5a5; color: #dc2626;
            padding: 10px 24px; border-radius: 12px;
            font-size: .85rem; font-weight: 700; cursor: pointer;
            transition: all .15s; font-family: 'Plus Jakarta Sans', sans-serif !important;
            display: inline-flex; align-items: center; gap: 7px;
        }
        .ip-btn-cancel:hover { background: #fef2f2; border-color: #f87171; transform: translateY(-1px); }
        .dark .ip-btn-cancel { background: transparent; }

        /* Form header */
        .ip-form-header {
            display: flex; flex-direction: column; align-items: center;
            margin-bottom: 32px; text-align: center;
        }
        .ip-form-icon {
            width: 68px; height: 68px; border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #f97316, #fb923c);
            box-shadow: 0 8px 24px rgba(249,115,22,.35);
            font-size: 2rem; margin-bottom: 20px;
        }
        .ip-form-title { font-size: 1.35rem; font-weight: 900; color: #0f172a; margin: 0 0 6px; }
        .dark .ip-form-title { color: #f1f5f9; }
        .ip-form-sub { font-size: .875rem; color: #64748b; max-width: 360px; }

        /* Submit button */
        .ip-btn-submit {
            width: 100%; padding: 15px 24px; border-radius: 14px;
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            font-size: .9375rem !important; font-weight: 800 !important;
            color: #fff !important; text-transform: uppercase; letter-spacing: .04em;
            background: linear-gradient(135deg, #f97316, #fb923c) !important;
            box-shadow: 0 8px 24px rgba(249,115,22,.35) !important;
            transition: transform .15s, box-shadow .15s !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }
        .ip-btn-submit:hover { transform: translateY(-2px) !important; box-shadow: 0 14px 36px rgba(249,115,22,.45) !important; }
        .ip-btn-submit:active { transform: scale(.98) !important; }
        .ip-btn-submit:disabled { background: #94a3b8 !important; box-shadow: none !important; transform: none !important; opacity: .7; cursor: not-allowed; }
    </style>

    <div class="izin-page max-w-xl mx-auto w-full">

        @if($izinHariIni)
            {{-- ===== Status Aktif ===== --}}
            <x-filament::section>
                <div class="ip-card" style="background:{{ $izinHariIni->tipe === 'Izin' ? '#eff6ff' : '#fefce8' }};border:1.5px dashed {{ $izinHariIni->tipe === 'Izin' ? '#bfdbfe' : '#fde68a' }};">
                    <div class="ip-icon" style="background:{{ $izinHariIni->tipe === 'Izin' ? '#dbeafe' : '#fef9c3' }}">
                        {{ $izinHariIni->tipe === 'Izin' ? '📋' : '🤒' }}
                    </div>

                    <div>
                        <h1 class="ip-title" style="color:{{ $izinHariIni->tipe === 'Izin' ? '#1d4ed8' : '#a16207' }}">
                            Pengajuan {{ $izinHariIni->tipe }} Aktif
                        </h1>
                        <p class="ip-date">{{ $izinHariIni->tanggal->isoFormat('dddd, D MMMM Y') }}</p>
                        <div style="margin-top:12px;">
                            <span class="ip-badge {{ $izinHariIni->status === 'Disetujui' ? 'ip-badge-ok' : 'ip-badge-wait' }}">
                                @if($izinHariIni->status === 'Disetujui')
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                                {{ $izinHariIni->status }}
                            </span>
                        </div>
                    </div>

                    <div class="ip-quote w-full">"{{ $izinHariIni->alasan }}"</div>

                    @if($izinHariIni->bukti)
                        <a href="{{ asset('storage/' . $izinHariIni->bukti) }}" target="_blank" class="ip-attachment">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            Lihat Lampiran Bukti
                        </a>
                    @endif

                    <div class="ip-banner ip-banner-red w-full">
                        <strong>⛔ Absensi Dinonaktifkan</strong>
                        Anda tidak dapat melakukan absensi selama pengajuan ini masih aktif.
                    </div>

                    <button
                        type="button"
                        wire:click="batalkan"
                        wire:confirm="Yakin ingin membatalkan pengajuan ini?"
                        class="ip-btn-cancel"
                    >
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Batalkan Pengajuan
                    </button>
                </div>
            </x-filament::section>

        @elseif($sudahAbsenHariIni)
            {{-- ===== Sudah Hadir ===== --}}
            <x-filament::section>
                <div class="ip-card" style="background:#f0fdf4;border:1.5px dashed #86efac;">
                    <div class="ip-icon" style="background:#dcfce7">✅</div>
                    <div>
                        <h1 class="ip-title" style="color:#15803d">Anda Sudah Hadir Hari Ini!</h1>
                        <p style="font-size:.875rem;color:#64748b;margin:6px 0 0">Presensi sudah tercatat untuk hari ini.</p>
                    </div>
                    <div class="ip-banner ip-banner-blue w-full">
                        <strong>ℹ️ Informasi</strong>
                        Pengajuan Izin/Sakit hanya bisa dilakukan <strong>sebelum</strong> melakukan absensi masuk.
                    </div>
                </div>
            </x-filament::section>

        @else
            {{-- ===== Form Baru ===== --}}
            <x-filament::section>
                <div class="ip-form-header">
                    <div class="ip-form-icon">📄</div>
                    <h1 class="ip-form-title">Ajukan Izin / Sakit</h1>
                    <p class="ip-form-sub">
                        Halo, <strong>{{ $user->name }}</strong>!
                        Isi formulir di bawah jika Anda tidak dapat hadir hari ini.
                    </p>
                </div>

                <form wire:submit.prevent="submit" class="space-y-5">
                    {{ $this->form }}

                    <div class="pt-2">
                        <button type="submit" wire:loading.attr="disabled" class="ip-btn-submit">
                            <span wire:loading.remove wire:target="submit">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                Kirim Pengajuan
                            </span>
                            <span wire:loading wire:target="submit" style="display:flex;align-items:center;gap:8px;">
                                <svg style="animation:spin .8s linear infinite;width:18px;height:18px;" fill="none" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"></circle>
                                    <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.75"></path>
                                </svg>
                                Mengirim...
                            </span>
                        </button>
                    </div>
                </form>
            </x-filament::section>
        @endif

    </div>

    <style>@keyframes spin { to { transform:rotate(360deg); } }</style>
</x-filament-panels::page>
