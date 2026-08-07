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
        'quantity_issued',
        'remarks',
    ];

    protected $casts = [
        'stock_available' => 'boolean',
        'available_stock' => 'integer',
        'price' => 'decimal:2',
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
}
