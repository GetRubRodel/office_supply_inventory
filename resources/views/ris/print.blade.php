<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RIS - {{ $requisition->ris_no }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 15mm 15mm 15mm;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.2;
            color: #000;
        }
        .container {
            width: 190mm;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 4px;
        }
        .header .appendix {
            font-size: 10pt;
            font-weight: bold;
            text-align: right;
        }
        .header .title {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 2px 0;
        }
        .header .sub-title {
            font-size: 9pt;
            font-style: italic;
        }

        /* Top rows use border-collapse style layout */
        .bordered-box {
            border: 1px solid #000;
        }
        .bordered-box .bordered-row {
            display: flex;
            border-bottom: 1px solid #000;
        }
        .bordered-box .bordered-row:last-child {
            border-bottom: none;
        }
        .bordered-cell {
            padding: 3px 6px;
            border-right: 1px solid #000;
            min-height: 28px;
        }
        .bordered-cell:last-child {
            border-right: none;
        }
        .label {
            font-size: 9pt;
            font-weight: bold;
        }
        .value-line {
            border-bottom: 1px solid #000;
            min-height: 20px;
            padding: 1px 4px;
            margin-top: 1px;
            font-size: 11pt;
        }

        /* Items Table */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            font-size: 9.5pt;
            border: 1px solid #000;
        }
        table.items th, table.items td {
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
        }
        table.items th {
            font-weight: bold;
            font-size: 9pt;
            background: #f0f0f0;
        }
        table.items td.left {
            text-align: left;
        }

        .col-sn { width: 5%; }
        .col-unit { width: 8%; }
        .col-desc { width: 22%; }
        .col-qty { width: 8%; }
        .col-yes { width: 5%; }
        .col-no { width: 5%; }
        .col-issue { width: 8%; }
        .col-remarks { width: 10%; }

        /* Purpose */
        .purpose-section {
            border: 1px solid #000;
            border-top: none;
            padding: 4px 6px;
        }
        .purpose-section .value-line {
            border: none;
            min-height: 36px;
            font-style: italic;
        }

        /* Signatories */
        .signatories {
            display: flex;
            border: 1px solid #000;
            border-top: none;
        }
        .sign-box {
            flex: 1;
            padding: 6px 4px;
            text-align: center;
            border-right: 1px solid #000;
            min-height: 130px;
        }
        .sign-box:last-child {
            border-right: none;
        }
        .sign-box .role {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 2px;
        }
        .sign-box .sig-line {
            border-bottom: 1px solid #000;
            height: 36px;
            margin: 2px auto;
            width: 90%;
        }
        .sign-box .label-sm {
            font-size: 8pt;
            text-align: left;
        }
        .sign-box .sign-value {
            border-bottom: 1px solid #000;
            min-height: 18px;
            padding: 0 2px;
            font-size: 9pt;
            text-align: left;
            margin-bottom: 2px;
        }

        .no-print { display: block; }
        @media print {
            .no-print { display: none; }
            body { font-size: 10pt; }
            .container { width: 100%; }
            table.items { font-size: 9pt; }
            @page { margin: 10mm 15mm 15mm 15mm; }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="appendix">Appendix 63</div>
            <div class="title">REQUISITION AND ISSUE SLIP</div>
        </div>

        {{-- Row 1: Entity Name & Fund Cluster --}}
        <div class="bordered-box">
            <div class="bordered-row">
                <div class="bordered-cell" style="flex: 7;">
                    <div class="label">Entity Name :</div>
                    <div class="value-line">{{ $requisition->entity_name ?? '_____________________________' }}</div>
                </div>
                <div class="bordered-cell" style="flex: 3;">
                    <div class="label">Fund Cluster :</div>
                    <div class="value-line">{{ $requisition->fund_cluster ?? '___________' }}</div>
                </div>
            </div>

            {{-- Row 2: Division & Responsibility Center --}}
            <div class="bordered-row">
                <div class="bordered-cell" style="flex: 7;">
                    <div class="label">Division :</div>
                    <div class="value-line">{{ $requisition->division ?? '_______________________________________________' }}</div>
                </div>
                <div class="bordered-cell" style="flex: 3;">
                    <div class="label">Responsibility Center :</div>
                    <div class="value-line">{{ $requisition->responsibility_center_code ?? '___________' }}</div>
                </div>
            </div>

            {{-- Row 3: Office & RIS No. --}}
            <div class="bordered-row">
                <div class="bordered-cell" style="flex: 7;">
                    <div class="label">Office :</div>
                    <div class="value-line">{{ $requisition->office ?? '________________________________________________' }}</div>
                </div>
                <div class="bordered-cell" style="flex: 3;">
                    <div class="label">RIS No. :</div>
                    <div class="value-line">{{ $requisition->ris_no ?? '_________________' }}</div>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="items">
            <thead>
                <tr>
                    <th colspan="2">Requisition</th>
                    <th rowspan="2" class="col-remarks">Item Description</th>
                    <th colspan="3">Stock Available?</th>
                    <th colspan="2">Issue</th>
                </tr>
                <tr>
                    <th class="col-sn">Stock No.</th>
                    <th class="col-unit">Unit</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-yes">No</th>
                    <th class="col-yes">Yes</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-issue">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @php $items = $requisition->items; @endphp
                @forelse($items as $item)
                <tr>
                    <td>{{ $item->stock_no }}</td>
                    <td>{{ $item->unit }}</td>
                    <td class="left">{{ $item->description ?? $item->supply?->name ?? '' }}</td>
                    <td>{{ $item->quantity_requested }}</td>
                    <td>{{ $item->stock_available === false ? '✓' : '' }}</td>
                    <td>{{ $item->stock_available === true ? '✓' : '' }}</td>
                    <td>{{ $item->quantity_issued }}</td>
                    <td class="left">{{ $item->remarks }}</td>
                </tr>
                @empty
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                @endforelse
                @php $rowCount = $items->count(); @endphp
                @for($i = $rowCount; $i < 5; $i++)
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                @endfor
            </tbody>
        </table>

        {{-- Purpose --}}
        <div class="purpose-section">
            <div class="label">Purpose :</div>
            <div class="value-line">{{ $requisition->purpose ?? '_____________________________' }}</div>
        </div>

        {{-- Signatories --}}
        <div class="signatories">
            {{-- Requested by --}}
            <div class="sign-box">
                <div class="role">Requested by:</div>
                <div class="sig-line"></div>
                <div class="label-sm">Signature</div>
                <div class="sign-value">{{ $requisition->requested_by_name ?? '_________________' }}</div>
                <div class="label-sm">Printed Name</div>
                <div class="sign-value">{{ $requisition->requested_by_designation ?? '_________________' }}</div>
                <div class="label-sm">Designation</div>
                <div class="sign-value">{{ $requisition->requested_by_date ? $requisition->requested_by_date->format('m/d/Y') : '_________________' }}</div>
                <div class="label-sm">Date</div>
            </div>
            {{-- Approved by --}}
            <div class="sign-box">
                <div class="role">Approved by:</div>
                <div class="sig-line"></div>
                <div class="label-sm">Signature</div>
                <div class="sign-value">{{ $requisition->approved_by_name ?? '_________________' }}</div>
                <div class="label-sm">Printed Name</div>
                <div class="sign-value">{{ $requisition->approved_by_designation ?? '_________________' }}</div>
                <div class="label-sm">Designation</div>
                <div class="sign-value">{{ $requisition->approved_by_date ? $requisition->approved_by_date->format('m/d/Y') : '_________________' }}</div>
                <div class="label-sm">Date</div>
            </div>
            {{-- Issued by --}}
            <div class="sign-box">
                <div class="role">Issued by:</div>
                <div class="sig-line"></div>
                <div class="label-sm">Signature</div>
                <div class="sign-value">{{ $requisition->issued_by_name ?? '_________________' }}</div>
                <div class="label-sm">Printed Name</div>
                <div class="sign-value">{{ $requisition->issued_by_designation ?? '_________________' }}</div>
                <div class="label-sm">Designation</div>
                <div class="sign-value">{{ $requisition->issued_by_date ? $requisition->issued_by_date->format('m/d/Y') : '_________________' }}</div>
                <div class="label-sm">Date</div>
            </div>
            {{-- Received by --}}
            <div class="sign-box">
                <div class="role">Received by:</div>
                <div class="sig-line"></div>
                <div class="label-sm">Signature</div>
                <div class="sign-value">{{ $requisition->received_by_name ?? '_________________' }}</div>
                <div class="label-sm">Printed Name</div>
                <div class="sign-value">{{ $requisition->received_by_designation ?? '_________________' }}</div>
                <div class="label-sm">Designation</div>
                <div class="sign-value">{{ $requisition->received_by_date ? $requisition->received_by_date->format('m/d/Y') : '_________________' }}</div>
                <div class="label-sm">Date</div>
            </div>
            {{-- Cancelled by --}}
            @if($requisition->cancelled_by_name)
            <div class="sign-box" style="background:#fff5f5;">
                <div class="role" style="color:#991b1b;">Cancelled by:</div>
                <div class="sig-line"></div>
                <div class="label-sm">Signature</div>
                <div class="sign-value">{{ $requisition->cancelled_by_name }}</div>
                <div class="label-sm">Printed Name</div>
                <div class="sign-value">{{ $requisition->cancelled_by_designation ?? '_________________' }}</div>
                <div class="label-sm">Designation</div>
                <div class="sign-value">{{ $requisition->cancelled_by_date ? $requisition->cancelled_by_date->format('m/d/Y') : '_________________' }}</div>
                <div class="label-sm">Date</div>
            </div>
            @endif
        </div>

        {{-- Print Button --}}
        <div class="no-print" style="text-align:center; margin-top:20px;">
            <button onclick="window.print()" style="padding:10px 30px; font-size:14px; cursor:pointer;">Print This Form</button>
            <button onclick="window.close()" style="padding:10px 30px; font-size:14px; cursor:pointer;">Close</button>
        </div>
    </div>
</body>
</html>
