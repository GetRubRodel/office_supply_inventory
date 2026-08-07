<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbcItem extends Model
{
    protected $fillable = [
        'abc_id',
        'item_number',
        'unit',
        'quantity',
        'description',
        'supplier1_price',
        'supplier2_price',
        'supplier3_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'supplier1_price' => 'decimal:2',
        'supplier2_price' => 'decimal:2',
        'supplier3_price' => 'decimal:2',
    ];

    public function abc(): BelongsTo
    {
        return $this->belongsTo(AbstractOfCanvass::class, 'abc_id');
    }
}
