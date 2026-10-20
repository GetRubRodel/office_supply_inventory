<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Clear all existing records from:
     *  1. Inventory Report  → rpci_items, rpcis
     *  2. RSMI Report       → (runtime view backed by requisition_items + requisitions)
     *  3. Requisition (RIS) → requisition_items, requisitions
     *
     * Deletion order respects FK constraints:
     *  - rpci_items.rpci_id       → rpcis.id       (cascadeOnDelete)
     *  - requisition_items.requisition_id → requisitions.id (cascadeOnDelete)
     *
     * Other modules (Supplies, PR, RFQ, ABC, PO, IAR, BAC, etc.) are untouched.
     */
    public function up(): void
    {
        // ── 1. RPCI / Inventory Report ────────────────────────────────
        // Delete child rows first, then parent.
        DB::table('rpci_items')->delete();
        DB::table('rpcis')->delete();

        // ── 2. Requisition (RIS) + RSMI Report data ───────────────────
        // RSMI is a runtime report that reads from requisition_items,
        // so clearing these tables empties all three modules.
        DB::table('requisition_items')->delete();
        DB::table('requisitions')->delete();
    }

    public function down(): void
    {
        // This migration is one-way: data cannot be restored.
        // The down() method intentionally does nothing.
    }
};
