<?php

namespace App\Models;

use App\Notifications\PrRejected;
use App\Support\CurrentUser;

use App\Models\Concerns\HasEditingLock;
use Illuminate\Database\Eloquent\Builder;
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
        'rejection_reason',
        'rejected_by_name',
        'rejected_by_designation',
        'rejected_at',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_budget' => 'decimal:2',
        'editing_started_at' => 'datetime',
        'rejected_at' => 'datetime',
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

    /**
     * Requests for Quotation created from this Purchase Request.
     *
     * A PR is considered "Converted to RFQ" when at least one RFQ references
     * it via this relationship.
     */
    public function requestForQuotations(): HasMany
    {
        return $this->hasMany(RequestForQuotation::class, 'purchase_request_id');
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($targetStatus, $allowed, true);
    }

    public function transitionTo(string $targetStatus, ?string $rejectionReason = null): void
    {
        if (! $this->canTransitionTo($targetStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from '{$this->status}' to '{$targetStatus}'."
            );
        }

        $user = CurrentUser::get();

        // Enforce authorization for submit-for-review transition
        // (only the original requester or the Administrator may submit).
        if ($targetStatus === self::STATUS_FOR_REVIEW) {
            abort_unless($user && $user->canSubmitPurchaseRequest($this), 403);
        }

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

        // Require rejection reason when rejecting
        if ($targetStatus === self::STATUS_REJECTED && empty($rejectionReason)) {
            abort(422, 'Rejection remarks are required.');
        }

        $data = ['status' => $targetStatus];

        if ($user) {
            // Record the requester when submitting for review. The position
            // comes from the requester's profile (`position`), never the role.
            if ($targetStatus === self::STATUS_FOR_REVIEW) {
                if (empty($this->requested_by_name)) {
                    $data['requested_by_name'] = $user->name;
                }
                if (empty($this->requested_by_designation)) {
                    $data['requested_by_designation'] = $this->user?->position ?? $user->position;
                }
            }

            // Record the approver when approving
            if ($targetStatus === self::STATUS_APPROVED) {
                $data['approved_by_name'] = $user->name;
                $data['approved_by_designation'] = $user->position;
            }

            // Record rejection details
            if ($targetStatus === self::STATUS_REJECTED) {
                $data['rejection_reason'] = $rejectionReason;
                $data['rejected_by_name'] = $user->name;
                $data['rejected_by_designation'] = $user->position;
                $data['rejected_at'] = now();
            }
        }

        $this->update($data);

        // Notify the original requester automatically whenever the PR is
        // rejected. Centralized here (instead of in each Filament action) so
        // every rejection path — table action, view page, or any future
        // bulk/API flow — produces exactly one notification per rejection.
        if ($targetStatus === self::STATUS_REJECTED) {
            $this->notifyRequesterOfRejection();
        }
    }

    /**
     * Send the rejection notification to the original requester, guarded
     * against duplicates for the same rejection event.
     *
     * The guard keys on the rejection timestamp (`rejected_at`) plus the
     * rejection reason: re-firing the same rejection cannot create a second
     * notification, while a new rejection (after revise → resubmit) carries
     * a new timestamp and/or reason and is notified normally.
     */
    protected function notifyRequesterOfRejection(): void
    {
        if (! $this->user) {
            return;
        }

        $rejectedAt = $this->rejected_at?->toDateTimeString();

        $alreadyNotified = $this->user->notifications()
            ->where('type', PrRejected::class)
            ->where('data->purchase_request_id', $this->id)
            ->when($rejectedAt, fn ($query) => $query->where('data->rejected_at', $rejectedAt))
            ->where('data->rejection_reason', (string) $this->rejection_reason)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $this->user->notify(new PrRejected(
            $this,
            (string) $this->rejection_reason,
            (string) $this->rejected_by_name,
        ));
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

    /**
     * Approved Purchase Requests that have not yet been converted into a
     * Request for Quotation — the source records for the RFQ badge.
     */
    public function scopeEligibleForRfq(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_APPROVED)
            ->whereDoesntHave('requestForQuotations');
    }
}
