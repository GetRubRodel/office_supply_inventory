<?php

namespace App\Models;

use App\Models\Concerns\HasEditingLock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbstractOfCanvass extends Model
{
    use HasEditingLock;

    protected $table = 'abstracts_of_canvass';

    protected $fillable = [
        'abc_no',
        'rfq_id',
        'date_of_advertisement',
        'date_of_opening',
        'supplier1_name',
        'supplier2_name',
        'supplier3_name',
        'chairman_name',
        'chairman_designation',
        'vice_chairman_name',
        'vice_chairman_designation',
        'member1_name',
        'member1_designation',
        'member2_name',
        'member2_designation',
        'member3_name',
        'member3_designation',
        'approved_by_name',
        'approved_by_designation',
        'recommendation',
        'winning_supplier_id',
    ];

    protected $casts = [
        'date_of_advertisement' => 'date',
        'date_of_opening' => 'date',
        'editing_started_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AbstractOfCanvass $abc) {
            $year = now()->year;
            $maxSeq = self::whereYear('created_at', $year)->max('id');
            $nextId = ($maxSeq ?? 0) + 1;
            $abc->abc_no = sprintf('ABC-%s-%04d', $year, $nextId);
        });
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AbcItem::class, 'abc_id');
    }

    public function winningSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'winning_supplier_id');
    }
}
