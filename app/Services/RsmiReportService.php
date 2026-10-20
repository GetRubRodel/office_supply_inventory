<?php

namespace App\Services;

use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Support\ReportClock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Single source of truth for the Report of Supplies and Materials Issued
 * (RSMI, Appendix 64).
 *
 * The Print action and the Download action must show byte-for-byte the same
 * report. Previously each one built its own query and its own recapitulation,
 * which meant:
 *
 *   - the print route filtered on ['issued', 'received'] while the module page
 *     filtered on ['issued', 'pending_receipt', 'received'], so a
 *     pending_receipt requisition appeared in the download but not the print;
 *   - totals were recomputed from raw models in three different places.
 *
 * Every consumer now goes through {@see build()}, so a row present in one
 * output is present in the other, with identical values, ordering, labels and
 * totals.
 *
 * This service is strictly read-only: it never creates, updates or deletes any
 * inventory, requisition or supply record.
 */
class RsmiReportService
{
    /**
     * Requisition statuses that count as "supplies issued".
     *
     * Uses the model constants so a status rename cannot silently desync the
     * filter again.
     *
     * @var list<string>
     */
    public const ISSUED_STATUSES = [
        Requisition::STATUS_ISSUED,
        Requisition::STATUS_PENDING_RECEIPT,
        Requisition::STATUS_RECEIVED,
    ];

    /**
     * The report header constants, shared by the print and download renderers
     * so a label can never be changed in one place only.
     */
    public const TITLE = 'REPORT OF SUPPLIES AND MATERIALS ISSUED';

    public const APPENDIX = 'Appendix 64';

    public const ENTITY_NAME = 'COMMISSION ON HUMAN RIGHTS XII';

    public const FUND_CLUSTER = 'Regular Agency Fund';

    public const SUPPLY_NOTE = 'To be filled up by the Supply and/or Property Division/Unit';

    public const ACCOUNTING_NOTE = 'To be filled up by the Accounting Division/Unit';

    public const CERTIFICATION = 'I hereby certify to the correctness of the above information.';

    public const CUSTODIAN_LABEL = 'Signature over Printed Name of Supply and/or Property Custodian';

    public const ACCOUNTING_STAFF_LABEL = 'Signature over Printed Name of Designated Accounting Staff';

    /**
     * The eight main-table column headers, in display order.
     *
     * @var list<string>
     */
    public const HEADERS = [
        'RIS No.',
        'Responsibility Center Code',
        'Stock No.',
        'Item Description',
        'Unit',
        'Qty',
        'Unit Cost',
        'Amount',
    ];

    /**
     * The four recapitulation column headers, in display order.
     *
     * @var list<string>
     */
    public const RECAP_HEADERS = [
        'Stock No.',
        'Quantity',
        'Unit Cost',
        'Total Cost',
    ];

    /**
     * Build the complete, fully-formatted RSMI report payload.
     *
     * Every value is already rendered as a display string so that the print
     * and download renderers cannot disagree on number formatting.
     *
     * @param  array{date_from?: string|null, date_to?: string|null, category_id?: mixed, supply_id?: mixed}  $filters
     * @return array{
     *     items: list<array<string, string>>,
     *     recapitulation: list<array<string, string|int>>,
     *     grand_total_qty: string,
     *     grand_total_amount: string,
     *     recap_total_qty: string,
     *     recap_total_cost: string,
     *     period: string|null,
     *     period_label: string|null,
     *     generated_at: string,
     *     issued_by: string|null,
     * }
     */
    public function build(array $filters = [], ?string $issuedBy = null): array
    {
        $items = $this->items($filters);
        $recapitulation = $this->recapitulation($items);

        $grandTotalAmount = 0.0;
        $renderedItems = [];

        foreach ($items as $item) {
            $unitCost = (float) $item->unit_cost_for_issuance;
            $amount = (float) $item->quantity_issued * $unitCost;
            $grandTotalAmount += $amount;

            $renderedItems[] = [
                'ris_no' => (string) ($item->requisition?->ris_no ?: '-'),
                'responsibility_center_code' => (string) ($item->requisition?->responsibility_center_code ?: '-'),
                'stock_no' => (string) ($item->supply?->stock_no ?? $item->stock_no ?? '-'),
                'item_description' => (string) ($item->supply?->name ?? $item->description ?? 'N/A'),
                'unit' => (string) ($item->supply?->unit ?? $item->unit ?? '-'),
                'quantity' => number_format((int) $item->quantity_issued),
                'unit_cost' => number_format($unitCost, 2),
                'amount' => number_format($amount, 2),
            ];
        }

        $recapTotalQty = 0;
        $recapTotalCost = 0.0;
        $renderedRecap = [];

        foreach ($recapitulation as $row) {
            $cost = (float) $row['unit_cost'] * (int) $row['quantity'];
            $recapTotalQty += (int) $row['quantity'];
            $recapTotalCost += $cost;

            $renderedRecap[] = [
                'stock_number' => (string) $row['stock_number'],
                'quantity' => (int) $row['quantity'],
                'unit_cost' => number_format((float) $row['unit_cost'], 2),
                'total_cost' => number_format($cost, 2),
            ];
        }

        return [
            'items' => $renderedItems,
            'recapitulation' => $renderedRecap,
            'grand_total_qty' => number_format((int) $items->sum('quantity_issued')),
            'grand_total_amount' => number_format($grandTotalAmount, 2),
            'recap_total_qty' => number_format($recapTotalQty),
            'recap_total_cost' => number_format($recapTotalCost, 2),
            'period' => $this->period($filters),
            'period_label' => $this->periodLabel($filters),
            // Rendered through ReportClock so the print header and the DOCX
            // header can never disagree on the timezone.
            'generated_at' => ReportClock::format(),
            'issued_by' => $issuedBy,
        ];
    }

    /**
     * Fetch the issued RequisitionItems matching the given filters.
     *
     * @param  array{date_from?: string|null, date_to?: string|null, category_id?: mixed, supply_id?: mixed}  $filters
     * @return Collection<int, RequisitionItem>
     */
    public function items(array $filters = []): Collection
    {
        $query = RequisitionItem::with(['requisition', 'supply'])
            ->whereHas('requisition', fn (Builder $q) => $q->whereIn('status', self::ISSUED_STATUSES));

        if ($dateFrom = $filters['date_from'] ?? null) {
            $query->whereHas('requisition', fn (Builder $q) => $q->whereDate('issued_by_date', '>=', $dateFrom));
        }

        if ($dateTo = $filters['date_to'] ?? null) {
            $query->whereHas('requisition', fn (Builder $q) => $q->whereDate('issued_by_date', '<=', $dateTo));
        }

        if ($categoryId = $filters['category_id'] ?? null) {
            $query->whereHas('supply', fn (Builder $q) => $q->where('category_id', $categoryId));
        }

        if ($supplyId = $filters['supply_id'] ?? null) {
            $query->where('supply_id', $supplyId);
        }

        return $query->orderBy('id')->get();
    }

    /**
     * Group issued items by stock number for the recapitulation table.
     *
     * @param  Collection<int, RequisitionItem>  $items
     * @return list<array{stock_number: string, quantity: int, unit_cost: float}>
     */
    public function recapitulation(Collection $items): array
    {
        return $items
            ->groupBy(fn ($item) => $item->supply?->stock_no ?? $item->stock_no)
            ->map(function ($group, $stockNo) {
                $first = $group->first();

                return [
                    'stock_number' => (string) $stockNo,
                    'quantity' => (int) $group->sum('quantity_issued'),
                    'unit_cost' => (float) $first->unit_cost_for_issuance,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Total quantity issued across the filtered items.
     *
     * @param  Collection<int, RequisitionItem>  $items
     */
    public function grandTotalQuantity(Collection $items): int
    {
        return (int) $items->sum('quantity_issued');
    }

    /**
     * Total amount issued across the filtered items.
     *
     * @param  Collection<int, RequisitionItem>  $items
     */
    public function grandTotalAmount(Collection $items): float
    {
        return (float) $items->sum(
            fn ($item) => (float) $item->quantity_issued * (float) $item->unit_cost_for_issuance
        );
    }

    /**
     * The raw period string, e.g. "January 01, 2026 - January 31, 2026".
     *
     * @param  array<string, mixed>  $filters
     */
    public function period(array $filters = []): ?string
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        if ($dateFrom && $dateTo) {
            return \Carbon\Carbon::parse($dateFrom)->format('F d, Y')
                . ' - ' . \Carbon\Carbon::parse($dateTo)->format('F d, Y');
        }

        if ($dateFrom) {
            return 'From ' . \Carbon\Carbon::parse($dateFrom)->format('F d, Y');
        }

        if ($dateTo) {
            return 'To ' . \Carbon\Carbon::parse($dateTo)->format('F d, Y');
        }

        return null;
    }

    /**
     * The bold label the period is prefixed with in the print header.
     *
     * @param  array<string, mixed>  $filters
     */
    public function periodLabel(array $filters = []): ?string
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        if ($dateFrom && $dateTo) {
            return 'Period:';
        }

        if ($dateFrom) {
            return 'From:';
        }

        if ($dateTo) {
            return 'To:';
        }

        return null;
    }
}
