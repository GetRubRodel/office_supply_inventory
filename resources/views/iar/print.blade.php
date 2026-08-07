<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inspection and Acceptance Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .top-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .top-header td { padding: 0; vertical-align: top; }

        .logo-area { width: 80px; text-align: center; }
        .logo-area img { width: 70px; height: auto; }

        .title-area { padding-left: 10px; vertical-align: middle; }
        .title-area h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .doc-control-area {
            text-align: right;
            font-size: 7.5pt;
            line-height: 1.5;
            white-space: nowrap;
        }
        .doc-control-area .label { font-weight: bold; }

        .sub-header {
            width: 100%;
            font-size: 7.5pt;
            margin-top: 2px;
            border-collapse: collapse;
        }
        .sub-header td { padding: 0; }

        .header-divider {
            border: none;
            border-top: 2px solid #000;
            margin: 4px 0 6px 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        .info-table td {
            padding: 3px 0;
            font-size: 9pt;
            vertical-align: top;
        }
        .info-table .label { font-weight: bold; }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }
        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 5px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 8.5pt;
        }
        .items-table td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .items-table .text-center { text-align: center; }

        .inspection-section {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .inspection-section td {
            padding: 5px 10px;
            vertical-align: top;
            font-size: 9pt;
        }
        .inspection-box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 4px;
            min-height: 100px;
        }
        .inspection-label {
            font-weight: bold;
            font-size: 9pt;
        }
        .checkbox-line {
            margin: 4px 0;
            font-size: 8.5pt;
        }
        .signatory-name {
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 30px;
            display: inline-block;
            min-width: 180px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .signatory-title {
            font-size: 8pt;
            color: #333;
            margin-top: 1px;
        }
    </style>
</head>
<body>

    {{-- ═══ TOP HEADER ═══ --}}
    <table class="top-header">
        <tr>
            <td class="logo-area">
                @php
                    $logoPath = public_path('images/logo.png');
                @endphp
                @if(file_exists($logoPath))
                    <img src="{{ asset('images/logo.png') }}" alt="System Logo" style="height:70px;width:auto;">
                @else
                    <div style="width:70px;height:70px;border:2px solid #ccc;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:6pt;font-weight:bold;text-align:center;line-height:1.2;color:#999;">
                        LOGO
                    </div>
                @endif
            </td>
            <td class="title-area">
                <h1>Inspection and Acceptance Report</h1>
            </td>
            <td style="width:45%;" class="doc-control-area">
                <span class="label">Document Control No.:</span> CHR-ROXII-ASD-FR-013<br>
                <span class="label">Revision:</span> 000 &nbsp;&nbsp;<br>
                <span class="label">Revision Date:</span> 24 November 2023
            </td>
        </tr>
    </table>

    {{-- ═══ SUB-HEADER ═══ --}}
    <table class="sub-header">
        <tr>
            <td style="text-align:left; width:50%;">
                <span class="label">Process Owner:</span> Commission on Human Rights XII
            </td>

        </tr>
    </table>

    <hr class="header-divider">

    {{-- ═══ SUPPLIER / PO INFO ═══ --}}
    <table class="info-table">
        <tr>
            <td style="width:50%;"><span class="label">Supplier:</span> {{ $iar->supplier_name ?? '________________________' }}</td>
            <td style="width:50%;"><span class="label">IAR No.:</span> {{ $iar->iar_no ?? '________________________' }}</td>
        </tr>
        <tr>
            <td><span class="label">PO No.:</span> {{ $iar->po_no ?? '________________________' }}</td>
            <td><span class="label">Date:</span> {{ $iar->date ? \Carbon\Carbon::parse($iar->date)->format('m/d/Y') : '________________________' }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Requisitioning Office/Department :</span> {{ $iar->requisitioning_office_dept ?? '________________________' }}</td>
        </tr>
    </table>

    {{-- ═══ ITEMS TABLE ═══ --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:18%;">Stock No.</th>
                <th style="width:12%;">Unit</th>
                <th style="width:50%;">Description</th>
                <th style="width:20%;">Quantity</th>
            </tr>
        </thead>
        <tbody>
            @forelse($iar->items as $item)
                <tr>
                    <td class="text-center">{{ $item->stock_no ?? '-' }}</td>
                    <td class="text-center">{{ $item->unit ?? '-' }}</td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td class="text-center">{{ number_format($item->quantity) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="padding:20px;">No items listed.</td>
                </tr>
            @endforelse

            @for($i = ($iar->items->count() ?? 0); $i < 12; $i++)
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endfor
        </tbody>
    </table>

    {{-- ═══ INSPECTED / ACCEPTED ═══ --}}
    <table class="inspection-section">
        <tr>
            <td style="width:50%;">
                <div class="inspection-box">
                    <div class="inspection-label">Inspected by:</div>
                    <div style="margin-top:4px;">
                        <span class="checkbox-line">{{ $iar->inspection_complete ? '☑' : '☐' }} Complete</span>&nbsp;&nbsp;&nbsp;
                        <span class="checkbox-line">{{ $iar->inspection_partial ? '☑' : '☐' }} Partial</span>
                    </div>
                    <div style="margin-top:8px;"><span class="label">Date:</span> {{ $iar->inspection_date ? \Carbon\Carbon::parse($iar->inspection_date)->format('m/d/Y') : '________________' }}</div>
                    <div class="signatory-name">{{ $iar->inspector_name ?? '________________________' }}</div>
                    <div class="signatory-title">{{ $iar->inspector_designation ?? '' }}</div>
                </div>
            </td>
            <td style="width:50%;">
                <div class="inspection-box">
                    <div class="inspection-label">Accepted by:</div>
                    <div style="margin-top:4px;">
                        <span class="checkbox-line">{{ $iar->acceptance_complete ? '☑' : '☐' }} Complete</span>&nbsp;&nbsp;&nbsp;
                        <span class="checkbox-line">{{ $iar->acceptance_partial ? '☑' : '☐' }} Partial</span>
                    </div>
                    <div style="margin-top:8px;"><span class="label">Date:</span> {{ $iar->acceptance_date ? \Carbon\Carbon::parse($iar->acceptance_date)->format('m/d/Y') : '________________' }}</div>
                    <div class="signatory-name">{{ $iar->acceptor_name ?? '________________________' }}</div>
                    <div class="signatory-title">{{ $iar->acceptor_designation ?? '' }}</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
