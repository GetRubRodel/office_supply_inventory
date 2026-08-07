<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Request for Quotation</title>
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

        /* ─── TOP HEADER: Logo + Title + Doc Control ─── */
        .top-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .top-header td {
            padding: 0;
            vertical-align: top;
        }

        .logo-area {
            width: 80px;
            text-align: center;
        }
        .logo-area img {
            width: 70px;
            height: auto;
        }

        .title-area {
            padding-left: 10px;
            vertical-align: middle;
        }
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
        .doc-control-area .label {
            font-weight: bold;
        }

        /* ─── SUB-HEADER: Process Owner + Page ─── */
        .sub-header {
            width: 100%;
            font-size: 7.5pt;
            margin-top: 2px;
            border-collapse: collapse;
        }
        .sub-header td {
            padding: 0;
        }

        /* ─── DIVIDER ─── */
        .header-divider {
            border: none;
            border-top: 2px solid #000;
            margin: 4px 0 6px 0;
        }

        /* ─── INFO TABLE: RFQ No. + Date + Company Info ─── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }
        .info-table td {
            padding: 2px 0;
            font-size: 9pt;
            vertical-align: top;
        }
        .info-table .label {
            font-weight: bold;
        }

        /* ─── LETTER BODY ─── */
        .letter-body {
            margin: 8px 0;
        }
        .salutation {
            font-size: 9pt;
            margin-top: 8px;
        }
        .body-text {
            font-size: 9pt;
            line-height: 1.6;
            text-align: justify;
        }
        .body-text p {
            margin: 6px 0;
            text-indent: 30px;
        }

        .valediction {
            text-align: right;
            margin-top: 30px;
            font-size: 9pt;
        }
        .valediction .signatory-block {
            margin-top: 55px;
        }
        .valediction .signatory-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            display: inline-block;
            min-width: 220px;
        }
        .valediction .signatory-title {
            font-size: 8pt;
            font-weight: bold;
        }

        /* ─── ITEMS TABLE ─── */
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
        .items-table .text-center {
            text-align: center;
        }
        .items-table .text-right {
            text-align: right;
        }

        /* ─── FOOTER SIGNATORIES ─── */
        .footer-signatories {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .footer-signatories td {
            width: 50%;
            text-align: center;
            padding: 5px 20px;
            vertical-align: top;
        }
        .footer-label {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 5px;
        }
        .footer-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 45px;
            display: inline-block;
            min-width: 200px;
        }
        .footer-subtitle {
            font-size: 8pt;
            font-weight: bold;
            color: #333;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    {{-- ═══ TOP HEADER: Logo + Title + Doc Control ═══ --}}
    <table class="top-header">
        <tr>
            {{-- Logo --}}
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

            {{-- Title --}}
            <td class="title-area">
                <h1>Request for Quotation</h1>
            </td>

            {{-- Document Control --}}
            <td style="width:45%;" class="doc-control-area">
                <span class="label">Document Control No.:</span> CHR-ROXII-ASD-FR-007<br>
                <span class="label">Revision:</span> 000 &nbsp;&nbsp;<br>
                <span class="label">Revision Date:</span> 24 November 2023
            </td>
        </tr>
    </table>

    {{-- ═══ SUB-HEADER: Process Owner + Page ═══ --}}
    <table class="sub-header">
        <tr>
            <td style="text-align:left; width:50%;">
                <span class="label">Process Owner:</span> Commission on Human Rights XII
            </td>

        </tr>
    </table>

    <hr class="header-divider">

    {{-- ═══ RFQ NO. + DATE + COMPANY INFO ═══ --}}
    <table class="info-table">
        <tr>
            <td style="width:50%;">
                <span class="label">RFQ NO.:</span>
                {{ $rfq->rfq_no ?? '________________________' }}
            </td>
            <td style="width:50%; text-align:right;">
                <span class="label">Date:</span>
                {{ $rfq->date ? \Carbon\Carbon::parse($rfq->date)->format('m/d/Y') : '________________________' }}
            </td>
        </tr>
        <tr>
            <td style="padding-top:4px;">
                <span class="label">Company Name:</span>
                {{ $rfq->company_name ?? '_________________________' }}
            </td>
            <td></td>
        </tr>
        <tr>
            <td>
                <span class="label">Address:</span>
                {{ $rfq->address ?? '_______________________________' }}
            </td>
            <td></td>
        </tr>
        <tr>
            <td>
                <span class="label">Contact Number:</span>
                {{ $rfq->contact_number ?? '________________________' }}
            </td>
            <td></td>
        </tr>
    </table>

    <br>

    {{-- ═══ LETTER BODY ═══ --}}
    <div class="letter-body">
        <div class="salutation">
            <strong>Gentlemen/Madam:</strong>
        </div>

        <div class="body-text">
            <p>
                Please quote your prices for the articles listed below which this Office desires to purchase from you in a manner advantageous to the government.
            </p>
            <p>
                This Office reserved the right to reject any or all quotations, to waive formality therein to accept such quotations that may be considered most advantageous to the government.
            </p>
        </div>

        <div class="valediction">
            <p style="margin-bottom:5px;">Very truly yours,</p>
            <div class="signatory-block">
                <div class="signatory-line">
                    {{ $rfq->approved_by_name ?: '________________________' }}
                </div>
                <div class="signatory-title">
                    {{ $rfq->approved_by_designation ?: 'Regional Director' }}
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ ITEMS TABLE ═══ --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:10%;">Quantity</th>
                <th style="width:12%;">Unit</th>
                <th style="width:56%;">Description of Article/s</th>
                <th style="width:22%;">Unit Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rfq->items as $item)
                <tr>
                    <td class="text-center">{{ number_format($item->quantity) }}</td>
                    <td class="text-center">{{ $item->unit ?? '-' }}</td>
                    <td>{{ $item->item_description ?? '-' }}</td>
                    <td class="text-right">{{ $item->unit_cost ? number_format($item->unit_cost, 2) : '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="padding:20px;">No items listed.</td>
                </tr>
            @endforelse

            {{-- Fill blank rows so table looks complete on print --}}
            @for($i = ($rfq->items->count() ?? 0); $i < 15; $i++)
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endfor
        </tbody>
    </table>

    {{-- ═══ FOOTER SIGNATORIES ═══ --}}
    <table class="footer-signatories">
        <tr>
            <td>
                <div class="footer-label">Canvassed by:</div>
                <div class="footer-line">
                    {{ $rfq->canvassed_by_name ?: '________________________' }}
                </div>
                <div class="footer-subtitle">
                    {{ $rfq->canvassed_by_designation ?: 'Name of Canvasser' }}
                </div>
            </td>
            <td>
                <div class="footer-label">Quoted by:</div>
                <div class="footer-line">
                    {{ $rfq->quoted_by_name ?: '________________________' }}
                </div>
                <div class="footer-subtitle">
                    {{ $rfq->quoted_by_supplier ?: 'Name & Signature of Supplier' }}
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
