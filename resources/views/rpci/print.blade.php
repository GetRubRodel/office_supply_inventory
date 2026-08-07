<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPCI - {{ $rpci->report_no }}</title>
    <style>
        @page {
            size: Legal landscape;
            margin: 12mm 15mm 15mm 15mm;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 8.5pt;
            line-height: 1.15;
            color: #000;
        }
        .container {
            width: 100%;
            max-width: 330mm;
            margin: 0 auto;
        }

        /* ─── HEADER ─────────────────────────────────────── */
        .header {
            text-align: center;
            margin-bottom: 6px;
        }
        .header .appendix {
            font-size: 8pt;
            font-weight: bold;
        }
        .header .title {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 3px 0;
            text-transform: uppercase;
        }
        .header .sub-title {
            font-size: 8pt;
            font-style: italic;
        }
        .header .as-of {
            font-size: 9pt;
            font-weight: bold;
            margin-top: 2px;
        }

        /* ─── BORDERED BOX (Info Section) ──────────────── */
        .bordered-box {
            border: 1px solid #000;
        }
        .bordered-row {
            display: flex;
            border-bottom: 1px solid #000;
        }
        .bordered-row:last-child {
            border-bottom: none;
        }
        .bordered-cell {
            padding: 2px 5px;
            border-right: 1px solid #000;
            min-height: 24px;
        }
        .bordered-cell:last-child {
            border-right: none;
        }
        .bordered-cell .field-label {
            font-size: 7.5pt;
            font-weight: bold;
        }
        .bordered-cell .field-value {
            border-bottom: 1px solid #000;
            min-height: 18px;
            padding: 1px 3px;
            margin-top: 1px;
            font-size: 9pt;
            font-weight: bold;
        }
        .bordered-cell .field-value-plain {
            font-size: 9pt;
            font-weight: bold;
            padding: 1px 3px;
            min-height: 18px;
        }

        /* ─── ITEMS TABLE ─────────────────────────────────── */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 7.8pt;
            border: 1px solid #000;
        }
        table.items th, table.items td {
            border: 1px solid #000;
            padding: 2px 2px;
            text-align: center;
            vertical-align: middle;
        }
        table.items thead th {
            font-weight: bold;
            font-size: 7.5pt;
            background: #f0f0f0;
        }
        table.items td.left {
            text-align: left;
            padding-left: 3px;
        }
        table.items td.right {
            text-align: right;
            padding-right: 3px;
        }

        /* Column widths: 10 columns */
        .col-art   { width: 8%; }
        .col-desc  { width: 16%; }
        .col-stock { width: 8%; }
        .col-uom   { width: 6%; }
        .col-uv    { width: 8%; }
        .col-bpc   { width: 8%; }
        .col-ohpc  { width: 8%; }
        .col-soq   { width: 8%; }
        .col-sov   { width: 9%; }
        .col-rem   { width: 12%; }

        /* ─── CERTIFICATION (3-column layout) ──────────── */
        .cert-section {
            border: 1px solid #000;
            border-top: none;
        }
        .cert-title-row {
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            padding: 3px;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
        }
        .cert-grid {
            display: flex;
            flex-wrap: wrap;
        }
        .cert-box {
            flex: 1;
            min-width: 33.33%;
            padding: 6px 8px;
            border-right: 1px solid #000;
            min-height: 95px;
        }
        .cert-box:last-child {
            border-right: none;
        }
        .cert-box .cert-role {
            font-weight: bold;
            font-size: 7.5pt;
            margin-bottom: 3px;
            text-align: center;
        }
        .cert-box .sig-line {
            border-bottom: 1px solid #000;
            height: 28px;
            margin: 2px auto;
            width: 85%;
        }
        .cert-box .cert-name {
            border-bottom: 1px solid #000;
            min-height: 16px;
            padding: 0 3px;
            font-size: 8pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1px;
        }
        .cert-box .cert-label {
            font-size: 7pt;
            text-align: center;
        }

        /* ─── FOOTER ──────────────────────────────────────── */
        .footer-section {
            border: 1px solid #000;
            border-top: none;
            padding: 3px 6px;
            font-size: 7pt;
            text-align: center;
        }

        .no-print { display: block; }

        @media print {
            .no-print { display: none; }
            body { font-size: 8pt; }
            table.items { font-size: 7.5pt; }
            @page {
                size: Legal landscape;
                margin: 12mm 15mm 15mm 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- ======== HEADER ======== --}}
        <div class="header">
            <div class="appendix">Appendix 66</div>
            <div class="title">REPORT ON THE PHYSICAL COUNT OF INVENTORIES</div>
            <div class="sub-title">({{ $rpci->inventory_type ?? 'Common-Office Supplies' }})</div>
            <div class="as-of">{{ $rpci->report_date ? $rpci->report_date->format('F j, Y') : '' }}</div>
        </div>

        {{-- ======== HEADER INFO BOX ======== --}}
        <div class="bordered-box">
            {{-- Row 1: Fund Cluster --}}
            <div class="bordered-row">
                <div class="bordered-cell" style="flex: 1;">
                    <div class="field-label">Fund Cluster :</div>
                    <div class="field-value">{{ $rpci->fund_cluster ?? '___________________________' }}</div>
                </div>
            </div>

            {{-- Row 2: For which [person] [position] [office] is accountable --}}
            <div class="bordered-row">
                <div class="bordered-cell" style="flex: 1;">
                    <div style="font-size: 7.5pt; font-weight: bold; margin-bottom: 2px;">For which</div>
                    <div class="field-value">{{ $rpci->accountable_person ?? '___________________________' }}</div>
                    <div style="font-size: 7.5pt; font-weight: bold; margin-top: 2px; margin-bottom: 2px;"></div>
                    <div class="field-value">{{ $rpci->accountable_position ?? '___________________________' }}</div>
                    <div style="font-size: 7.5pt; margin-top: 1px;">CHR Region XII</div>
                </div>
            </div>

            {{-- Row 3: Accountability date --}}
            <div class="bordered-row">
                <div class="bordered-cell" style="flex: 1;">
                    <div class="field-label">is accountable, having assumed such accountability on</div>
                    <div class="field-value">{{ $rpci->accountability_date ? $rpci->accountability_date->format('F j, Y') : '___________________________' }}</div>
                </div>
            </div>
        </div>

        {{-- ======== ITEMS TABLE ======== --}}
        <table class="items">
            <thead>
                <tr>
                    <th class="col-art">Article</th>
                    <th class="col-desc">Description</th>
                    <th class="col-stock">Stock Number</th>
                    <th class="col-uom">Unit of Measure</th>
                    <th class="col-uv">Unit Value (₱)</th>
                    <th class="col-bpc">Balance Per Card (Qty)</th>
                    <th class="col-ohpc">On Hand Per Count (Qty)</th>
                    <th class="col-soq">Shortage / Overage (Qty)</th>
                    <th class="col-sov">Shortage / Overage (Value ₱)</th>
                    <th class="col-rem">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @php $items = $rpci->items; @endphp
                @forelse($items as $item)
                @php
                    // Always read fetched fields from the live Supply record when available
                    $supply       = $item->supply;
                    $article      = $supply?->category->name ?? $item->article;
                    $description  = $supply?->name ?? $item->description;
                    $stockNumber  = $supply?->stock_no ?? $item->stock_number;
                    $unitOfMeasure = $supply?->unit ?? $item->unit_of_measure;
                    $unitValue    = ($supply && $supply->unit_cost > 0)
                        ? $supply->unit_cost
                        : ($item->unit_value ?? 0);
                @endphp
                <tr>
                    <td class="left">{{ $article }}</td>
                    <td class="left">{{ $description }}</td>
                    <td>{{ $stockNumber }}</td>
                    <td>{{ $unitOfMeasure }}</td>
                    <td class="right">{{ number_format($unitValue, 2) }}</td>
                    <td>{{ number_format($item->balance_per_card) }}</td>
                    <td>{{ number_format($item->on_hand_per_count) }}</td>
                    <td>
                        @php $sq = (int) $item->shortage_quantity; @endphp
                        @if($sq > 0)
                            ({{ number_format($sq) }})
                        @else
                            {{ number_format($sq) }}
                        @endif
                    </td>
                    <td class="right">{{ number_format($item->shortage_value, 2) }}</td>
                    <td class="left">{{ $item->remarks }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="padding: 10px; text-align: center; font-style: italic;">
                        No inventory items recorded.
                    </td>
                </tr>
                @endforelse

                {{-- Fill remaining rows to maintain minimum table height --}}
                @php $rowCount = $items->count(); @endphp
                @for($i = $rowCount; $i < max(8, $rowCount + 2); $i++)
                <tr>
                    <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                    <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                    <td>&nbsp;</td><td>&nbsp;</td>
                </tr>
                @endfor
            </tbody>
        </table>

        {{-- ======== TOTALS ROW ======== --}}
        @php
            $totalBpc = $items->sum('balance_per_card');
            $totalOhpc = $items->sum('on_hand_per_count');
            $totalSoQty = $items->sum('shortage_quantity');
            $totalSoVal = $items->sum('shortage_value');
        @endphp
        <table class="items" style="margin-top: -1px;">
            <tfoot>
                <tr style="font-weight: bold; background: #f0f0f0;">
                    <td colspan="5" style="text-align: right; padding-right: 5px;">TOTALS</td>
                    <td>{{ number_format($totalBpc) }}</td>
                    <td>{{ number_format($totalOhpc) }}</td>
                    <td>{{ number_format($totalSoQty) }}</td>
                    <td class="right">{{ number_format($totalSoVal, 2) }}</td>
                    <td>&nbsp;</td>
                </tr>
            </tfoot>
        </table>

        {{-- ======== CERTIFICATION ======== --}}
        <div class="cert-section">
            <div class="cert-title-row">CERTIFICATION</div>
            <div style="font-size: 7.5pt; text-align: justify; padding: 3px 5px; border-bottom: 1px solid #000;">
                WE HEREBY CERTIFY that the items listed above have been physically counted and verified in our presence,
                and that the quantities shown under "On Hand Per Count" reflect the actual count conducted on
                <strong>{{ $rpci->report_date ? $rpci->report_date->format('F j, Y') : '________________' }}</strong>.
            </div>
        </div>

        {{-- Signatories Grid: 3-column layout --}}
        <div class="cert-section">
            <div class="cert-grid">
                {{-- LEFT: Certified Correct by (Chairman + Members) --}}
                <div class="cert-box">
                    <div style="font-size: 7.5pt; font-weight: bold; text-align: center; margin-bottom: 4px;">Certified Correct by:</div>
                    <div class="cert-role">INVENTORY COMMITTEE CHAIRMAN</div>
                    <div class="sig-line"></div>
                    <div class="cert-name">{{ $rpci->chairman_name ?? '____________________________' }}</div>
                    <div class="cert-label">Printed Name and Signature</div>
                    <div style="margin-top: 6px;"></div>
                    <div class="cert-role">INVENTORY COMMITTEE MEMBER</div>
                    <div class="sig-line"></div>
                    <div class="cert-name">{{ $rpci->member_one_name ?? '____________________________' }}</div>
                    <div class="cert-label">Printed Name and Signature</div>
                    <div style="margin-top: 6px;"></div>
                    <div class="cert-role">INVENTORY COMMITTEE MEMBER</div>
                    <div class="sig-line"></div>
                    <div class="cert-name">{{ $rpci->member_two_name ?? '____________________________' }}</div>
                    <div class="cert-label">Printed Name and Signature</div>
                </div>

                {{-- CENTER: Approved by (Head of Agency) --}}
                <div class="cert-box">
                    <div style="font-size: 7.5pt; font-weight: bold; text-align: center; margin-bottom: 4px;">Approved by:</div>
                    <div class="sig-line" style="margin-top: 8px;"></div>
                    <div class="cert-name">{{ $rpci->approved_by ?? '____________________________' }}</div>
                    <div class="cert-label">Printed Name and Signature</div>
                    <div class="cert-name" style="margin-top: 4px;">{{ $rpci->approved_position ?? '____________________________' }}</div>
                    <div class="cert-label">Head of Agency / Authorized Representative</div>
                </div>

                {{-- RIGHT: Verified by (COA Representative) --}}
                <div class="cert-box">
                    <div style="font-size: 7.5pt; font-weight: bold; text-align: center; margin-bottom: 4px;">Verified by:</div>
                    <div class="sig-line" style="margin-top: 8px;"></div>
                    <div class="cert-name">{{ $rpci->verified_by ?? '____________________________' }}</div>
                    <div class="cert-label">Printed Name and Signature</div>
                    <div class="cert-name" style="margin-top: 4px;">{{ $rpci->verified_position ?? '____________________________' }}</div>
                    <div class="cert-label">COA Representative</div>
                </div>
            </div>
        </div>

        {{-- ======== FOOTER ======== --}}
        <div class="footer-section">
            <div>CHR-ROXII-ASD-FR-009 | Page 1 of 1 | Revision 0 | Effectivity Date: {{ now()->format('F j, Y') }}</div>
        </div>

        {{-- ======== PRINT BUTTONS ======== --}}
        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; margin-right: 10px; background: #2563eb; color: #fff; border: none; border-radius: 4px;">
                🖨️ Print This Form
            </button>
            <button onclick="window.close()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; background: #6b7280; color: #fff; border: none; border-radius: 4px;">
                Close
            </button>
        </div>
    </div>
</body>
</html>
