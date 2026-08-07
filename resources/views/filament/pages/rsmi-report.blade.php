<x-filament-panels::page>
    <x-filament-panels::form>
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button wire:click="applyFilters" color="primary">
                Generate Report
            </x-filament::button>
            <x-filament::button wire:click="resetFilters" color="gray">
                Reset
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    @php
        $items = $this->getIssuedItems();
        $recap = $this->getRecapitulation($items);
        $grandTotalQty = $items->sum('quantity_issued');
        $grandTotalAmt = $items->sum(fn ($i) => $i->quantity_issued * (float) ($i->price ?? $i->supply?->unit_price ?? 0));
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo = $this->filters['date_to'] ?? null;
    @endphp

    @if(count($items) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">RIS No.</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Stock No.</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Item Description</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Unit</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Qty Issued</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Unit Cost</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Amount</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Date Issued</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($items as $item)
                        @php
                            $unitCost = (float) ($item->price ?? $item->supply?->unit_price ?? 0);
                            $amount = $item->quantity_issued * $unitCost;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-1.5 border text-xs text-center">{{ $item->requisition?->ris_no ?? '-' }}</td>
                            <td class="px-3 py-1.5 border text-xs text-center">{{ $item->supply?->stock_no ?? $item->stock_no ?? '-' }}</td>
                            <td class="px-3 py-1.5 border text-xs">{{ $item->supply?->name ?? $item->description ?? 'N/A' }}</td>
                            <td class="px-3 py-1.5 border text-xs text-center">{{ $item->supply?->unit ?? $item->unit ?? '-' }}</td>
                            <td class="px-3 py-1.5 border text-xs text-center">{{ number_format((int) $item->quantity_issued) }}</td>
                            <td class="px-3 py-1.5 border text-xs text-right">{{ number_format($unitCost, 2) }}</td>
                            <td class="px-3 py-1.5 border text-xs text-right">{{ number_format($amount, 2) }}</td>
                            <td class="px-3 py-1.5 border text-xs text-center">
                                {{ $item->requisition?->issued_by_date ? \Carbon\Carbon::parse($item->requisition?->issued_by_date)->format('m/d/Y') : '-' }}
                            </td>
                        </tr>
                    @endforeach

                    {{-- Grand Total row --}}
                    <tr class="bg-gray-100 font-semibold">
                        <td colspan="4" class="px-3 py-2 border text-xs text-right">Grand Total</td>
                        <td class="px-3 py-2 border text-xs text-center">{{ number_format($grandTotalQty) }}</td>
                        <td class="px-3 py-2 border text-xs text-right"></td>
                        <td class="px-3 py-2 border text-xs text-right">{{ number_format($grandTotalAmt, 2) }}</td>
                        <td class="px-3 py-2 border"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Recapitulation --}}
        @if(count($recap) > 0)
            <div class="mt-6">
                <h3 class="text-sm font-bold uppercase mb-2">Recapitulation</h3>
                <table class="min-w-full divide-y divide-gray-200 border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 border text-xs font-semibold text-center">Stock No.</th>
                            <th class="px-3 py-2 border text-xs font-semibold text-center">Quantity</th>
                            <th class="px-3 py-2 border text-xs font-semibold text-center">Unit Cost</th>
                            <th class="px-3 py-2 border text-xs font-semibold text-center">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @php $totalRecapQty = 0; $totalRecapCost = 0; @endphp
                        @foreach($recap as $r)
                            @php
                                $cost = $r['unit_cost'] * $r['quantity'];
                                $totalRecapQty += $r['quantity'];
                                $totalRecapCost += $cost;
                            @endphp
                            <tr>
                                <td class="px-3 py-1.5 border text-xs text-center">{{ $r['stock_number'] }}</td>
                                <td class="px-3 py-1.5 border text-xs text-center">{{ number_format($r['quantity']) }}</td>
                                <td class="px-3 py-1.5 border text-xs text-right">{{ number_format($r['unit_cost'], 2) }}</td>
                                <td class="px-3 py-1.5 border text-xs text-right">{{ number_format($cost, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-100 font-semibold">
                            <td class="px-3 py-2 border text-xs text-left">TOTAL</td>
                            <td class="px-3 py-2 border text-xs text-center">{{ number_format($totalRecapQty) }}</td>
                            <td class="px-3 py-2 border"></td>
                            <td class="px-3 py-2 border text-xs text-right">{{ number_format($totalRecapCost, 2) }}</td>
                        </tr>
                        <tr class="bg-gray-100 font-bold">
                            <td colspan="3" class="px-3 py-2 border text-xs text-right">Overall Total Cost</td>
                            <td class="px-3 py-2 border text-xs text-right">{{ number_format($totalRecapCost, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    @else
        <div class="text-center text-gray-400 py-10 text-sm">
            No issued supplies and materials found for the selected filters.
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Livewire.on('open-print-url', function ({ url }) {
                    window.open(url, '_blank');
                });
            });
        </script>
    @endpush
</x-filament-panels::page>
