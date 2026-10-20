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
        $rows = $this->getRows();
        $grandTotal = $rows->sum('gross_amount');
    @endphp

    @if (count($rows) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Date Requested</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Control No.</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">P.O. Date</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">IAR Date</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Particulars</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Responsibility Center</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Source of Fund</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-right">Gross Amount</th>
                        <th class="px-3 py-2 border text-xs font-semibold text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($rows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-1.5 border text-xs text-center">{{ $row['date_requested']?->format('m/d/Y') ?? '—' }}</td>
                            <td class="px-3 py-1.5 border text-xs text-center">{{ $row['control_number'] }}</td>
                            <td class="px-3 py-1.5 border text-xs text-center">{{ $row['po_date']?->format('m/d/Y') ?? '—' }}</td>
                            <td class="px-3 py-1.5 border text-xs text-center">{{ $row['iar_date']?->format('m/d/Y') ?? '—' }}</td>
                            <td class="px-3 py-1.5 border text-xs">{{ $row['particulars'] ?: '—' }}</td>
                            <td class="px-3 py-1.5 border text-xs">{{ $row['responsibility_center'] ?: '—' }}</td>
                            <td class="px-3 py-1.5 border text-xs">{{ $row['source_of_fund'] ?: '—' }}</td>
                            <td class="px-3 py-1.5 border text-xs text-right">₱{{ number_format((float) $row['gross_amount'], 2) }}</td>
                            <td class="px-3 py-1.5 border text-xs text-center">
                                <x-filament::button wire:click="openView({{ $row['id'] }})" size="xs" color="gray" icon="heroicon-o-eye">
                                    View
                                </x-filament::button>
                            </td>
                        </tr>
                    @endforeach

                    <tr class="bg-gray-100 font-semibold">
                        <td colspan="7" class="px-3 py-2 border text-xs text-right">Grand Total</td>
                        <td class="px-3 py-2 border text-xs text-right">₱{{ number_format((float) $grandTotal, 2) }}</td>
                        <td class="px-3 py-2 border"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center text-gray-400 py-10 text-sm">
            No procurement records found for the selected filters.
        </div>
    @endif

    {{-- Read-only record details --}}
    <x-filament::modal id="view-record" width="3xl">
        @if ($viewing = $this->getViewingRecord())
            <x-slot name="header">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    Procurement Monitoring Details — {{ $viewing['control_number'] }}
                </h2>
            </x-slot>

            <div class="space-y-4">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-2">Purchase Request</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">Control No.</dt>
                            <dd class="font-medium text-right">{{ $viewing['control_number'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">Date Requested</dt>
                            <dd class="font-medium text-right">{{ $viewing['date_requested']?->format('F d, Y') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">Office / Division</dt>
                            <dd class="font-medium text-right">{{ $viewing['responsibility_center'] ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">RC Code</dt>
                            <dd class="font-medium text-right">{{ $viewing['pr']->rc_code ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">Project Code</dt>
                            <dd class="font-medium text-right">{{ $viewing['pr']->code ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">Source of Fund</dt>
                            <dd class="font-medium text-right">{{ $viewing['source_of_fund'] ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1 sm:col-span-2">
                            <dt class="text-gray-500">Name of Project</dt>
                            <dd class="font-medium text-right">{{ $viewing['particulars'] ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1 sm:col-span-2">
                            <dt class="text-gray-500">Purpose</dt>
                            <dd class="font-medium text-right">{{ $viewing['pr']->purpose ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">Approved Budget / APP</dt>
                            <dd class="font-medium text-right">₱{{ number_format((float) ($viewing['pr']->approved_budget ?? 0), 2) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">PR Status</dt>
                            <dd class="font-medium text-right">{{ ucfirst(str_replace('_', ' ', (string) $viewing['pr']->status)) }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-2">Purchase Order</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">P.O. No.</dt>
                            <dd class="font-medium text-right">{{ $viewing['po']->po_no ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">P.O. Date</dt>
                            <dd class="font-medium text-right">{{ $viewing['po_date']?->format('F d, Y') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">IAR Date</dt>
                            <dd class="font-medium text-right">{{ $viewing['iar_date']?->format('F d, Y') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">Supplier</dt>
                            <dd class="font-medium text-right">{{ $viewing['po']->supplier_name ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">Mode of Procurement</dt>
                            <dd class="font-medium text-right">{{ $viewing['po']->mode_of_procurement ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">Place of Delivery</dt>
                            <dd class="font-medium text-right">{{ $viewing['po']->place_of_delivery ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">Date of Delivery</dt>
                            <dd class="font-medium text-right">{{ $viewing['po']->date_of_delivery?->format('F d, Y') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">Gross Amount</dt>
                            <dd class="font-medium text-right">₱{{ number_format((float) $viewing['gross_amount'], 2) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-gray-100 py-1">
                            <dt class="text-gray-500">PO Status</dt>
                            <dd class="font-medium text-right">{{ ucfirst(str_replace('_', ' ', (string) $viewing['po']->status)) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-3">
                    <x-filament::button wire:click="closeView" color="gray">
                        Close
                    </x-filament::button>
                </div>
            </x-slot>
        @endif
    </x-filament::modal>

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
