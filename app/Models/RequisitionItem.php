<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionItem extends Model
{
    protected $fillable = [
        'requisition_id',
        'stock_no',
        'supply_id',
        'unit',
        'description',
        'quantity_requested',
        'stock_available',
        'available_stock',
        'price',
        'issued_unit_cost',
        'quantity_issued',
        'remarks',
    ];

    protected $casts = [
        'stock_available' => 'boolean',
        'available_stock' => 'integer',
        'price' => 'decimal:2',
        'issued_unit_cost' => 'decimal:2',
        'quantity_requested' => 'integer',
        'quantity_issued' => 'integer',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    /**
     * Unit cost (Moving Average Cost) at the time of issuance.
     *
     * Resolution order (a value of zero is treated as "no cost recorded"
     * and falls through, matching the RPCI report convention):
     *  1. issued_unit_cost — MAC snapshotted when the RIS was issued.
     *  2. price            — unit cost snapshot when the RIS was requested.
     *  3. supply.unit_cost — the supply's current MAC.
     *  4. supply.unit_price — standard/reference price as a last resort.
     *
     * Used by the RSMI report (page + print) so issued items are always
     * valued at the MAC in effect for that transaction.
     */
    public function getUnitCostForIssuanceAttribute(): float
    {
        $snapshot = (float) ($this->issued_unit_cost ?? 0);
        if ($snapshot > 0) {
            return $snapshot;
        }

        $price = (float) ($this->price ?? 0);
        if ($price > 0) {
            return $price;
        }

        $mac = (float) ($this->supply?->unit_cost ?? 0);
        if ($mac > 0) {
            return $mac;
        }

        return (float) ($this->supply?->unit_price ?? 0);
    }
}
