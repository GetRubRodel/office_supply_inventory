<?php

namespace App\Models;

use App\Models\Concerns\HasEditingLock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestForQuotation extends Model
{
    use HasEditingLock;

    protected $fillable = [
        'rfq_no',
        'purchase_request_id',
        'date',
        'supplier_id',
        'company_name',
        'address',
        'contact_number',
        'tin',
        'canvassed_by_name',
        'canvassed_by_designation',
        'quoted_by_name',
        'quoted_by_supplier',
        'approved_by_name',
        'approved_by_designation',
    ];

    protected $casts = [
        'date' => 'date',
        'editing_started_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (RequestForQuotation $rfq) {
            $year = now()->year;
            $maxSeq = self::whereYear('created_at', $year)->max('id');
            $nextId = ($maxSeq ?? 0) + 1;
            $rfq->rfq_no = sprintf('RFQ-%s-%04d', $year, $nextId);
        });
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class, 'rfq_id');
    }
}
