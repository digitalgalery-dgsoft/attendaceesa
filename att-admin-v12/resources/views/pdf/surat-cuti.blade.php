<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Izin Cuti</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
        }
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 20px;
        }
        .content-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .content-table td {
            padding: 5px;
            vertical-align: top;
        }
        .content-table td:first-child {
            width: 30%;
            font-weight: bold;
        }
        .content-table td:nth-child(2) {
            width: 5%;
        }
        .signature-section {
            width: 100%;
            margin-top: 50px;
        }
        .signature-table {
            width: 100%;
            text-align: center;
        }
        .signature-table td {
            width: 33%;
            vertical-align: bottom;
            height: 100px; /* Space for signature */
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'Perusahaan') }}</h1>
        <p>Formulir Pengajuan Izin / Cuti Karyawan</p>
    </div>

    <div class="title">SURAT IZIN CUTI</div>

    <p>Yang bertanda tangan di bawah ini:</p>

    <table class="content-table">
        <tr>
            <td>Nama Lengkap</td>
            <td>:</td>
            <td>{{ $record->employee->full_name }}</td>
        </tr>
        <tr>
            <td>Posisi / Jabatan</td>
            <td>:</td>
            <td>{{ $record->employee->position->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Cabang</td>
            <td>:</td>
            <td>{{ $record->employee->branch->name ?? '-' }}</td>
        </tr>
    </table>

    <p>Mengajukan permohonan <strong>Cuti ({{ ucwords(str_replace('_', ' ', $record->sub_type)) }})</strong> dengan rincian sebagai berikut:</p>

    <table class="content-table">
        <tr>
            <td>Tanggal Mulai</td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($record->start_date)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td>Tanggal Selesai</td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($record->end_date)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td>Jumlah Hari</td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($record->start_date)->diffInDays(\Carbon\Carbon::parse($record->end_date)) + 1 }} Hari</td>
        </tr>
        <tr>
            <td>Alasan Cuti</td>
            <td>:</td>
            <td>{{ $record->notes ?? '-' }}</td>
        </tr>
    </table>

    <p>Demikian surat permohonan cuti ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    <p>Pemohon,</p>
                    <br><br><br>
                    <p class="signature-name">{{ $record->employee->full_name }}</p>
                    <p>Tanggal: {{ \Carbon\Carbon::parse($record->created_at)->translatedFormat('d F Y') }}</p>
                </td>
                <td>
                    <p>Mengetahui,</p>
                    <br><br><br>
                    <p class="signature-name">{{ $record->employee->supervisor->full_name ?? 'Head / Supervisor' }}</p>
                    <p>Tanggal: {{ $record->head_approved_at ? \Carbon\Carbon::parse($record->head_approved_at)->translatedFormat('d F Y') : '-' }}</p>
                </td>
                <td>
                    <p>Menyetujui,</p>
                    <br><br><br>
                    <p class="signature-name">{{ $record->approver->name ?? 'HRD' }}</p>
                    <p>Tanggal: {{ $record->hrd_approved_at ? \Carbon\Carbon::parse($record->hrd_approved_at)->translatedFormat('d F Y') : '-' }}</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
