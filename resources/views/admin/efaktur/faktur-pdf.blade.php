<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Faktur Pajak {{ $fakturPajak->nsfp }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            background: #fff;
            padding: 20px 30px;
        }
        .header-box {
            border: 2px solid #000;
            padding: 10px 15px;
            margin-bottom: 16px;
        }
        .faktur-title {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .faktur-subtitle {
            font-size: 8pt;
            text-align: center;
            color: #555;
        }
        .nsfp-box {
            border: 1px solid #000;
            padding: 6px 10px;
            margin: 10px 0;
            font-size: 11pt;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            text-align: center;
            background: #f8f8f8;
        }
        .section-label {
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            margin-bottom: 4px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }
        .party-box {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }
        .party-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 14px;
        }
        .party-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .party-table td:first-child {
            width: 120px;
            font-weight: bold;
            white-space: nowrap;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 14px;
        }
        .items-table th {
            background: #e0e0e0;
            border: 1px solid #999;
            padding: 4px 6px;
            text-align: center;
            font-weight: bold;
        }
        .items-table td {
            border: 1px solid #bbb;
            padding: 3px 6px;
            vertical-align: top;
        }
        .items-table tfoot td {
            background: #f0f0f0;
            font-weight: bold;
            border: 1px solid #999;
            padding: 4px 6px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-table {
            width: 45%;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 10pt;
            margin-bottom: 20px;
        }
        .summary-table td {
            padding: 3px 8px;
            border-bottom: 1px solid #eee;
        }
        .summary-table .total-row td {
            border-top: 2px solid #000;
            font-weight: bold;
            font-size: 11pt;
            padding-top: 5px;
        }
        .signature-row {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        .signature-cell {
            display: table-cell;
            width: 33%;
            text-align: center;
            vertical-align: top;
            font-size: 9pt;
        }
        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #000;
            padding-top: 4px;
        }
        .divider { height: 12px; }
        .two-col { display: table; width: 100%; }
        .col-left { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
        .col-right { display: table-cell; width: 50%; vertical-align: top; padding-left: 10px; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header-box">
        <div class="faktur-title">FAKTUR PAJAK</div>
        <div class="faktur-subtitle">Dokumen ini merupakan bukti pungutan pajak berdasarkan Undang-Undang No. 42 Tahun 2009</div>
        <div class="nsfp-box">
            Kode &amp; Nomor Seri Faktur Pajak: {{ $fakturPajak->nsfp }}
        </div>
        <table style="width:100%; font-size:9pt;">
            <tr>
                <td style="width:50%"><strong>Tanggal Faktur:</strong> {{ $fakturPajak->tanggal_faktur->format('d F Y') }}</td>
                <td style="width:50%; text-align:right">
                    <strong>Masa Pajak:</strong>
                    {{ \Carbon\Carbon::create()->month($fakturPajak->masa_pajak)->format('F') }} {{ $fakturPajak->tahun_pajak }}
                </td>
            </tr>
        </table>
    </div>

    {{-- Penjual & Pembeli --}}
    <div class="two-col">
        <div class="col-left">
            <div class="section-label">Pengusaha Kena Pajak — Penjual</div>
            <table class="party-table">
                <tr>
                    <td>Nama</td>
                    <td>: {{ $fakturPajak->nama_penjual }}</td>
                </tr>
                <tr>
                    <td>NPWP</td>
                    <td>: <strong>{{ $fakturPajak->npwp_penjual ?: '—' }}</strong></td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>: {{ $fakturPajak->alamat_penjual ?: '—' }}</td>
                </tr>
            </table>
        </div>
        <div class="col-right">
            <div class="section-label">Pembeli Barang Kena Pajak / Jasa Kena Pajak</div>
            <table class="party-table">
                <tr>
                    <td>Nama</td>
                    <td>: {{ $fakturPajak->nama_pembeli }}</td>
                </tr>
                <tr>
                    <td>NPWP</td>
                    <td>: <strong>{{ $fakturPajak->npwp_pembeli ?: '00.000.000.0-000.000' }}</strong></td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>: {{ $fakturPajak->alamat_pembeli ?: '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="divider"></div>

    {{-- Line Items --}}
    <div class="section-label">Rincian Barang Kena Pajak / Jasa Kena Pajak</div>
    <div class="divider" style="height:6px;"></div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%">No.</th>
                <th style="width:3%">Kode</th>
                <th>Nama Barang / Jasa Kena Pajak</th>
                <th style="width:12%" class="text-right">Harga Satuan (Rp)</th>
                <th style="width:8%" class="text-center">Jml</th>
                <th style="width:10%" class="text-right">Total Harga (Rp)</th>
                <th style="width:9%" class="text-right">Diskon (Rp)</th>
                <th style="width:11%" class="text-right">DPP (Rp)</th>
                <th style="width:9%" class="text-right">PPN (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fakturPajak->items as $i => $item)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td class="text-center">{{ $item->kode_objek_pajak }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td class="text-right">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                <td class="text-center">{{ $item->jumlah_barang }}</td>
                <td class="text-right">{{ number_format($item->harga_total, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->diskon, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->dpp, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->ppn, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="text-right">Jumlah</td>
                <td class="text-right">{{ number_format($fakturPajak->dpp, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($fakturPajak->ppn, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Tax Summary --}}
    <table class="summary-table">
        <tr>
            <td>DPP</td>
            <td class="text-right">Rp {{ number_format($fakturPajak->dpp, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>PPN ({{ $fakturPajak->ppn_rate }}%)</td>
            <td class="text-right">Rp {{ number_format($fakturPajak->ppn, 2, ',', '.') }}</td>
        </tr>
        @if($fakturPajak->ppnbm > 0)
        <tr>
            <td>PPnBM</td>
            <td class="text-right">Rp {{ number_format($fakturPajak->ppnbm, 2, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td>Total Tagihan</td>
            <td class="text-right">Rp {{ number_format($fakturPajak->totalTagihan(), 2, ',', '.') }}</td>
        </tr>
    </table>

    {{-- Signature block --}}
    <div class="signature-row">
        <div class="signature-cell">
            &nbsp;
        </div>
        <div class="signature-cell">
            &nbsp;
        </div>
        <div class="signature-cell">
            <div>{{ $fakturPajak->nama_penjual }}</div>
            <div class="signature-line">
                <div>Nama Terang &amp; Cap Perusahaan</div>
                <div style="margin-top:4px;">Tanggal: {{ $fakturPajak->tanggal_faktur->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    {{-- Footer note --}}
    <div style="margin-top:20px; font-size:7.5pt; color:#666; border-top:1px solid #ccc; padding-top:8px;">
        Faktur Pajak ini dibuat secara elektronik oleh sistem Invent-MAG.
        Nomor Seri: {{ $fakturPajak->nsfp }} | Status: {{ $fakturPajak->statusLabel() }}
        @if($fakturPajak->djp_reference_number)
            | Ref DJP: {{ $fakturPajak->djp_reference_number }}
        @endif
    </div>

</body>
</html>
