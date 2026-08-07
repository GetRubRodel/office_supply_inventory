<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RpciItem extends Model
{
    protected $table = 'rpci_items';

    protected $fillable = [
        'rpci_id',
        'supply_id',
        'article',
        'description',
        'stock_number',
        'unit_of_measure',
        'unit_value',
        'balance_per_card',
        'on_hand_per_count',
        'shortage_quantity',
        'shortage_value',
        'remarks',
        'sort_order',
    ];

    protected $casts = [
        'unit_value' => 'decimal:2',
        'balance_per_card' => 'integer',
        'on_hand_per_count' => 'integer',
        'shortage_quantity' => 'integer',
        'shortage_value' => 'decimal:2',
    ];

    public function rpci(): BelongsTo
    {
        return $this->belongsTo(Rpci::class);
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    /**
     * Compute shortage/overage based on balance_per_card and on_hand_per_count.
     *
     * FORMULA:
     *   Shortage/Overage Quantity = Balance Per Card - On Hand Per Count
     *   Shortage/Overage Value    = Shortage Quantity × Unit Value
     *
     * Positive result = SHORTAGE (card shows more than actual count)
     * Negative result = OVERAGE (actual count exceeds what card shows)
     */
    public function computeShortageOverage(): void
    {
        $this->shortage_quantity = ($this->balance_per_card ?? 0) - ($this->on_hand_per_count ?? 0);
        $this->shortage_value = $this->shortage_quantity * ($this->unit_value ?? 0);
    }
}
