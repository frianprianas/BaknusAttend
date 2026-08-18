<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Kehadiran Guru & TU - {{ $namaBulan }} {{ $year }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            background: #fff;
            margin: 0;
            padding: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px double #1e3a8a;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            width: 70px;
            height: auto;
        }

        .school-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .school-subtitle {
            font-size: 12px;
            font-weight: bold;
            color: #4b5563;
            margin: 2px 0;
        }

        .school-address {
            font-size: 9px;
            color: #6b7280;
            margin: 0;
        }

        .report-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 10px;
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 8px 5px;
            border: 1px solid #1e3a8a;
            text-align: center;
        }

        .data-table td {
            padding: 6px 5px;
            border: 1px solid #cbd5e1;
            text-align: center;
            font-size: 10px;
        }

        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-left {
            text-align: left !important;
            padding-left: 8px !important;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 4px;
        }

        .badge-guru { background: #dbeafe; color: #1e40af; }
        .badge-tu { background: #fef3c7; color: #92400e; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #ffedd5; color: #9a3412; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .signature-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 10px;
        }

        .signature-space {
            height: 60px;
        }

        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            @page { margin: 1cm; size: A4 landscape; }
        }
    </style>
</head>
<body>

    <!-- Tombol Cetak / Back untuk Mode Browser -->
    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="background: #1e3a8a; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer;">
            🖨️ Cetak Laporan / Simpan PDF
        </button>
        <button onclick="window.close()" style="background: #64748b; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-left: 8px;">
            ✖ Tutup
        </button>
    </div>

    <!-- Kop Surat -->
    <table class="header-table">
        <tr>
            <td style="width: 80px; text-align: center;">
                <img src="https://smk.baktinusantara666.sch.id/wp-content/uploads/2021/04/logo-666.png" class="logo" alt="Logo SMK 666" onerror="this.style.display='none'">
            </td>
            <td>
                <div class="school-title">SMK BHAKTI NUSANTARA 666</div>
                <div class="school-subtitle">Sistem Informasi Kehadiran Pegawai (BaknusAttend)</div>
                <div class="school-address">Jl. Percobaan No. 65 Cileunyi, Kab. Bandung - Jawa Barat | Email: info@smk.baktinusantara666.sch.id</div>
            </td>
        </tr>
    </table>

    <div class="report-title">
        LAPORAN REKAPITULASI KEHADIRAN GURU & STAFF TU
    </div>

    <div class="meta-info">
        <div><b>Periode:</b> {{ $namaBulan }} {{ $year }}</div>
        <div><b>Hari Kerja Efektif:</b> {{ $effectiveDays }} Hari</div>
        <div><b>Filter Jabatan:</b> {{ $roleFilter === 'all' ? 'Semua Pegawai (Guru & TU)' : $roleFilter }}</div>
        <div><b>Total Pegawai:</b> {{ count($rekapData) }} Orang</div>
    </div>

    <!-- Tabel Rekap -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 100px;">NIPY / ID</th>
                <th>Nama Pegawai</th>
                <th style="width: 70px;">Jabatan</th>
                <th style="width: 70px;">Hadir Sekolah</th>
                <th style="width: 70px;">Dinas Luar</th>
                <th style="width: 70px;">Terlambat</th>
                <th style="width: 70px;">Total Hadir</th>
                <th style="width: 50px;">Sakit</th>
                <th style="width: 50px;">Izin</th>
                <th style="width: 50px;">Alpa</th>
                <th style="width: 80px;">% Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekapData as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left font-mono">{{ $row['nipy'] }}</td>
                    <td class="text-left"><b>{{ $row['name'] }}</b></td>
                    <td>
                        <span class="badge {{ $row['role'] === 'Guru' ? 'badge-guru' : 'badge-tu' }}">
                            {{ $row['role'] }}
                        </span>
                    </td>
                    <td>{{ $row['hadir_sekolah'] }}</td>
                    <td>{{ $row['hadir_dl'] }}</td>
                    <td>{{ $row['terlambat'] }}</td>
                    <td><b>{{ $row['total_hadir'] }}</b></td>
                    <td>{{ $row['sakit'] }}</td>
                    <td>{{ $row['izin'] }}</td>
                    <td>{{ $row['alpa'] }}</td>
                    <td>
                        <span class="badge {{ $row['persentase'] >= 80 ? 'badge-success' : ($row['persentase'] >= 50 ? 'badge-warning' : 'badge-danger') }}">
                            {{ $row['persentase'] }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" style="padding: 20px; color: #94a3b8;">
                        Tidak ada data pegawai untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Tanda Tangan -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <b>Kepala Sekolah</b>
                <div class="signature-space"></div>
                <b><u>Drs. H. Teteng Mulyana, M.M.</u></b><br>
                NIPY. 19680315 199303 1 004
            </td>
            <td>
                Bandung, {{ date('d') }} {{ $namaBulan }} {{ $year }}<br>
                <b>Admin Kepegawaian / TU</b>
                <div class="signature-space"></div>
                <b><u>{{ auth()->user()->name ?? 'Administrator' }}</u></b><br>
                BaknusAttend Official Report
            </td>
        </tr>
    </table>

    <script>
        // Otomatis picu dialog print setelah halaman selesai dimuat
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
