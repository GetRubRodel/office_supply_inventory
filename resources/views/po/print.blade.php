<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order</title>
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
            font-size: 16pt;
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

        .supplier-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        .supplier-table td {
            padding: 2px 0;
            font-size: 9pt;
            vertical-align: top;
        }
        .supplier-table .label { font-weight: bold; }

        .delivery-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        .delivery-table td {
            padding: 2px 0;
            font-size: 9pt;
            vertical-align: top;
        }
        .delivery-table .label { font-weight: bold; }

        .letter-text {
            font-size: 9pt;
            line-height: 1.5;
            margin: 6px 0;
        }
        .letter-text p { margin: 4px 0; }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
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
        .items-table .text-right { text-align: right; }
        .items-table .text-bold { font-weight: bold; }

        .amount-words {
            font-size: 8.5pt;
            font-weight: bold;
            margin: 4px 0;
            padding: 4px;
            border: 1px solid #000;
        }

        .penalty-text {
            font-size: 8pt;
            line-height: 1.4;
            margin: 6px 0;
        }

        .signatories {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .signatories td {
            width: 50%;
            padding: 5px 10px;
            vertical-align: top;
        }
        .signatory-label {
            font-weight: bold;
            font-size: 9pt;
        }
        .signatory-name {
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 40px;
            display: inline-block;
            min-width: 200px;
            font-weight: bold;
        }
        .signatory-title {
            font-size: 8pt;
            color: #333;
            margin-top: 2px;
        }
        .conforme-block {
            margin-top: 10px;
        }
        .conforme-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            display: inline-block;
            min-width: 220px;
        }

        .funds-section {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .funds-section td {
            padding: 3px 10px;
            vertical-align: top;
            font-size: 9pt;
        }
        .funds-label {
            font-weight: bold;
            font-size: 9pt;
        }
        .funds-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            display: inline-block;
            min-width: 200px;
        }
        .funds-title {
            font-size: 8pt;
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
                <h1>Purchase Order</h1>
            </td>
            <td style="width:45%;" class="doc-control-area">
                <span class="label">Document Control No.:</span> CHR-ROXII-ASD-FR-012<br>
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

    {{-- ═══ SUPPLIER INFO ═══ --}}
    <table class="supplier-table">
        <tr>
            <td style="width:50%;"><span class="label">Supplier:</span> {{ $po->supplier_name ?? '________________________' }}</td>
            <td style="width:50%;"><span class="label">P.O. No.:</span> {{ $po->po_no ?? '________________________' }}</td>
        </tr>
        @if(!empty($po->contact_number))
        <tr>
            <td><span class="label">Contact Number:</span> {{ $po->contact_number }}</td>
            <td></td>
        </tr>
        @endif
        <tr>
            <td><span class="label">Address:</span> {{ $po->address ?? '________________________' }}</td>
            <td><span class="label">Date:</span> {{ $po->date ? \Carbon\Carbon::parse($po->date)->format('m/d/Y') : '________________________' }}</td>
        </tr>
        <tr>
            <td><span class="label">TIN:</span> {{ $po->tin ? \App\Support\TinFormatter::format($po->tin) : '________________________' }}</td>
            <td><span class="label">Mode of Procurement:</span> {{ $po->mode_of_procurement ?? '________________________' }}</td>
        </tr>
    </table>

    {{-- ═══ LETTER BODY ═══ --}}
    <div class="letter-text">
        <p><strong>Gentlemen:</strong></p>
        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Please furnish this Office the following articles subject to the terms and conditions contain herein:</p>
    </div>

    {{-- ═══ DELIVERY INFO ═══ --}}
    <table class="delivery-table">
        <tr>
            <td style="width:50%;"><span class="label">Place of Delivery:</span> {{ $po->place_of_delivery ?? '________________________' }}</td>
            <td style="width:50%;"><span class="label">Delivery Term:</span> {{ $po->delivery_term ?? '________________________' }}</td>
        </tr>
        <tr>
            <td><span class="label">Date of Delivery:</span> {{ $po->date_of_delivery ? \Carbon\Carbon::parse($po->date_of_delivery)->format('m/d/Y') : '________________________' }}</td>
            <td><span class="label">Payment Term:</span> {{ $po->payment_term ?? '________________________' }}</td>
        </tr>
    </table>

    {{-- ═══ ITEMS TABLE ═══ --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:12%;">Stock No.</th>
                <th style="width:8%;">Unit</th>
                <th style="width:32%;">Description</th>
                <th style="width:14%;">Quantity/Term</th>
                <th style="width:17%;">Unit Cost</th>
                <th style="width:17%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($po->items as $item)
                <tr>
                    <td class="text-center">{{ $item->stock_no ?? '-' }}</td>
                    <td class="text-center">{{ $item->unit ?? '-' }}</td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td class="text-center">{{ number_format($item->quantity) }}</td>
                    <td class="text-right">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding:20px;">No items listed.</td>
                </tr>
            @endforelse

            {{-- Filler rows --}}
            @for($i = ($po->items->count() ?? 0); $i < 12; $i++)
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endfor
        </tbody>
    </table>

    {{-- ═══ AMOUNT IN WORDS + TOTAL ═══ --}}
    @php
        $total = (float) ($po->total_amount ?? 0);
        $words = $po->amount_in_words ?? \App\Models\PurchaseOrder::numberToWords($total);
    @endphp
    <div class="amount-words">
        ***{{ $words }}*** &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <span style="float:right;">{{ number_format($total, 2) }}</span>
    </div>

    {{-- ═══ PENALTY ═══ --}}
    <div class="penalty-text">
        In case of failure to make full delivery within the time specified above, penalty of one-tenth (1/10) of one percent for every day of delay shall be imposed.
    </div>

    {{-- ═══ SIGNATORIES ═══ --}}
    <table class="signatories">
        <tr>
            <td>
                <div class="signatory-label">Conforme:</div>
                <div class="conforme-block">
                    <div class="conforme-line">{{ $po->conforme_name ?? '________________________' }}</div>
                    <div class="signatory-title">Signature Over Printed Name of Supplier</div>
                    @if($po->conforme_date)
                        <div style="margin-top:8px;"><span class="signatory-title">Date: </span>{{ \Carbon\Carbon::parse($po->conforme_date)->format('m/d/Y') }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="signatory-name">{{ $po->authorized_official_name ?? '________________________' }}</div>
                <div class="signatory-title">{{ $po->authorized_official_designation ?? 'Authorized Official' }}</div>
            </td>
        </tr>
    </table>

    {{-- ═══ FUNDS AVAILABLE ═══ --}}
    <div style="margin-top:30px;">
        <div class="funds-label">Funds Available:</div>
        <table class="funds-section">
            <tr>
                <td style="width:50%;">
                    <div class="funds-line">{{ $po->funds_available_by ?? '________________________' }}</div>
                    <div class="funds-title">{{ $po->funds_available_designation ?? 'Admin Asst. II/Budget Officer' }}</div>
                </td>
                <td style="width:50%;">
                    <span class="funds-label">ALOBS No.:</span> {{ $po->alobs_no ?? '________________' }}<br>
                    <span class="funds-label">Amount:</span> {{ $po->alobs_amount ? '₱ ' . number_format($po->alobs_amount, 2) : '________________' }}
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
