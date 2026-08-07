<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report of Supplies and Materials Issued</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 11pt;
            font-weight: bold;
            margin: 0 0 8px 0;
        }

        .header-info {
            margin: 5px 0;
            font-size: 9pt;
        }

        .header-info span {
            margin: 0 15px;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .main-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 6px 5px;
            text-align: center;
            font-weight: bold;
            font-size: 8.5pt;
        }

        .main-table td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 8.5pt;
        }

        .main-table .text-center {
            text-align: center;
        }

        .main-table .text-right {
            text-align: right;
        }

        .total-row td {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .recap-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .recap-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 10px 0 5px 0;
            text-transform: uppercase;
        }

        .signatories {
            margin-top: 40px;
            width: 100%;
        }

        .signatories td {
            width: 50%;
            text-align: center;
            padding: 10px 20px;
            vertical-align: top;
            border: none;
        }

        .signatory-name {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 40px;
            display: inline-block;
            min-width: 200px;
        }

        .signatory-title {
            font-size: 8.5pt;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <h5 style="text-align: right; margin: 0; font-style: italic;">Appendix 64</h5>
        <h1>Report of Supplies and Materials Issued</h1>
        <div class="header-info">
            <span><strong>Entity Name:</strong> COMMISSION ON HUMAN RIGHTS XII</span>
            <span><strong>Fund Cluster:</strong> Regular Agency Fund</span>
        </div>
        @if($dateFrom || $dateTo)
        <div class="header-info">
            @if($dateFrom && $dateTo)
                <span><strong>Period:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('F d, Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('F d, Y') }}</span>
            @elseif($dateFrom)
                <span><strong>From:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('F d, Y') }}</span>
            @elseif($dateTo)
                <span><strong>To:</strong> {{ \Carbon\Carbon::parse($dateTo)->format('F d, Y') }}</span>
            @endif
        </div>
        @endif
        <div class="header-info">
            <span><strong>Generated:</strong> {{ now()->format('F d, Y h:i A') }}</span>
        </div>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th colspan="8" style="text-align:center; font-size:12pt;">
                    REPORT OF SUPPLIES AND MATERIALS ISSUED
                </th>
            </tr>
            <tr>
                <th colspan="6" style="text-align:left;">
                    To be filled up by the Supply and/or Property Division/Unit
                </th>
                <th colspan="2" style="text-align:left;">
                    To be filled up by the Accounting Division/Unit
                </th>
            </tr>
            <tr>
                <th style="width: 12%;">RIS No.</th>
                <th style="width: 10%;">Responsibility Center Code</th>
                <th style="width: 12%;">Stock No.</th>
                <th style="width: 25%;">Item Description</th>
                <th style="width: 8%;">Unit</th>
                <th style="width: 8%;">Qty</th>
                <th style="width: 12%;">Unit Cost</th>
                <th style="width: 13%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp

            @forelse($items as $transaction)
                @php
                    $unitCost = (float) ($transaction->price ?? $transaction->supply?->unit_price ?? 0);
                    $amount = $transaction->quantity_issued * $unitCost;
                    $grandTotal += $amount;
                @endphp
                <tr>
                    <td class="text-center">{{ $transaction->requisition?->ris_no ?? '-' }}</td>
                    <td class="text-center">{{ $transaction->requisition?->responsibility_center_code ?? '-' }}</td>
                    <td class="text-center">{{ $transaction->supply?->stock_no ?? $transaction->stock_no ?? '-' }}</td>
                    <td class="text-center">{{ $transaction->supply?->name ?? $transaction->description ?? 'N/A' }}</td>
                    <td class="text-center">{{ $transaction->supply?->unit ?? $transaction->unit ?? '-' }}</td>
                    <td class="text-center">{{ number_format((int) $transaction->quantity_issued) }}</td>
                    <td class="text-right">{{ number_format($unitCost, 2) }}</td>
                    <td class="text-right">{{ number_format($amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">No records found.</td>
                </tr>
            @endforelse

            <tr class="total-row">
                <td colspan="5"><strong>Grand Total</strong></td>
                <td class="text-center"><strong>{{ number_format($items->sum('quantity_issued')) }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($grandTotal, 2) }}</strong></td>
            </tr>

            <tr>
                <td colspan="8" style="height:15px;"></td>
            </tr>

            <tr>
                <td colspan="8" style="font-weight:bold; text-align: center">Recapitulation</td>
            </tr>
            <tr>
                <th colspan="2">Stock No.</th>
                <th colspan="2">Quantity</th>
                <th colspan="2">Unit Cost</th>
                <th colspan="2">Total Cost</th>
            </tr>

            @php
                $totalQty = 0;
                $totalCost = 0;
            @endphp

            @foreach($recapitulation as $item)
                @php
                    $cost = $item['unit_cost'] * $item['quantity'];
                    $totalQty += $item['quantity'];
                    $totalCost += $cost;
                @endphp
                <tr>
                    <td colspan="2" class="text-center">{{ $item['stock_number'] }}</td>
                    <td colspan="2" class="text-center">{{ number_format($item['quantity']) }}</td>
                    <td colspan="2" class="text-right">{{ number_format($item['unit_cost'], 2) }}</td>
                    <td colspan="2" class="text-right">{{ number_format($cost, 2) }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="2"><strong>Total</strong></td>
                <td colspan="2" class="text-center"><strong>{{ number_format($totalQty) }}</strong></td>
                <td colspan="2"></td>
                <td colspan="2" class="text-right"><strong>{{ number_format($totalCost, 2) }}</strong></td>
            </tr>
            <tr class="total-row">
                <td colspan="6" style="text-align: right;"><strong>Overall Total Cost</strong></td>
                <td colspan="2" class="text-right"><strong>{{ number_format($totalCost, 2) }}</strong></td>
            </tr>

            <tr>
                <td colspan="8" style="height:30px;"></td>
            </tr>

            <tr>
                <td colspan="4" class="text-center">
                    <div style="margin-top:40px;">
                        <div style="border-top:1px solid #000; display:inline-block; min-width:200px;">
                            {{ $issuedBy ?? '________________________' }}
                        </div>
                        <div>Supply Officer / Property Custodian</div>
                    </div>
                </td>
                <td colspan="4" class="text-center">
                    <div style="margin-top:40px;">
                        <div style="border-top:1px solid #000; display:inline-block; min-width:200px;">
                           Name: ________________________
                        </div>
                        <div>Designated Accounting Staff</div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
