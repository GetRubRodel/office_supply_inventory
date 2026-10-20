<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report of Supplies and Materials Issued</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
            width: 210mm;
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
    {{--
        This view is the RSMI reference output. The Download action renders the
        same App\Services\RsmiReportService payload through PhpWord, so the two
        must stay structurally identical: same labels (shared constants), same
        row order, same totals, same timestamps (shared ReportClock).
    --}}
    @php
        $svc = app(App\Services\RsmiReportService::class);
    @endphp

    <div class="header">
        <h5 style="text-align: right; margin: 0; font-style: italic;">{{ App\Services\RsmiReportService::APPENDIX }}</h5>
        <h1>{{ App\Services\RsmiReportService::TITLE }}</h1>
        <div class="header-info">
            <span><strong>Entity Name:</strong> {{ App\Services\RsmiReportService::ENTITY_NAME }}</span>
            <span><strong>Fund Cluster:</strong> {{ App\Services\RsmiReportService::FUND_CLUSTER }}</span>
        </div>
        @if($report['period_label'])
        <div class="header-info">
            <span><strong>{{ $report['period_label'] }}</strong> {{ $report['period'] }}</span>
        </div>
        @endif
        <div class="header-info">
            <span><strong>Generated:</strong> {{ $report['generated_at'] }} (PST)</span>
        </div>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th colspan="8" style="text-align:center; font-size:12pt;">
                    {{ App\Services\RsmiReportService::TITLE }}
                </th>
            </tr>
            <tr>
                <th colspan="6" style="text-align:left;">
                    {{ App\Services\RsmiReportService::SUPPLY_NOTE }}
                </th>
                <th colspan="2" style="text-align:left;">
                    {{ App\Services\RsmiReportService::ACCOUNTING_NOTE }}
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
            @forelse($report['items'] as $row)
                <tr>
                    <td class="text-center">{{ $row['ris_no'] }}</td>
                    <td class="text-center">{{ $row['responsibility_center_code'] }}</td>
                    <td class="text-center">{{ $row['stock_no'] }}</td>
                    <td class="text-center">{{ $row['item_description'] }}</td>
                    <td class="text-center">{{ $row['unit'] }}</td>
                    <td class="text-center">{{ $row['quantity'] }}</td>
                    <td class="text-right">{{ $row['unit_cost'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">No records found.</td>
                </tr>
            @endforelse

            <tr class="total-row">
                <td colspan="5"><strong>Grand Total</strong></td>
                <td class="text-center"><strong>{{ $report['grand_total_qty'] }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ $report['grand_total_amount'] }}</strong></td>
            </tr>

            <tr>
                <td colspan="8" style="height:15px;"></td>
            </tr>

            <tr>
                <td colspan="8" style="font-weight:bold; text-align: center">Recapitulation</td>
            </tr>
            <tr>
                <th colspan="2">{{ App\Services\RsmiReportService::RECAP_HEADERS[0] }}</th>
                <th colspan="2">{{ App\Services\RsmiReportService::RECAP_HEADERS[1] }}</th>
                <th colspan="2">{{ App\Services\RsmiReportService::RECAP_HEADERS[2] }}</th>
                <th colspan="2">{{ App\Services\RsmiReportService::RECAP_HEADERS[3] }}</th>
            </tr>

            @foreach($report['recapitulation'] as $item)
                <tr>
                    <td colspan="2" class="text-center">{{ $item['stock_number'] }}</td>
                    <td colspan="2" class="text-center">{{ number_format($item['quantity']) }}</td>
                    <td colspan="2" class="text-right">{{ $item['unit_cost'] }}</td>
                    <td colspan="2" class="text-right">{{ $item['total_cost'] }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="2"><strong>Total</strong></td>
                <td colspan="2" class="text-center"><strong>{{ $report['recap_total_qty'] }}</strong></td>
                <td colspan="2"></td>
                <td colspan="2" class="text-right"><strong>{{ $report['recap_total_cost'] }}</strong></td>
            </tr>
            <tr class="total-row">
                <td colspan="6" style="text-align: right;"><strong>Overall Total Cost</strong></td>
                <td colspan="2" class="text-right"><strong>{{ $report['recap_total_cost'] }}</strong></td>
            </tr>

            <tr>
                <td colspan="8" style="height:30px;"></td>
            </tr>

            <tr>
                <td colspan="4" class="text-center">
                    <div style="text-align: left;">{{ App\Services\RsmiReportService::CERTIFICATION }}</div>
                    <div style="margin-top:40px;">
                        <div style="border-top:1px solid #000; display:inline-block; min-width:200px;">
                            {{ $report['issued_by'] ?? '________________________' }}
                        </div>
                        <div>{{ App\Services\RsmiReportService::CUSTODIAN_LABEL }}</div>
                    </div>
                </td>
                <td colspan="4" class="text-center">
                    <div style="margin-top:40px;">
                        <div style="border-top:1px solid #000; display:inline-block; min-width:200px;">
                           Ana Fe B. Galanto
                        </div>
                        <div>{{ App\Services\RsmiReportService::ACCOUNTING_STAFF_LABEL }}</div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
