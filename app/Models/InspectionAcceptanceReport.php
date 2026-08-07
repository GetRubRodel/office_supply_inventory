<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionAcceptanceReport extends Model
{

    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_STOCKED_IN = 'stocked_in';

    protected $fillable = [
        'iar_no',
        'po_id',
        'supplier_name',
        'po_no',
        'date',
        'requisitioning_office_dept',
        'inspector_name',
        'inspector_designation',
        'inspection_date',
        'inspection_complete',
        'inspection_partial',
        'acceptor_name',
        'acceptor_designation',
        'acceptance_date',
        'acceptance_complete',
        'acceptance_partial',
        'status',
        'stocked_in_at',
        'stocked_in_by_user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'inspection_date' => 'date',
        'acceptance_date' => 'date',
        'inspection_complete' => 'boolean',
        'inspection_partial' => 'boolean',
        'acceptance_complete' => 'boolean',
        'editing_started_at' => 'datetime',
        'acceptance_partial' => 'boolean',
        'stocked_in_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (InspectionAcceptanceReport $iar) {
            $year = now()->year;
            $maxSeq = self::whereYear('created_at', $year)->max('id');
            $nextId = ($maxSeq ?? 0) + 1;
            $iar->iar_no = sprintf('IAR-%s-%04d', $year, $nextId);
        });
    }

    public function po(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(IarItem::class, 'iar_id');
    }

    public function stockInRecords(): HasMany
    {
        return $this->hasMany(StockInRecord::class, 'iar_id');
    }

    public function stockedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stocked_in_by_user_id');
    }

    /**
     * Get a comma-separated list of stock numbers from all IAR items.
     * Used for display in the list table.
     */
    public function getStockNumbersAttribute(): string
    {
        return $this->items->pluck('stock_no')->filter()->unique()->implode(', ');
    }

    /**
     * Get a comma-separated list of linked Supply stock numbers from all IAR items.
     * Shows the Supply module's stock_no for items that have been linked to a supply.
     * Used for display in the list table.
     */
    public function getSupplyNosAttribute(): string
    {
        return $this->items
            ->filter(fn (IarItem $item) => !empty($item->supply_id))
            ->map(fn (IarItem $item) => $item->supply?->stock_no)
            ->filter()
            ->unique()
            ->implode(', ');
    }

    public function isStockedIn(): bool
    {
        return $this->status === self::STATUS_STOCKED_IN;
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_STOCKED_IN => 'success',
            default => 'gray',
        };
    }
}
