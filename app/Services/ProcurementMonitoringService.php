<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the rows shown in the Procurement Monitoring Report.
 *
 * The report joins each Purchase Order back to its originating Purchase
 * Request through the existing document chain (no new relationships, tables
 * or database changes are introduced):
 *
 *     PurchaseOrder -> abc_id -> AbstractOfCanvass -> rfq_id -> RequestForQuotation -> purchase_request_id -> PurchaseRequest
 *     PurchaseOrder -> po_id (hasOne) -> InspectionAcceptanceReport   (IAR Date)
 *
 * All data is read-only: this service never writes to PR/PO records.
 */
class ProcurementMonitoringService
{
    /**
     * Report rows: one row per Purchase Request that already has a linked
     * Purchase Order. When a PR has more than one linked PO, only the most
     * recent PO is reported (prevents duplicate entries while keeping the
     * latest data on screen).
     *
     * @param  array<string, mixed>  $filters
     */
    public function rows(array $filters = []): Collection
    {
        $dateFrom = $this->parseDate($filters['date_from'] ?? null);
        $dateTo = $this->parseDate($filters['date_to'] ?? null);
        $officeDivision = $filters['office_division'] ?? null;

        // Only POs that link back to a Purchase Request belong in the report.
        $purchaseOrders = PurchaseOrder::query()
            ->whereHas('abc.rfq.purchaseRequest')
            ->with([
                'abc.rfq.purchaseRequest',
                'bacResolution',
                // Latest IAR per PO (the IAR resource only allows one IAR per PO).
                'iar' => fn ($query) => $query->latest('created_at'),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $rows = $purchaseOrders
            ->map(function (PurchaseOrder $po) use ($dateFrom, $dateTo, $officeDivision) {
                $pr = $po->abc?->rfq?->purchaseRequest;

                if (! $pr) {
                    return null;
                }

                if ($dateFrom && $pr->date && $pr->date->lt($dateFrom)) {
                    return null;
                }

                if ($dateTo && $pr->date && $pr->date->gt($dateTo)) {
                    return null;
                }

                if ($officeDivision && $pr->office_division !== $officeDivision) {
                    return null;
                }

                return [
                    'id' => $po->id,
                    'pr' => $pr,
                    'po' => $po,
                    'date_requested' => $pr->date,
                    'control_number' => static::controlNumber($pr->pr_no),
                    'po_date' => $po->date,
                    'iar_date' => $po->iar?->date,
                    'particulars' => $pr->name_of_project,
                    'responsibility_center' => $pr->office_division,
                    'source_of_fund' => $pr->source_of_fund,
                    'gross_amount' => (float) $po->total_amount,
                ];
            })
            ->filter()
            // POs are ordered newest-first, so keeping the first occurrence
            // per PR yields the latest PO for that PR.
            ->unique('pr.id')
            ->values();

        // Newest Date Requested first.
        return $rows
            ->sortByDesc(fn (array $row): int => $row['date_requested'] ? $row['date_requested']->timestamp : 0)
            ->values();
    }

    /**
     * Display the PR number only, e.g. "PR-2026-0001" -> "2026-0001".
     */
    public static function controlNumber(?string $prNo): string
    {
        return preg_replace('/^PR-/', '', (string) $prNo);
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return $value instanceof Carbon ? $value->copy() : Carbon::parse($value);
    }
}
