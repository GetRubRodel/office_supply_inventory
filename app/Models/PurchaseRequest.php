<?php

namespace App\Models;

use App\Support\CurrentUser;

use App\Models\Concerns\HasEditingLock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    use HasEditingLock;

    const STATUS_DRAFT      = 'draft';
    const STATUS_FOR_REVIEW = 'for_review';
    const STATUS_APPROVED   = 'approved';
    const STATUS_REJECTED   = 'rejected';
    const STATUS_CANCELLED  = 'cancelled';

    const STATUS_TRANSITIONS = [
        self::STATUS_DRAFT      => [self::STATUS_FOR_REVIEW, self::STATUS_CANCELLED],
        self::STATUS_FOR_REVIEW => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED   => [self::STATUS_CANCELLED],
        self::STATUS_REJECTED   => [self::STATUS_DRAFT],
        self::STATUS_CANCELLED  => [],
    ];

    protected $fillable = [
        'user_id',
        'pr_no',
        'date',
        'office_division',
        'rc_code',
        'code',
        'name_of_project',
        'purpose',
        'source_of_fund',
        'approved_budget',
        'requested_by_name',
        'requested_by_designation',
        'approved_by_name',
        'approved_by_designation',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_budget' => 'decimal:2',
        'editing_started_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PurchaseRequest $pr) {
            $year = now()->year;

            $maxSeq = self::whereYear('created_at', $year)->max('id');

            $nextId = ($maxSeq ?? 0) + 1;
            $pr->pr_no = sprintf('PR-%s-%04d', $year, $nextId);

            if (is_null($pr->user_id) && CurrentUser::check()) {
                $pr->user_id = CurrentUser::id();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($targetStatus, $allowed, true);
    }

    public function transitionTo(string $targetStatus): void
    {
        if (! $this->canTransitionTo($targetStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from '{$this->status}' to '{$targetStatus}'."
            );
        }

        $user = CurrentUser::get();

        // Enforce authorization for approval-related transitions
        if (in_array($targetStatus, [self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
            abort_unless($user && $user->canApprove(), 403);
        }

        // Enforce authorization for cancel transition (Admin, Regional Director)
        if ($targetStatus === self::STATUS_CANCELLED) {
            abort_unless(
                $user && ($user->isAdmin() || $user->isRegionalDirector()),
                403
            );
        }

        $data = ['status' => $targetStatus];

        if ($user) {
            // Record the requester when submitting for review
            if ($targetStatus === self::STATUS_FOR_REVIEW && empty($this->requested_by_name)) {
                $data['requested_by_name'] = $user->name;
                $data['requested_by_designation'] = $user->role;
            }

            // Record the approver when approving
            if ($targetStatus === self::STATUS_APPROVED) {
                $data['approved_by_name'] = $user->name;
                $data['approved_by_designation'] = $user->role;
            }
        }

        $this->update($data);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_FOR_REVIEW);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }
}
