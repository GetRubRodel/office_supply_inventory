<?php

namespace App\Services;

use App\Support\CurrentUser;

use App\Models\InspectionAcceptanceReport;
use App\Models\StockInRecord;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockInService
{
    /**
     * Process automatic stock-in for an approved IAR.
     *
     * For each accepted IAR item (quantity_accepted > 0):
     *  - If the item already has a supply_id, use that existing Supply record.
     *  - Otherwise, search for an existing Supply with a matching item description (name).
     *  - If a match is found, the existing Supply is reused: quantity is added and MAC
     *    is recalculated. No new Stock Number is generated.
     *  - If no match is found, a new Supply is created with an auto-generated unique
     *    Stock Number.
     *
     * Duplicate prevention:
     *  - Before creating a new supply, the system checks whether an item with the same
     *    Item Description already exists in the Supplies module (case-insensitive).
     *  - This ensures each unique item description has exactly one Stock Number
     *    throughout the system.
     *
     * MAC (Moving Average Cost) recalculation:
     *  - New MAC = ((Old Qty × Old MAC) + (New Qty × New Unit Cost)) / (Old Qty + New Qty)
     *  - Applied whenever stock is received, whether to an existing or new supply.
     *
     * Every newly created Supply MUST have a valid category_id. The resolution order is:
     *  1. The IAR item's own category_id (set by the user on the IAR form).
     *  2. The linked Supply's category_id (if the item has a supply_id).
     *  3. A user-friendly validation error is thrown if no category can be resolved.
     *
     * @param InspectionAcceptanceReport $iar
     * @return array{success: bool, message: string, records: array}
     * @throws ValidationException
     */
    public function process(InspectionAcceptanceReport $iar): array
    {
        // ── Pre-flight validations (outside transaction, no DB writes) ──────

        if ($iar->isStockedIn()) {
            throw ValidationException::withMessages([
                'status' => 'This IAR has already been stocked in.',
            ]);
        }

        if ($iar->stockInRecords()->exists()) {
            throw ValidationException::withMessages([
                'iar_id' => 'Stock-in records already exist for this IAR.',
            ]);
        }

        $iar->loadMissing('items');

        $acceptedItems = $iar->items->where('quantity_accepted', '>', 0);
        if ($acceptedItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'At least one IAR item must have a quantity accepted greater than 0.',
            ]);
        }

        // Validate: description
        $missingDescription = $acceptedItems->first(fn ($item) => empty(trim($item->description ?? '')));
        if ($missingDescription) {
            throw ValidationException::withMessages([
                'items' => "Item row {$missingDescription->id} is missing a description. Please fill in the description for every accepted item.",
            ]);
        }

        // Validate: unit_cost > 0
        $missingCost = $acceptedItems->first(fn ($item) => empty($item->unit_cost) || (float) $item->unit_cost <= 0);
        if ($missingCost) {
            throw ValidationException::withMessages([
                'items' => "Item \"{$missingCost->description}\" has no unit cost. Please set a unit cost greater than zero before stocking in.",
            ]);
        }

        // Validate: every item that will create a NEW supply must have a category_id.
        $missingCategory = $acceptedItems->first(function ($item) {
            if (!empty($item->supply_id)) {
                return false;
            }
            if ($this->findExistingSupply($item)) {
                return false;
            }
            return empty($item->category_id);
        });
        if ($missingCategory) {
            throw ValidationException::withMessages([
                'category_id' => "Item \"{$missingCategory->description}\" is not linked to an existing supply and has no category assigned. Please edit the IAR and select a Category for this item before stocking in.",
            ]);
        }

        // ── Process inside a DB transaction ────────────────────────────────

        $createdRecords  = [];
        $createdSupplies = [];
        $updatedSupplies = [];

        DB::transaction(function () use ($iar, $acceptedItems, &$createdRecords, &$createdSupplies, &$updatedSupplies) {
            foreach ($acceptedItems as $item) {
                $quantityAccepted = (int) $item->quantity_accepted;
                $unitCost         = (float) ($item->unit_cost ?? 0);

                $supply = $this->findOrCreateSupply($item);

                // Link supply back to the IAR item for traceability and data consistency.
                // Always ensure the IAR item reflects the canonical supply record's
                // stock_no, unit, and category so that downstream reports are consistent.
                $needsSave = false;

                if (empty($item->supply_id) || $item->supply_id !== $supply->id) {
                    $item->supply_id = $supply->id;
                    $needsSave = true;
                }
                if ($item->stock_no !== $supply->stock_no) {
                    $item->stock_no = $supply->stock_no;
                    $needsSave = true;
                }
                if (empty($item->unit) && !empty($supply->unit)) {
                    $item->unit = $supply->unit;
                    $needsSave = true;
                }
                if ($needsSave) {
                    $item->save();
                }

                if ($supply->wasRecentlyCreated) {
                    $createdSupplies[] = $supply->stock_no;
                } else {
                    $updatedSupplies[] = $supply->stock_no;
                }

                // Create the StockInRecord with IAR number as reference
                $record = new StockInRecord();
                $record->reference_number = StockInRecord::generateReferenceNumber();
                $record->iar_id            = $iar->id;
                $record->supply_id         = $supply->id;
                $record->quantity_received = $quantityAccepted;
                $record->unit_cost         = $unitCost;
                $record->total_cost        = $quantityAccepted * $unitCost;
                $record->supplier          = $iar->supplier_name;
                $record->date_received     = $iar->date ?? now();
                $record->remarks           = "Auto stock-in from IAR {$iar->iar_no}";
                $record->save();

                // Recalculate MAC and update unit_cost / total_cost on the Supply
                $supply->receiveStock(
                    quantity:        $quantityAccepted,
                    unitCost:        $unitCost,
                    referenceNumber: $iar->iar_no,
                    dateReceived:    $iar->date ?? now()
                );

                $createdRecords[] = $record;
            }

            $iar->status               = InspectionAcceptanceReport::STATUS_STOCKED_IN;
            $iar->stocked_in_at        = now();
            $iar->stocked_in_by_user_id = CurrentUser::id();
            $iar->save();
        });

        $parts = [];
        $parts[] = count($createdRecords) . ' item(s) successfully stocked in from IAR ' . $iar->iar_no;
        if ($createdSupplies) {
            $parts[] = 'New supplies created: ' . implode(', ', $createdSupplies);
        }
        if ($updatedSupplies) {
            $parts[] = 'Existing supplies updated: ' . implode(', ', $updatedSupplies);
        }

        return [
            'success' => true,
            'message' => implode('. ', $parts) . '.',
            'records' => $createdRecords,
        ];
    }

    /**
     * Find an existing Supply matching the IAR item, or create a new one.
     *
     * Duplicate prevention — matching criteria:
     *  1. If the IAR item already has a supply_id, use that Supply directly.
     *  2. Try to find a Supply with a matching item description (name), case-insensitive.
     *  3. Create a new Supply with an auto-generated unique Stock Number.
     *
     * When a new Supply is created, the IAR item's unit and category are inherited
     * so that each new record has complete metadata from the start.
     *
     * @param \App\Models\IarItem $item
     * @return Supply
     */
    protected function findOrCreateSupply(\App\Models\IarItem $item): Supply
    {
        // 1. Already linked to a supply — use it as-is
        if (!empty($item->supply_id)) {
            return Supply::findOrFail($item->supply_id);
        }

        // 2. Check for duplicate: match on description only
        $existingSupply = $this->findExistingSupply($item);
        if ($existingSupply) {
            return $existingSupply;
        }

        // 3. Create a brand-new supply
        $categoryId = $this->resolveCategoryId($item);

        $supply = new Supply();
        $supply->name              = trim($item->description);
        $supply->unit              = $item->unit ?? null;
        $supply->category_id       = $categoryId;
        $supply->current_stock     = 0;
        $supply->unit_cost         = 0;
        $supply->average_unit_cost = 0; // kept in sync for DB backward compatibility
        $supply->total_cost        = 0;
        $supply->reorder_level     = 0;
        $supply->status            = 'active';
        // stock_no is auto-generated in Supply::boot() when empty
        $supply->save();

        return $supply;
    }

    /**
     * Search for an existing Supply that matches the IAR item on item description (name).
     *
     * Duplicate prevention — matching criteria:
     *  Match is performed on the item description (name) alone, using a case-insensitive
     *  comparison. This ensures that each unique item description has exactly one Stock
     *  Number throughout the system, regardless of differences in unit, category, or
     *  stock number across procurement transactions.
     *
     * @param \App\Models\IarItem $item
     * @return Supply|null
     */
    protected function findExistingSupply(\App\Models\IarItem $item): ?Supply
    {
        $itemName = trim($item->description ?? '');

        if ($itemName === '') {
            return null;
        }

        return Supply::whereRaw('LOWER(name) = ?', [mb_strtolower($itemName)])->first();
    }

    /**
     * Resolve a valid category_id for a new Supply record.
     *
     * @param \App\Models\IarItem $item
     * @return int|null
     */
    protected function resolveCategoryId(\App\Models\IarItem $item): ?int
    {
        // Direct: IAR item has an explicit category
        if (!empty($item->category_id)) {
            return (int) $item->category_id;
        }

        // Fallback: inherit from the linked supply
        if (!empty($item->supply_id)) {
            $supply = Supply::find($item->supply_id);
            if ($supply && !empty($supply->category_id)) {
                return (int) $supply->category_id;
            }
        }

        return null;
    }
}
