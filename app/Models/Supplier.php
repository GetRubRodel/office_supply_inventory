<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'tin',
        'address',
    ];

    public function supplies(): HasMany
    {
        return $this->hasMany(Supply::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Comma-separated list of PO numbers linked to this supplier, ordered by
     * PO date then id. Displayed in the Supplier module for reference.
     */
    public function getPoNumbersAttribute(): string
    {
        return $this->purchaseOrders
            ->sortBy([['date', 'asc'], ['id', 'asc']])
            ->pluck('po_no')
            ->filter()
            ->unique()
            ->implode(', ');
    }

    /**
     * Comma-separated list of PO dates (Y-m-d) linked to this supplier.
     */
    public function getPoDatesAttribute(): string
    {
        return $this->purchaseOrders
            ->sortBy([['date', 'asc'], ['id', 'asc']])
            ->pluck('date')
            ->filter()
            ->map(fn ($date): string => $date->format('Y-m-d'))
            ->implode(', ');
    }
}
