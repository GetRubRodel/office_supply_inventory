<?php
/** @var \App\Filament\Pages\InventoryReport $this */
?>

<x-filament-panels::page>
    <x-filament-panels::form>
        {{ $this->form }}

        <div class="flex gap-2">
            <x-filament::button wire:click="applyFilters" color="primary">
                Apply Filters
            </x-filament::button>
            <x-filament::button wire:click="resetFilters" color="gray">
                Reset
            </x-filament::button>
            <x-filament::button wire:click="printReport" color="warning" icon="heroicon-m-printer">
                Print
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <div id="report-content" class="space-y-6 mt-6">
        {{-- Summary Section --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Items</h3>
                <p class="text-2xl font-bold">{{ $this->getSupplies()->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Stock In</h3>
                <p class="text-2xl font-bold text-success-600">{{ $this->getTotalStockIn() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Stock Out</h3>
                <p class="text-2xl font-bold text-danger-600">{{ $this->getTotalStockOut() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Available Stock</h3>
                <p class="text-2xl font-bold text-primary-600">{{ $this->getTotalCurrentStock() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Inventory Value</h3>
                <p class="text-2xl font-bold">₱{{ number_format($this->getTotalInventoryValue(), 2) }}</p>
            </div>
        </div>

        {{-- Inventory Balances Table (Screen) --}}
        <div id="screen-inventory-table" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium">Inventory Balances (Moving Average Cost)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-3 py-2 text-left font-medium whitespace-nowrap">IAR No.</th>
                            <th class="px-3 py-2 text-left font-medium whitespace-nowrap">Stock No.</th>
                            <th class="px-3 py-2 text-left font-medium">Item Description</th>
                            <th class="px-3 py-2 text-left font-medium">Category</th>
                            <th class="px-3 py-2 text-left font-medium whitespace-nowrap">UoM</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Current Qty</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Unit Cost (MAC)</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Total Cost</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Total Stock In</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Total Stock Out</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Available Stock</th>
                            <th class="px-3 py-2 text-left font-medium whitespace-nowrap">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->getSupplies() as $supply)
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                <td class="px-3 py-2 whitespace-nowrap text-xs">{{ $supply->latest_reference_number ?? '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $supply->stock_no }}</td>
                                <td class="px-3 py-2">{{ $supply->name }}</td>
                                <td class="px-3 py-2">{{ $supply->category?->name ?? 'N/A' }}</td>
                                <td class="px-3 py-2">{{ $supply->unit ?? 'N/A' }}</td>
                                <td class="px-3 py-2 text-right font-semibold {{ $supply->isLowStock() ? 'text-danger-600' : '' }}">
                                    {{ number_format($supply->current_stock) }}
                                </td>
                                <td class="px-3 py-2 text-right">₱{{ number_format($supply->unit_cost, 2) }}</td>
                                <td class="px-3 py-2 text-right">₱{{ number_format($supply->total_cost, 2) }}</td>
                                <td class="px-3 py-2 text-right text-success-600">{{ number_format($supply->total_stock_in) }}</td>
                                <td class="px-3 py-2 text-right text-danger-600">{{ number_format($supply->total_stock_out) }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ number_format($supply->current_available_stock) }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    {{ $supply->last_updated_date ? \Carbon\Carbon::parse($supply->last_updated_date)->format('Y-m-d') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-4 text-center text-gray-500">No supplies found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Inventory Balances Table (Print Only — excludes IAR No., Stock No., Category, Current Qty, Last Updated) --}}
        <div id="print-inventory-table" style="display: none;" class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-lg font-medium">Inventory Balances (Moving Average Cost)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-3 py-2 text-left font-medium">Item Description</th>
                            <th class="px-3 py-2 text-left font-medium whitespace-nowrap">UoM</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Unit Cost (MAC)</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Total Cost</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Total Stock In</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Total Stock Out</th>
                            <th class="px-3 py-2 text-right font-medium whitespace-nowrap">Available Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->getSupplies() as $supply)
                            <tr class="border-t border-gray-200">
                                <td class="px-3 py-2">{{ $supply->name }}</td>
                                <td class="px-3 py-2">{{ $supply->unit ?? 'N/A' }}</td>
                                <td class="px-3 py-2 text-right">₱{{ number_format($supply->unit_cost, 2) }}</td>
                                <td class="px-3 py-2 text-right">₱{{ number_format($supply->total_cost, 2) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($supply->total_stock_in) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($supply->total_stock_out) }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ number_format($supply->current_available_stock) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">No supplies found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Issued Items History Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium">Issued Stock History (from RIS)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-4 py-2 text-left font-medium">RIS No.</th>
                            <th class="px-4 py-2 text-left font-medium">Item</th>
                            <th class="px-4 py-2 text-left font-medium">Category</th>
                            <th class="px-4 py-2 text-right font-medium">Quantity Issued</th>
                            <th class="px-4 py-2 text-right font-medium">Unit Price</th>
                            <th class="px-4 py-2 text-left font-medium">Issue Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->getIssuedItems() as $item)
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-2">{{ $item->requisition?->ris_no ?? 'N/A' }}</td>
                                <td class="px-4 py-2">{{ $item->supply?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2">{{ $item->supply?->category?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-right">{{ $item->quantity_issued }}</td>
                                <td class="px-4 py-2 text-right">{{ $item->price ? '₱' . number_format($item->price, 2) : 'N/A' }}</td>
                                <td class="px-4 py-2">{{ $item->requisition?->issued_by_date?->format('Y-m-d') ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">No issued items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('print-report', function () {
                var screenTable = document.getElementById('screen-inventory-table');
                var printTable = document.getElementById('print-inventory-table');

                // Swap: hide screen table, show print-only table
                if (screenTable) screenTable.style.display = 'none';
                if (printTable) printTable.style.display = '';

                var printContents = document.getElementById('report-content').innerHTML;
                var originalContents = document.body.innerHTML;

                document.body.innerHTML =
                    '<div style="padding: 20px; font-family: Arial, sans-serif;">' +
                        '<h1 style="text-align: center; margin-bottom: 20px;">Inventory Report</h1>' +
                        '<p style="text-align: center; color: #666; margin-bottom: 30px;">Generated on ' + '{{ now()->format("Y-m-d H:i:s") }}' + '</p>' +
                        printContents +
                    '</div>';

                window.print();
                document.body.innerHTML = originalContents;
                window.location.reload();
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
