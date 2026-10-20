<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;

/**
 * Keeps the Suppliers module in sync with supplier information captured on
 * Purchase Orders, using only the existing PurchaseOrder -> Supplier
 * relationship (purchase_orders.supplier_id).
 *
 * Rules (duplicate-averse, supplier-record-first):
 * - The Supplier record is the primary source of truth.
 * - If a matching Supplier already exists, the PO is linked to it and only
 *   MISSING fields (phone, tin, address) are filled in from PO data — existing
 *   supplier values are never overwritten.
 * - If no Supplier matches, one is created from the PO data and linked.
 * - Resolution order: explicit supplier_id FK -> case-insensitive name match
 *   -> TIN match -> create new.
 */
class SupplierSyncService
{
    /**
     * Sync the supplier captured on a single Purchase Order.
     *
     * Returns the resolved Supplier, or null when the PO carries no supplier
     * name (nothing to sync).
     */
    public function syncFromPurchaseOrder(PurchaseOrder $po): ?Supplier
    {
        // The name captured on the Purchase Order itself — not the live
        // BAC/ABC-derived `supplier_name` accessor — so a Purchase Order that
        // carries no supplier name of its own is skipped instead of inheriting
        // the winning bid from its source BAC Resolution.
        $name = trim((string) $po->capturedSupplierName());

        if ($name === '') {
            return null;
        }

        $supplier = $this->resolveSupplier($po, $name);

        // Link the PO to the supplier through the existing FK relationship.
        // The update runs through the query builder so it does not re-trigger
        // the PurchaseOrder::saved hook (no recursion).
        if ((int) $po->supplier_id !== (int) $supplier->id) {
            PurchaseOrder::whereKey($po->id)->update(['supplier_id' => $supplier->id]);
            $po->supplier_id = $supplier->id;
        }

        $this->fillMissingFields($supplier, $po);

        return $supplier;
    }

    /**
     * Backfill existing Purchase Orders that were created before the sync
     * existed. Idempotent: re-running only links and fills blanks; it never
     * creates duplicates.
     */
    public function backfillAll(): int
    {
        $count = 0;

        PurchaseOrder::query()
            ->whereNotNull('supplier_name')
            ->where('supplier_name', '<>', '')
            ->orderBy('id')
            ->each(function (PurchaseOrder $po) use (&$count) {
                if ($this->syncFromPurchaseOrder($po) !== null) {
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Resolve the Supplier for a PO, preferring existing records so we never
     * create duplicates.
     */
    private function resolveSupplier(PurchaseOrder $po, string $name): Supplier
    {
        // 1. Respect an explicit relationship when it is still consistent with
        //    the name on the PO.
        if ($po->supplier_id) {
            $linked = Supplier::find($po->supplier_id);

            if ($linked && $this->namesMatch($linked->name, $name)) {
                return $linked;
            }

            // The linked supplier's name no longer matches the PO. Prefer an
            // existing name match over creating a new record; otherwise keep
            // the original link (never create a duplicate just because the
            // PO name drifted).
            $match = $this->findByName($name);
            if ($match) {
                return $match;
            }

            if ($linked) {
                return $linked;
            }
        }

        // 2. Reuse an existing supplier with the same name.
        $byName = $this->findByName($name);
        if ($byName) {
            return $byName;
        }

        // 3. Reuse an existing supplier with the same TIN.
        $tin = $this->cleanTin($po->tin);
        if ($tin !== null) {
            $byTin = Supplier::where('tin', $tin)->first();
            if ($byTin) {
                return $byTin;
            }
        }

        // 4. Nothing matched — create a new supplier from the PO data.
        return Supplier::create([
            'name' => $name,
            'phone' => $this->cleanPhone($po->contact_number),
            'tin' => $tin,
            'address' => trim((string) $po->address) !== '' ? trim((string) $po->address) : null,
        ]);
    }

    /**
     * Fill in only the supplier fields that are missing, using the latest PO
     * data. Existing supplier values are never overwritten.
     */
    private function fillMissingFields(Supplier $supplier, PurchaseOrder $po): void
    {
        $changed = false;

        if (blank($supplier->phone)) {
            $phone = $this->cleanPhone($po->contact_number);
            if ($phone !== null) {
                $supplier->phone = $phone;
                $changed = true;
            }
        }

        if (blank($supplier->tin)) {
            $tin = $this->cleanTin($po->tin);
            if ($tin !== null) {
                $supplier->tin = $tin;
                $changed = true;
            }
        }

        if (blank($supplier->address)) {
            $address = trim((string) $po->address);
            if ($address !== '') {
                $supplier->address = $address;
                $changed = true;
            }
        }

        if ($changed) {
            $supplier->save();
        }
    }

    private function namesMatch(?string $a, string $b): bool
    {
        return mb_strtolower(trim((string) $a)) === mb_strtolower($b);
    }

    private function findByName(string $name): ?Supplier
    {
        return Supplier::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first();
    }

    private function cleanPhone(?string $value): ?string
    {
        $value = preg_replace('/[^\d+]/', '', (string) $value);

        return $value === '' ? null : $value;
    }

    private function cleanTin(?string $value): ?string
    {
        $value = preg_replace('/\D/', '', (string) $value);

        return $value === '' ? null : $value;
    }
}
