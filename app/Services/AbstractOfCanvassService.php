<?php

namespace App\Services;

use App\Models\AbstractOfCanvass;
use App\Models\Supplier;

/**
 * Single source of truth for Abstract of Bids & Canvass evaluation logic.
 *
 * Responsibilities:
 *  - Compute each supplier's total quotation as the sum of (quantity × unit price).
 *  - Identify the supplier with the lowest total (the "Winning Supplier").
 *  - Detect ties between suppliers at the lowest total.
 *  - Resolve a free-text supplier name to a Supplier module record.
 */
class AbstractOfCanvassService
{
    /**
     * Number of supplier price columns used by the ABC (supplier1..3).
     */
    public const SUPPLIER_COLUMN_COUNT = 3;

    /**
     * Compute total quotation per supplier from a saved ABC record.
     * Totals are sum of quantity × unit price for every quoted item.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    public static function supplierTotals(AbstractOfCanvass $abc): array
    {
        return self::calculateTotals($abc->items->all());
    }

    /**
     * Compute total quotation per supplier from raw Filament repeater data.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: float, 1: float, 2: float}
     */
    public static function supplierTotalsFromItems(array $items): array
    {
        return self::calculateTotals($items);
    }

    /**
     * Core calculation shared by the record and raw-data variants.
     * Accepts a list of AbcItem models or associative arrays.
     *
     * @param  array<int, mixed>  $items
     * @return array{0: float, 1: float, 2: float}
     */
    private static function calculateTotals(array $items): array
    {
        $totals = [0.0, 0.0, 0.0];

        foreach ($items as $item) {
            $quantity = max(0, (float) self::valueOf($item, 'quantity', 1));

            for ($i = 0; $i < self::SUPPLIER_COLUMN_COUNT; $i++) {
                $price = (float) self::valueOf($item, 'supplier'.($i + 1).'_price', 0);
                $totals[$i] += round($quantity * $price, 2);
            }
        }

        return $totals;
    }

    /**
     * Read a property from either a model or an array, with a default.
     */
    private static function valueOf(mixed $item, string $key, mixed $default): mixed
    {
        if (is_array($item)) {
            return $item[$key] ?? $default;
        }

        return $item->{$key} ?? $default;
    }

    /**
     * Index (0-based) of the supplier with the lowest total quotation.
     *
     * Prefers the lowest *positive* total so that suppliers with missing
     * (unquoted) prices never win by default. When every total is zero,
     * falls back to the lowest including zero.
     *
     * Returns null when there are no items at all.
     */
    public static function lowestTotalIndex(array $totals): ?int
    {
        $minIndex = null;
        $minTotal = null;

        foreach ($totals as $index => $total) {
            if ($total > 0 && ($minTotal === null || $total < $minTotal)) {
                $minTotal = $total;
                $minIndex = $index;
            }
        }

        if ($minIndex === null && $totals !== []) {
            $min = min($totals);
            $minIndex = array_search($min, $totals, true);
            $minIndex = $minIndex === false ? null : $minIndex;
        }

        return $minIndex;
    }

    /**
     * Indexes of every supplier tied at the lowest total quotation.
     *
     * @return array<int, int>
     */
    public static function tiedLowestIndexes(array $totals): array
    {
        $winnerIndex = self::lowestTotalIndex($totals);

        if ($winnerIndex === null) {
            return [];
        }

        $lowest = $totals[$winnerIndex];

        return array_keys(
            array_filter($totals, fn (float $total): bool => abs($total - $lowest) < 0.005)
        );
    }

    /**
     * Resolve a Supplier module record from a free-text name.
     * Matching is trimmed and case-insensitive.
     */
    public static function resolveSupplierByName(?string $name): ?Supplier
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        return Supplier::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])
            ->first();
    }
}
