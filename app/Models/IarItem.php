<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IarItem extends Model
{
    protected $fillable = [
        'iar_id',
        'supply_id',
        'stock_no',
        'unit',
        'description',
        'quantity',
        'quantity_accepted',
        'quantity_rejected',
        'unit_cost',
        'category_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_accepted' => 'integer',
        'quantity_rejected' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function iar(): BelongsTo
    {
        return $this->belongsTo(InspectionAcceptanceReport::class, 'iar_id');
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
