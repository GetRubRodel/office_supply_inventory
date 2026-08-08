@php
    /** @var \App\Models\InspectionAcceptanceReport|null $previewIar */
    $previewIar = $iarId ? \App\Models\InspectionAcceptanceReport::with('items')->find($iarId) : null;
    $grandTotal = 0.0;
@endphp

@if (! $previewIar)
    <div class="text-sm text-gray-500 dark:text-gray-400">
        No IAR selected. Choose an eligible IAR above to preview its details.
    </div>
@else
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <div class="text-xs text-gray-400 dark:text-gray-500">IAR No.</div>
            <div class="font-medium">{{ $previewIar->iar_no }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-400 dark:text-gray-500">Purchase Order No.</div>
            <div class="font-medium">{{ $previewIar->po_no ?: '—' }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-400 dark:text-gray-500">Supplier</div>
            <div class="font-medium">{{ $previewIar->supplier_name ?: '—' }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-400 dark:text-gray-500">Inspection Date</div>
            <div class="font-medium">{{ $previewIar->inspection_date ? $previewIar->inspection_date->format('F j, Y') : '—' }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-400 dark:text-gray-500">Acceptance Date</div>
            <div class="font-medium">{{ $previewIar->acceptance_date ? $previewIar->acceptance_date->format('F j, Y') : '—' }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-400 dark:text-gray-500">Acceptance Details</div>
            <div class="font-medium">{{ $previewIar->acceptanceSummary() }}</div>
        </div>
    </div>

    @if ($previewIar->items->isEmpty())
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No items recorded on this IAR.</p>
    @else
        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800">
                        <th class="px-3 py-2 font-medium">Stock No.</th>
                        <th class="px-3 py-2 font-medium">Description</th>
                        <th class="px-3 py-2 text-right font-medium">Quantity</th>
                        <th class="px-3 py-2 font-medium">Unit</th>
                        <th class="px-3 py-2 text-right font-medium">Unit Cost</th>
                        <th class="px-3 py-2 text-right font-medium">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($previewIar->items as $item)
                        @php
                            $qty = $item->quantity_accepted ?: $item->quantity;
                            $unitCost = (float) $item->unit_cost;
                            $total = round($qty * $unitCost, 2);
                            $grandTotal += $total;
                        @endphp
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2">{{ $item->stock_no ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $item->description ?: '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $qty }}</td>
                            <td class="px-3 py-2">{{ $item->unit ?: '—' }}</td>
                            <td class="px-3 py-2 text-right">₱ {{ number_format($unitCost, 2) }}</td>
                            <td class="px-3 py-2 text-right">₱ {{ number_format($total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 font-semibold dark:border-gray-700">
                        <td colspan="5" class="px-3 py-2 text-right">TOTAL</td>
                        <td class="px-3 py-2 text-right">₱ {{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
@endif
