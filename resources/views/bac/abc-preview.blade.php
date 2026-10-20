@php
    /** @var \App\Models\AbstractOfCanvass|null $previewAbc */
    $previewAbc = $abcId ? \App\Models\AbstractOfCanvass::with(['items', 'winningSupplier'])->find($abcId) : null;
    $grandTotal = 0.0;
@endphp

@if (! $previewAbc)
    <div class="text-sm text-gray-500 dark:text-gray-400">
        No ABC selected. Choose an eligible ABC above to preview its details.
    </div>
@else
    @php $priceColumn = $previewAbc->winningPriceColumn(); @endphp

    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <div class="text-xs text-gray-400 dark:text-gray-500">ABC No.</div>
            <div class="font-medium">{{ $previewAbc->abc_no }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-400 dark:text-gray-500">RFQ No.</div>
            <div class="font-medium">{{ $previewAbc->rfq?->rfq_no ?? '—' }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-400 dark:text-gray-500">Date of Opening</div>
            <div class="font-medium">{{ $previewAbc->date_of_opening ? $previewAbc->date_of_opening->format('F j, Y') : '—' }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-400 dark:text-gray-500">Winning Supplier</div>
            <div class="font-medium">{{ $previewAbc->winningSupplierName() ?? '—' }}</div>
        </div>
        <div class="col-span-2">
            <div class="text-xs text-gray-400 dark:text-gray-500">Canvassed Suppliers</div>
            <div class="font-medium">
                {{ collect([$previewAbc->supplier1_name, $previewAbc->supplier2_name, $previewAbc->supplier3_name])->filter()->implode(' • ') ?: '—' }}
            </div>
        </div>
    </div>

    @if ($previewAbc->items->isEmpty())
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No items recorded on this ABC.</p>
    @else
        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800">
                        <th class="px-3 py-2 font-medium">Item No.</th>
                        <th class="px-3 py-2 font-medium">Description</th>
                        <th class="px-3 py-2 text-right font-medium">Quantity</th>
                        <th class="px-3 py-2 font-medium">Unit</th>
                        <th class="px-3 py-2 text-right font-medium">Unit Cost (Winning Bid)</th>
                        <th class="px-3 py-2 text-right font-medium">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($previewAbc->items->sortBy('item_number') as $item)
                        @php
                            $qty = max(1, (int) ($item->quantity ?: 1));
                            $unitCost = (float) ($item->{$priceColumn} ?? 0);
                            $total = round($qty * $unitCost, 2);
                            $grandTotal += $total;
                        @endphp
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2">{{ $item->item_number ?? '—' }}</td>
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
