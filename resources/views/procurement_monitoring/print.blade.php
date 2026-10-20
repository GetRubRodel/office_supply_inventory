<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Procurement Monitoring Report</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 10pt;
            font-weight: normal;
            margin: 0 0 6px 0;
        }

        .header-info {
            margin: 5px 0;
            font-size: 8.5pt;
        }

        .header-info span {
            margin: 0 12px;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .main-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
        }

        .main-table td {
            border: 1px solid #000;
            padding: 5px 4px;
            font-size: 8pt;
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

        .signature-block {
            margin-top: 40px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-block .signatory {
            text-align: center;
            width: 240px;
        }

        .signature-block .signatory .name {
            font-weight: bold;
            text-transform: uppercase;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 6px 14px; font-size: 10pt; cursor: pointer;">Print / Save as PDF</button>
    </div>

    <div class="header">
        <h1>Republic of the Philippines</h1>
        <h1>Commission on Human Rights Region XII</h1>
        <h2>Procurement Monitoring Report</h2>
        <div class="header-info">
            @if (($filters['date_from'] ?? null) || ($filters['date_to'] ?? null))
                <span>
                    Date Requested:
                    {{ $filters['date_from'] ?? 'Start' }}
                    to
                    {{ $filters['date_to'] ?? 'Present' }}
                </span>
            @endif
            @if ($filters['office_division'] ?? null)
                <span>Office / Division: {{ $filters['office_division'] }}</span>
            @endif
            <span>Generated on {{ \App\Support\ReportClock::format() }} (PST)</span>
        </div>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 9%;">Date Requested</th>
                <th style="width: 8%;">Control No.</th>
                <th style="width: 8%;">P.O. Date</th>
                <th style="width: 8%;">IAR Date</th>
                <th style="width: 24%;">Particulars</th>
                <th style="width: 15%;">Responsibility Center</th>
                <th style="width: 14%;">Source of Fund</th>
                <th style="width: 14%;">Gross Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="text-center">{{ $row['date_requested']?->format('m/d/Y') ?? '—' }}</td>
                    <td class="text-center">{{ $row['control_number'] }}</td>
                    <td class="text-center">{{ $row['po_date']?->format('m/d/Y') ?? '—' }}</td>
                    <td class="text-center">{{ $row['iar_date']?->format('m/d/Y') ?? '—' }}</td>
                    <td>{{ $row['particulars'] ?: '—' }}</td>
                    <td>{{ $row['responsibility_center'] ?: '—' }}</td>
                    <td>{{ $row['source_of_fund'] ?: '—' }}</td>
                    <td class="text-right">{{ number_format((float) $row['gross_amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">
                        No procurement records found for the selected filters.
                    </td>
                </tr>
            @endforelse

            <tr class="total-row">
                <td colspan="7" class="text-right">GRAND TOTAL</td>
                <td class="text-right">{{ number_format((float) $rows->sum('gross_amount'), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="signature-block">
        <div class="signatory">
            <div>Prepared by:</div>
            <br>
            <div class="name">{{ auth()->user()?->name ?? '' }}</div>
            <div>{{ auth()->user()?->role ? strtoupper(str_replace('_', ' ', auth()->user()->role)) : '' }}</div>
        </div>
    </div>
</body>
</html>
