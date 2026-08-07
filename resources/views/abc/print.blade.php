<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Abstract of Bids &amp; Canvass</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* ─── TOP HEADER ─── */
        .top-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .top-header td { padding: 0; vertical-align: top; }

        .logo-area { width: 70px; text-align: center; }
        .logo-area img { width: 60px; height: auto; }

        .title-area { padding-left: 8px; vertical-align: middle; }
        .title-area h1 {
            font-size: 13pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .doc-control-area {
            text-align: right;
            font-size: 7pt;
            line-height: 1.4;
            white-space: nowrap;
        }
        .doc-control-area .label { font-weight: bold; }

        .sub-header {
            width: 100%;
            font-size: 7pt;
            margin-top: 2px;
            border-collapse: collapse;
        }
        .sub-header td { padding: 0; }

        .header-divider {
            border: none;
            border-top: 2px solid #000;
            margin: 3px 0 5px 0;
        }

        /* ─── INFO ROW ─── */
        .info-row {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        .info-row td {
            padding: 2px 0;
            font-size: 8pt;
            vertical-align: top;
        }
        .info-row .label { font-weight: bold; }

        /* ─── MAIN TABLE ─── */
        .abc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }
        .abc-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: center;
            font-weight: bold;
            font-size: 7pt;
        }
        .abc-table td {
            border: 1px solid #000;
            padding: 3px;
            vertical-align: top;
            font-size: 7.5pt;
        }
        .abc-table .text-center { text-align: center; }
        .abc-table .text-right { text-align: right; }
        .abc-table .text-bold { font-weight: bold; }

        /* ─── WINNING SUPPLIER COLUMN ─── */
        .abc-table th.winner-col,
        .abc-table td.winner-col {
            font-weight: bold;
            background-color: #e8f0e8;
        }

        /* ─── CERTIFICATION ─── */
        .certification {
            font-size: 7.5pt;
            line-height: 1.5;
            margin: 8px 0;
            text-align: justify;
        }

        /* ─── COMMITTEE ─── */
        .committee {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .committee td {
            text-align: center;
            padding: 2px 8px;
            vertical-align: top;
            font-size: 7.5pt;
        }
        .committee .name {
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 35px;
            border-top: 1px solid #000;
            padding-top: 3px;
            display: inline-block;
            min-width: 130px;
        }
        .committee .designation {
            font-size: 7pt;
            margin-top: 1px;
        }
        .committee-label {
            font-weight: bold;
            font-size: 8pt;
            text-align: left;
            margin-bottom: 4px;
        }

        /* ─── APPROVAL ─── */
        .approval {
            margin-top: 20px;
        }
        .approval .approval-label {
            font-weight: bold;
            font-size: 8pt;
        }
        .approval .signatory-block {
            margin-top: 35px;
            margin-left: 0;
        }
        .approval .signatory-line {
            border-top: 1px solid #000;
            padding-top: 3px;
            display: inline-block;
            min-width: 240px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .approval .signatory-title {
            font-size: 7pt;
            margin-top: 1px;
        }

        /* ─── SUPPLIER HEADER ROW ─── */
        .supplier-header {
            font-size: 7pt;
        }
    </style>
</head>
<body>

    {{-- ═══ TOP HEADER: Logo + Title + Doc Control ═══ --}}
    <table class="top-header">
        <tr>
            <td class="logo-area">
                @php
                    $logoPath = public_path('images/logo.png');
                @endphp
                @if(file_exists($logoPath))
                    <img src="{{ asset('images/logo.png') }}" alt="System Logo" style="height:60px;width:auto;">
                @else
                    <div style="width:60px;height:60px;border:2px solid #ccc;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:5pt;font-weight:bold;text-align:center;line-height:1.2;color:#999;">
                        LOGO
                    </div>
                @endif
            </td>
            <td class="title-area">
                <h1>Abstract of Bids &amp; Canvass</h1>
            </td>
            <td style="width:40%;" class="doc-control-area">
                <span class="label">Document Control No.:</span> CHR-ROXII-ASD-FR-006<br>
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

    {{-- ═══ RFQ INFO ═══ --}}
    <table class="info-row">
        <tr>
            <td style="width:33%;">
                <span class="label">RFQ NO.:</span>
                {{ $abc->rfq?->rfq_no ?? '________________________' }}
            </td>
            <td style="width:33%;">
                <span class="label">Date of Advertisement:</span>
                {{ $abc->date_of_advertisement ? \Carbon\Carbon::parse($abc->date_of_advertisement)->format('m/d/Y') : '________________________' }}
            </td>
            <td style="width:34%;">
                <span class="label">Date of Opening:</span>
                {{ $abc->date_of_opening ? \Carbon\Carbon::parse($abc->date_of_opening)->format('m/d/Y') : '________________________' }}
            </td>
        </tr>
    </table>

    {{-- ═══ MAIN TABLE ═══ --}}
    @php
        use App\Services\AbstractOfCanvassService;

        $s1 = $abc->supplier1_name;
        $s2 = $abc->supplier2_name;
        $s3 = $abc->supplier3_name;
        $totals = AbstractOfCanvassService::supplierTotals($abc);
        $total1 = $totals[0];
        $total2 = $totals[1];
        $total3 = $totals[2];
        $winnerIndex = AbstractOfCanvassService::lowestTotalIndex($totals);
        $winnerSupplier = $abc->winningSupplier;
        $winnerName = $winnerSupplier?->name
            ?? ($abc->recommendation ?: null)
            ?? ([$s1, $s2, $s3][$winnerIndex] ?? null);
        $winnerColumns = AbstractOfCanvassService::tiedLowestIndexes($totals);
        $isWinnerColumn = fn (int $i): bool => in_array($i, $winnerColumns, true);
        $winnerColClass = fn (int $i): string => $isWinnerColumn($i) ? 'winner-col' : '';
    @endphp

    <table class="abc-table">
        <thead>
            <tr>
                <th style="width:5%;">Item</th>
                <th style="width:5%;">Qty</th>
                <th style="width:6%;">Unit</th>
                <th style="width:34%;">Description of Articles Called for Per Advertisement</th>
                <th style="width:50%;" colspan="3">SUPPLIER</th>
            </tr>
            <tr class="supplier-header">
                <th colspan="4"></th>
                <th style="width:16.67%;" class="{{ $winnerColClass(0) }}">{{ $s1 ?? 'Supplier 1' }}</th>
                <th style="width:16.67%;" class="{{ $winnerColClass(1) }}">{{ $s2 ?? 'Supplier 2' }}</th>
                <th style="width:16.67%;" class="{{ $winnerColClass(2) }}">{{ $s3 ?? 'Supplier 3' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($abc->items as $item)
                <tr>
                    <td class="text-center">{{ $item->item_number ?? '-' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-center">{{ $item->unit ?? '-' }}</td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td class="text-right">{{ $item->supplier1_price ? number_format($item->supplier1_price, 2) : '' }}</td>
                    <td class="text-right">{{ $item->supplier2_price ? number_format($item->supplier2_price, 2) : '' }}</td>
                    <td class="text-right">{{ $item->supplier3_price ? number_format($item->supplier3_price, 2) : '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding:15px;">No items listed.</td>
                </tr>
            @endforelse

            {{-- Filler rows --}}
            @for($i = ($abc->items->count() ?? 0); $i < 15; $i++)
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endfor
        </tbody>
        <tfoot>
            <tr class="text-bold">
                <td colspan="4" style="text-align:right;padding-right:8px;">TOTAL</td>
                <td class="text-right {{ $winnerColClass(0) }}">{{ number_format($total1, 2) }}</td>
                <td class="text-right {{ $winnerColClass(1) }}">{{ number_format($total2, 2) }}</td>
                <td class="text-right {{ $winnerColClass(2) }}">{{ number_format($total3, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ═══ CERTIFICATION ═══ --}}
    <div class="certification">
        <p>
            WE HEREBY CERTIFY that the above prices are reasonable being the most economically advantageous and responsive bid on the locality at the time of canvass based on the above abstract of canvass, it is recommended that the awards be made to:
            <strong>{{ $winnerName ?? '________________________' }}</strong>
            @if($winnerSupplier)
                <br>
                <span style="font-weight:normal;">
                    {{ trim($winnerSupplier->address ?? '') ? $winnerSupplier->address.' · ' : '' }}{{ trim($winnerSupplier->phone ?? '') ? 'Contact No.: '.$winnerSupplier->phone.' · ' : '' }}{{ trim($winnerSupplier->tin ?? '') ? 'TIN: '.$winnerSupplier->tin : '' }}
                </span>
            @endif
        </p>
    </div>

    {{-- ═══ COMMITTEE ON AWARDS ═══ --}}
    <div class="committee-label">COMMITTEE ON AWARDS:</div>
    <table class="committee">
        <tr>
            <td style="width:20%;">
                <div class="name">{{ $abc->chairman_name ?: '________________________' }}</div>
                <div class="designation">{{ $abc->chairman_designation ?: 'Chairman' }}</div>
            </td>
            <td style="width:20%;">
                <div class="name">{{ $abc->vice_chairman_name ?: '________________________' }}</div>
                <div class="designation">{{ $abc->vice_chairman_designation ?: 'Vice Chairman' }}</div>
            </td>
            <td style="width:20%;">
                <div class="name">{{ $abc->member1_name ?: '________________________' }}</div>
                <div class="designation">{{ $abc->member1_designation ?: 'Member' }}</div>
            </td>
            <td style="width:20%;">
                <div class="name">{{ $abc->member2_name ?: '________________________' }}</div>
                <div class="designation">{{ $abc->member2_designation ?: 'Member' }}</div>
            </td>
            <td style="width:20%;">
                <div class="name">{{ $abc->member3_name ?: '________________________' }}</div>
                <div class="designation">{{ $abc->member3_designation ?: 'Member' }}</div>
            </td>
        </tr>
    </table>

    {{-- ═══ APPROVED BY ═══ --}}
    <div class="approval">
        <div class="approval-label">APPROVED BY:</div>
        <div class="signatory-block">
            <div class="signatory-line">{{ $abc->approved_by_name ?: '________________________' }}</div>
            <div class="signatory-title">{{ $abc->approved_by_designation ?: 'Regional Director' }}</div>
        </div>
    </div>

</body>
</html>
