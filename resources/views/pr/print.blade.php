<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Request</title>
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

        /* ─── INFO TABLE: Office/Div | PR No. / Date / RC Code ─── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }
        .info-table td {
            padding: 3px 6px;
            border: 1px solid #000;
            font-size: 9pt;
            vertical-align: top;
        }
        .info-table .label-cell {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .info-table .left-col {
            width: 50%;
        }
        .info-table .right-col {
            width: 50%;
        }
        .info-table .rc-row td {
            border-top: none;
        }

        /* ─── ITEMS TABLE ─── */
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
        .items-table .text-center {
            text-align: center;
        }
        .items-table .text-right {
            text-align: right;
        }

        /* ─── DETAILS SECTION ─── */
        .details-section {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }
        .details-section td {
            padding: 3px 6px;
            border: 1px solid #000;
            font-size: 9pt;
            vertical-align: top;
        }
        .details-section .label-cell {
            font-weight: bold;
            width: 24%;
            background-color: #f0f0f0;
        }

        /* ─── SIGNATORIES ─── */
        .signatories {
            margin-top: 30px;
            width: 100%;
            border-collapse: collapse;
        }
        .signatories td {
            width: 50%;
            text-align: center;
            padding: 5px 20px;
            vertical-align: top;
        }
        .signatory-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 45px;
            display: inline-block;
            min-width: 200px;
        }
        .signatory-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #333;
            margin-top: 2px;
        }
        .signatory-label {
            font-weight: bold;
            font-size: 9pt;
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
                <h1>Purchase Request</h1>
            </td>

            {{-- Document Control --}}
            <td style="width:45%;" class="doc-control-area">
                <span class="label">Document Control No.:</span> CHR-ROXII-ASD-FR-008<br>
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

    {{-- ═══ INFO TABLE ═══ --}}
    <table class="info-table" style="width:100%; border-collapse:collapse;">
    <tr>
        <!-- Left Column -->
        <td style="width:50%; padding:8px; border:1px solid #000; vertical-align:top;">
            <div style="margin-bottom:8px;">
                <strong>Office / Division:</strong>
                {{ $purchaseRequest->office_division ?? '________________________' }}
            </div>

            <div>
                <strong>RC Code:</strong>
                {{ $purchaseRequest->rc_code ?? '________________________' }}
            </div>
        </td>

        <!-- Right Column -->
        <td style="width:50%; padding:8px; border:1px solid #000; vertical-align:top;">
            <div style="margin-bottom:8px;">
                <strong>PR No.:</strong>
                {{ $purchaseRequest->pr_no ?? '________________________' }}
            </div>

            <div>
                <strong>Date:</strong>
                {{ $purchaseRequest->date
                    ? \Carbon\Carbon::parse($purchaseRequest->date)->format('m/d/Y')
                    : '________________________' }}
            </div>
        </td>
    </tr>
</table>

    {{-- ═══ ITEMS TABLE ═══ --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:16%;">Stock/Property No.</th>
                <th style="width:10%;">Unit</th>
                <th style="width:44%;">Item Description</th>
                <th style="width:12%;">Quantity</th>
                <th style="width:18%;">Unit Cost</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseRequest->items as $item)
                <tr>
                    <td class="text-center">{{ $item->stock_property_no ?? '-' }}</td>
                    <td class="text-center">{{ $item->unit ?? '-' }}</td>
                    <td>{{ $item->item_description ?? '-' }}</td>
                    <td class="text-center">{{ number_format((int) $item->quantity) }}</td>
                    <td class="text-right">{{ $item->unit_cost ? number_format($item->unit_cost, 2) : '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding:20px;">No items listed.</td>
                </tr>
            @endforelse

            {{-- Fill remaining rows for empty lines --}}
            @for($i = $purchaseRequest->items->count(); $i < 12; $i++)
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endfor
        </tbody>
    </table>

    {{-- ═══ PROJECT DETAILS ═══ --}}
    <table class="details-section">
        <tr>
            <td class="label-cell">Code :</td>
            <td>{{ $purchaseRequest->code ?? '________________________' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Name of Project :</td>
            <td>{{ $purchaseRequest->name_of_project ?? '________________________' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Purpose :</td>
            <td>{{ $purchaseRequest->purpose ?? '________________________' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Source of Fund :</td>
            <td>{{ $purchaseRequest->source_of_fund ?? '________________________' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Approved Budget Allocation/APP :</td>
            <td>{{ $purchaseRequest->approved_budget ? '₱ ' . number_format($purchaseRequest->approved_budget, 2) : '________________________' }}</td>
        </tr>
    </table>

    {{-- ═══ SIGNATORIES ═══ --}}
    <table class="signatories">
        <tr>
            <td>
                <div class="signatory-label">Requested by:</div>
                <div class="signatory-line">
                    {{ $purchaseRequest->requested_by_name ?: '________________________' }}
                </div>
                <div class="signatory-title">
                    {{ $purchaseRequest->requested_by_designation ?: 'Special Investigator III' }}
                </div>
            </td>
            <td>
                <div class="signatory-label">Approved by:</div>
                <div class="signatory-line">
                    {{ $purchaseRequest->approved_by_name ?: '________________________' }}
                </div>
                <div class="signatory-title">
                    {{ $purchaseRequest->approved_by_designation ?: 'Regional Director' }}
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
