<?php

namespace App\Models;

use App\Support\CurrentUser;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Rpci extends Model
{
    protected $fillable = [
        'report_no',
        'inventory_type',
        'report_date',
        'fund_cluster',
        'accountable_person',
        'accountable_position',
        'accountability_date',
        'chairman_name',
        'member_one_name',
        'member_two_name',
        'approved_by',
        'approved_position',
        'verified_by',
        'verified_position',
        'remarks',
        'created_by',
        'status',
        'user_id',
    ];

    protected $casts = [
        'report_date' => 'date',
        'accountability_date' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Rpci $rpci) {
            if (empty($rpci->report_no)) {
                $rpci->report_no = static::generateReportNo();
            }
            if (empty($rpci->user_id)) {
                $rpci->user_id = CurrentUser::id();
            }
            if (empty($rpci->created_by)) {
                $rpci->created_by = CurrentUser::get()?->name;
            }
        });
    }

    public static function generateReportNo(): string
    {
        $year = now()->format('Y');
        $maxSeq = DB::table('rpcis')
            ->where('report_no', 'like', "RPCI-{$year}-%")
            ->select(DB::raw('MAX(CAST(SUBSTRING_INDEX(report_no, "-", -1) AS UNSIGNED)) as max_seq'))
            ->value('max_seq');

        $seq = ($maxSeq ?? 0) + 1;

        return sprintf('RPCI-%s-%04d', $year, $seq);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RpciItem::class)->orderBy('sort_order');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get report date formatted as "As of Month Day, Year"
     */
    public function getFormattedReportDateAttribute(): string
    {
        if (! $this->report_date) {
            return '';
        }

        return 'As of ' . $this->report_date->format('F j, Y');
    }

    /**
     * Check if the report is finalized (cannot be edited).
     */
    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }

    /**
     * Alias for backward compatibility.
     */
    public function isCompleted(): bool
    {
        return $this->isFinalized();
    }
}
