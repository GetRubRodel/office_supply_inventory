<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class StockInRecord extends Model
{
    protected $fillable = [
        'reference_number',
        'iar_id',
        'supply_id',
        'quantity_received',
        'unit_cost',
        'total_cost',
        'supplier',
        'date_received',
        'remarks',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'date_received' => 'date',
    ];

    public static function generateReferenceNumber(): string
    {
        // Portable equivalent of the MySQL-only
        // MAX(CAST(SUBSTRING_INDEX(reference_number, "-", -1) AS UNSIGNED)):
        // computes the highest trailing sequence across all reference numbers
        // and increments it. Works on MySQL and SQLite alike.
        $maxSeq = DB::table('stock_in_records')
            ->pluck('reference_number')
            ->map(function (string $referenceNumber): int {
                $parts = explode('-', $referenceNumber);

                return (int) end($parts);
            })
            ->max();

        $seq = ($maxSeq ?? 0) + 1;

        return sprintf('PO-%s-%04d', now()->format('Y'), $seq);
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function iar(): BelongsTo
    {
        return $this->belongsTo(InspectionAcceptanceReport::class, 'iar_id');
    }
}
