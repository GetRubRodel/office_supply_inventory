<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class Supply extends Model
{
    protected $fillable = [
        'stock_no',
        'category_id',
        'supplier_id',
        'name',
        'description',
        'unit',
        'reorder_level',
        'current_stock',
        'unit_price',
        'average_unit_cost',
        'unit_cost',
        'total_cost',
        'status',
        'reference_number',
        'last_received_date',
        'latest_reference_number',
    ];

    protected $casts = [
        'reorder_level' => 'integer',
        'current_stock' => 'integer',
        'unit_price' => 'decimal:2',
        'average_unit_cost' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'last_received_date' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Supply $supply) {
            if (empty($supply->stock_no)) {
                $supply->stock_no = static::generateStockNo();
            }
        });
    }

    public static function generateStockNo(): string
    {
        $maxSeq = DB::table('supplies')
            ->select(DB::raw('MAX(CAST(SUBSTRING_INDEX(stock_no, "-", -1) AS UNSIGNED)) as max_seq'))
            ->value('max_seq');

        $seq = ($maxSeq ?? 0) + 1;

        return sprintf('STK-%03d', $seq);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function requisitions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Requisition::class,
            RequisitionItem::class,
            'supply_id',
            'id',
            'id',
            'requisition_id'
        );
    }

    public function requisitionItems(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function stockInRecords(): HasMany
    {
        return $this->hasMany(StockInRecord::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    /**
     * Process a stock-in transaction: update quantity and recalculate Moving Average Cost (MAC).
     *
     * MAC formula:
     * New MAC = ((Current Qty × Current MAC) + (New Qty × New Unit Cost)) / (Current Qty + New Qty)
     *
     * Also updates unit_cost (alias of MAC) and total_cost (quantity × unit_cost).
     *
     * @param int    $quantity         Quantity received
     * @param float  $unitCost         Unit cost of received items
     * @param string|null|\DateTime $referenceNumber  Optional reference (e.g. IAR no.)
     * @param string|\DateTime|null $dateReceived     Optional date received
     */
    public function receiveStock(int $quantity, float $unitCost, string|null|\DateTime $referenceNumber = null, string|null|\DateTime $dateReceived = null): void
    {
        $oldQty = $this->current_stock;
        $oldMac = (float) ($this->unit_cost ?? $this->average_unit_cost ?? 0);

        // Calculate new moving average cost
        if ($oldQty + $quantity > 0) {
            $totalCost = ($oldQty * $oldMac) + ($quantity * $unitCost);
            $newMac = $totalCost / ($oldQty + $quantity);
        } else {
            $newMac = $unitCost;
        }

        $newQty = $oldQty + $quantity;
        $newMac = round($newMac, 2);

        $this->current_stock      = $newQty;
        $this->unit_cost          = $newMac;           // single source of MAC
        $this->average_unit_cost  = $newMac;           // kept in sync for DB backward compatibility
        $this->total_cost         = $newQty * $newMac;

        // Track last receipt info for quick reference
        if ($referenceNumber !== null) {
            $this->latest_reference_number = (string) $referenceNumber;
        }
        if ($dateReceived !== null) {
            $this->last_received_date = $dateReceived instanceof \DateTime ? $dateReceived->format('Y-m-d') : $dateReceived;
        }

        $this->save();
    }

    /**
     * Process a stock-out (issue) transaction: reduce quantity and total_cost.
     *
     * Per accounting rules, the unit cost (MAC) is NOT changed during stock-out.
     * Only current_stock and total_cost are reduced.
     *
     * @param int    $quantity         Quantity to issue
     * @param string|null $referenceNumber  Optional reference (e.g. RIS no.)
     */
    public function issueStock(int $quantity, string|null $referenceNumber = null): void
    {
        $this->current_stock = $this->current_stock - $quantity;
        $this->total_cost    = $this->current_stock * (float) $this->unit_cost;

        if ($referenceNumber !== null) {
            $this->reference_number = $referenceNumber;
        }

        $this->save();
    }
}
