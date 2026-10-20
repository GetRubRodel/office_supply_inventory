<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPCI Report - {{ $rpci->report_no }}</title>
    <style>
    @page {
        size: A4 landscape;
        margin: 12.7mm;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11pt;
        line-height: 1.4;
        color: #000;
    }

    .container {
        width: 100%;
        max-width: none;
        margin: 0 auto;
    }

    .header {
        text-align: center;
        margin-bottom: 8px;
    }

    .header .appendix {
        font-size: 10pt;
        font-weight: bold;
        text-align: right;
    }

    .header .title {
        font-size: 16pt;
        font-weight: bold;
        letter-spacing: 1px;
        margin: 4px 0;
        text-transform: uppercase;
    }

    .header .sub-title {
        font-size: 10pt;
        font-style: italic;
    }

    .header .as-of {
        font-size: 11pt;
        font-weight: bold;
        margin-top: 3px;
    }

    /* BORDERED BOX */
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
        padding: 4px 6px;
        border-right: 1px solid #000;
        min-height: 30px;
    }

    .bordered-cell:last-child {
        border-right: none;
    }

    .bordered-cell .field-label {
        font-size: 10pt;
        font-weight: bold;
    }

    .bordered-cell .field-value {
        border-bottom: 1px solid #000;
        min-height: 20px;
        padding: 2px 3px;
        margin-top: 2px;
        font-size: 11pt;
        font-weight: bold;
    }

    .bordered-cell .field-value-plain {
        font-size: 11pt;
        font-weight: bold;
        padding: 2px 3px;
        min-height: 20px;
    }

    /* ITEMS TABLE */
    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 5px;
        font-size: 10pt;
        border: 1px solid #000;
        /*
         * Column widths are declared once, in the shared
         * rpci/partials/items-colgroup partial, and are shared by the body
         * table and the TOTALS table below.
         *
         * `table-layout: fixed` makes that <colgroup> authoritative: column
         * widths are taken from it and are NOT re-negotiated from cell
         * content. Without it the browser sizes each table independently, so
         * the TOTALS table drifts out of line with the body table (the
         * Balance Per Card total then renders under the wrong column) and
         * long descriptions stretch the Remarks column.
         */
        table-layout: fixed;
    }

    table.items th,
    table.items td {
        border: 1px solid #000;
        padding: 4px 3px;
        text-align: center;
        vertical-align: middle;
        /* Fixed layout must not be allowed to overflow its column. */
        overflow-wrap: break-word;
        word-break: break-word;
    }

    table.items thead th {
        font-weight: bold;
        font-size: 10pt;
        background: #f0f0f0;
    }

    table.items td.left {
        text-align: left;
        padding-left: 4px;
    }

    table.items td.right {
        text-align: right;
        padding-right: 4px;
    }

    /* TOTALS ROW — one <td> per column, mirroring the body table exactly.
       Never merge cells here: a colspan (merged) cell makes the browser
       redistribute width across the spanned columns and pulls the totals out
       of alignment with the headers above. */
    table.items tr.totals-row > td {
        font-weight: bold;
        background: #f0f0f0;
    }

    table.items tr.totals-row > td.totals-label {
        text-align: right;
        padding-right: 5px;
    }

    /* CERTIFICATION */
    .cert-section {
        border: 1px solid #000;
        border-top: none;
    }

    .cert-title-row {
        text-align: center;
        font-weight: bold;
        font-size: 11pt;
        padding: 4px;
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
        padding: 8px 10px;
        border-right: 1px solid #000;
        min-height: 110px;
    }

    .cert-box:last-child {
        border-right: none;
    }

    .cert-box .cert-role {
        font-weight: bold;
        font-size: 10pt;
        margin-bottom: 4px;
        text-align: center;
    }

    .cert-box .sig-line {
        border-bottom: 1px solid #000;
        height: 32px;
        margin: 3px auto;
        width: 85%;
    }

    .cert-box .cert-name {
        border-bottom: 1px solid #000;
        min-height: 18px;
        padding: 0 3px;
        font-size: 10pt;
        font-weight: bold;
        text-align: center;
        margin-bottom: 2px;
    }

    .cert-box .cert-label {
        font-size: 9pt;
        text-align: center;
    }

    /* FOOTER */
    .footer-section {
        border: 1px solid #000;
        border-top: none;
        padding: 4px 7px;
        font-size: 9pt;
        text-align: center;
    }

    .no-print {
        display: block;
    }

    /* PRINT */
    @media print {
        .no-print {
            display: none;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
        }

        table.items {
            font-size: 10pt;
        }

        @page {
            size: A4 landscape;
            margin: 12.7mm;
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
                    <div class="field-label">Fund Cluster : {{ $rpci->fund_cluster ?? '' }}</div>
                </div>
            </div>

            {{-- Row 2: For which [person] [position] [office] is accountable --}}
            <div class="bordered-row">
              <p style="font-size: 8pt; margin: 5px 0; line-height: 1.4;">
                 <strong>
                    For which
                     {{ $rpci->accountable_person ?? '___________________________' }},
                     {{ $rpci->accountable_position ?? '___________________________' }}/
                    Supply Officer Designate, CHR Region XII is accountable, having assumed such accountability on
                     {{ $rpci->accountability_date ? $rpci->accountability_date->format('F j, Y') : '___________________________' }}.
                </strong>
              </p>
        </div>

        {{-- ======== ITEMS TABLE ======== --}}
        <table class="items">
            @include('rpci.partials.items-colgroup')
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Description</th>
                    <th>Stock Number</th>
                    <th>Unit of Measure</th>
                    <th>Unit Value (₱)</th>
                    <th>Balance Per Card (Qty)</th>
                    <th>On Hand Per Count (Qty)</th>
                    <th>Shortage / Overage (Qty)</th>
                    <th>Shortage / Overage (Value ₱)</th>
                    <th>Remarks</th>
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
                    <td>{{ $item->on_hand_per_count !== null ? number_format($item->on_hand_per_count) : '' }}</td>
                    <td>
                        @if ($item->shortage_quantity === null)
                            {{ '' }}
                        @else
                            @php $sq = (int) $item->shortage_quantity; @endphp
                            @if($sq > 0)
                                ({{ number_format($sq) }})
                            @else
                                {{ number_format($sq) }}
                            @endif
                        @endif
                    </td>
                    <td class="right">{{ $item->shortage_value !== null ? number_format($item->shortage_value, 2) : '' }}</td>
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
        {{--
            This is a separate <table> so it can sit flush against the body
            table, but it reuses the body's <colgroup> and `table-layout: fixed`
            so its columns land on exactly the same x-positions as the headers.

            It emits one <td> per column, in the same order as the header, with
            NO colspan/merged cells. Each total therefore sits directly beneath
            the column it belongs to:
              6 Balance Per Card (Qty)          -> $totalBpc
              7 On Hand Per Count (Qty)         -> $totalOhpc
              8 Shortage / Overage (Qty)        -> $totalSoQty
              9 Shortage / Overage (Value)      -> $totalSoVal
             10 Remarks                         -> blank
        --}}
        <table class="items" style="margin-top: -1px;">
            @include('rpci.partials.items-colgroup')
            <tfoot>
                <tr class="totals-row">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="totals-label">TOTALS</td>
                    <td>{{ number_format($totalBpc) }}</td>
                    <td>{{ number_format($totalOhpc) }}</td>
                    <td>{{ number_format($totalSoQty) }}</td>
                    <td class="right">{{ number_format($totalSoVal, 2) }}</td>
                    <td></td>
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
                    <div class="cert-label">Head of Agency / Authorized Representative</div>
                </div>

                {{-- RIGHT: Verified by (COA Representative) --}}
                <div class="cert-box">
                    <div style="font-size: 7.5pt; font-weight: bold; text-align: center; margin-bottom: 4px;">Verified by:</div>
                    <div class="sig-line" style="margin-top: 8px;"></div>
                    <div class="cert-name">{{ $rpci->verified_by ?? '____________________________' }}</div>
                    <div class="cert-label">Printed Name and Signature</div>
                    <div class="cert-label">COA Representative</div>
                </div>
            </div>
        </div>

        {{-- ======== FOOTER ======== --}}
        <div class="footer-section">
            <div>CHR-ROXII-ASD-FR-009 | Page 1 of 1 | Revision 0 | Effectivity Date: {{ \App\Support\ReportClock::format('F j, Y') }}</div>
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
