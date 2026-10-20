<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill abc_items.quantity / unit / description / item_number from the
 * source Request for Quotation (RFQ).
 *
 * Before this fix, the ABC item Repeater marked quantity, unit and
 * description as dehydrated(false), so those values were never persisted —
 * existing abc_items rows kept the DB defaults (quantity = 1, unit = NULL,
 * description = NULL) and item_number was never stored. This migration
 * restores the values from the originating rfq_items, matched positionally
 * (both collections are ordered by id and were created in one batch from
 * the RFQ; the ABC repeater is not reorderable or deletable).
 */
return new class extends Migration
{
    public function up(): void
    {
        $abcs = DB::table('abstracts_of_canvass')
            ->whereNotNull('rfq_id')
            ->orderBy('id')
            ->get();

        foreach ($abcs as $abc) {
            $rfqItems = DB::table('rfq_items')
                ->where('rfq_id', $abc->rfq_id)
                ->orderBy('id')
                ->get();

            $abcItems = DB::table('abc_items')
                ->where('abc_id', $abc->id)
                ->orderBy('id')
                ->get();

            foreach ($abcItems as $index => $abcItem) {
                $rfqItem = $rfqItems[$index] ?? null;

                if (! $rfqItem) {
                    continue;
                }

                $update = [];

                // quantity was never persisted (DB default 1), so restore it.
                if ((int) $abcItem->quantity === 1) {
                    $update['quantity'] = $rfqItem->quantity;
                }

                if ($abcItem->unit === null || $abcItem->unit === '') {
                    $update['unit'] = $rfqItem->unit;
                }

                if ($abcItem->description === null || $abcItem->description === '') {
                    $update['description'] = $rfqItem->item_description;
                }

                if ($abcItem->item_number === null) {
                    $update['item_number'] = $index + 1;
                }

                if ($update !== []) {
                    DB::table('abc_items')->where('id', $abcItem->id)->update($update);
                }
            }
        }
    }

    public function down(): void
    {
        // One-way data repair; no reverse operation is defined.
    }
};