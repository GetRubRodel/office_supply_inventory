<?php

namespace App\Models;

use App\Notifications\RisCancelled;
use App\Support\CurrentUser;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class Requisition extends Model
{
    const DIVISION_MAP = [
        'Administrative' => ['code' => 'ASD'],
        'Promotion'      => ['code' => 'PAD'],
        'Investigation'  => ['code' => 'INV'],
        'Legal'          => ['code' => 'LD'],
    ];

    const STATUS_REQUESTED       = 'requested';
    const STATUS_APPROVED        = 'approved';
    const STATUS_ISSUED          = 'issued';
    const STATUS_PENDING_RECEIPT = 'pending_receipt';
    const STATUS_RECEIVED        = 'received';
    const STATUS_CANCELLED       = 'cancelled';

    const STATUS_TRANSITIONS = [
        self::STATUS_REQUESTED       => [self::STATUS_APPROVED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED        => [self::STATUS_ISSUED, self::STATUS_CANCELLED],
        // `issued` may go straight to `received` for RIS rows that were issued
        // before the pending_receipt step existed (or whose auto-transition
        // failed), so the requester can still acknowledge receipt.
        self::STATUS_ISSUED          => [self::STATUS_PENDING_RECEIPT, self::STATUS_RECEIVED, self::STATUS_CANCELLED],
        self::STATUS_PENDING_RECEIPT => [self::STATUS_RECEIVED, self::STATUS_CANCELLED],
        self::STATUS_RECEIVED        => [],
        self::STATUS_CANCELLED       => [],
    ];

    protected $fillable = [
        'user_id',
        'entity_name',
        'fund_cluster',
        'division',
        'division_code',
        'sequence_number',
        'rc_sequence',
        'responsibility_center_code',
        'office',
        'ris_no',
        'purpose',
        'status',
        'requested_by_name',
        'requested_by_designation',
        'requested_by_date',
        'approved_by_name',
        'approved_by_designation',
        'approved_by_date',
        'issued_by_name',
        'issued_by_designation',
        'issued_by_date',
        'received_by_name',
        'received_by_designation',
        'received_by_date',
        'cancelled_by_name',
        'cancelled_by_designation',
        'cancelled_by_date',
        'cancellation_remarks',
    ];

    protected $casts = [
        'requested_by_date' => 'date',
        'approved_by_date' => 'date',
        'issued_by_date' => 'date',
        'received_by_date' => 'date',
        'cancelled_by_date' => 'date',
        'sequence_number' => 'integer',
        'rc_sequence' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_REQUESTED,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Requisition $requisition) {
            // Set default status if not provided
            if (! $requisition->status) {
                $requisition->status = self::STATUS_REQUESTED;
            }

            // Auto-populate requested_by from the authenticated user.
            //
            // The Designation is the requester's official plantilla POSITION
            // read from their profile (`users.position`) — never the RBAC
            // `role`, which is an internal permission label (e.g. 'staff') and
            // is not a valid designation on an official RIS form. The position
            // of the RIS owner is used when it differs from the actor, so
            // records created on another user's behalf stay accurate.
            $user = CurrentUser::get();
            if ($user) {
                $requester = $requisition->user ?? $user;

                $requisition->requested_by_name ??= $requester->name;
                $requisition->requested_by_designation ??= $requester->position;
                $requisition->requested_by_date ??= now();
            }

            // Auto-set division_code based on division name
            if ($requisition->division && isset(self::DIVISION_MAP[$requisition->division])) {
                $requisition->division_code = self::DIVISION_MAP[$requisition->division]['code'];
            }

            $year = now()->year;
            $code = $requisition->division_code ?? 'XXX';

            // Generate RIS number with global sequence across ALL divisions — RIS-YYYY-####
            $maxSeq = self::whereYear('created_at', $year)->max('sequence_number');
            $requisition->sequence_number = ($maxSeq ?? 0) + 1;
            $requisition->ris_no = sprintf('RIS-%s-%04d', $year, $requisition->sequence_number);

            // Generate Responsibility Center code with per-division sequence — RC-XXX-###
            $maxRcSeq = self::where('division_code', $code)->max('rc_sequence');
            $requisition->rc_sequence = ($maxRcSeq ?? 0) + 1;
            $requisition->responsibility_center_code = sprintf('RC-%s-%03d', $code, $requisition->rc_sequence);
        });

        static::updating(function (Requisition $requisition) {
            // If division changed, re-generate RC code with the new division's sequence
            if ($requisition->isDirty('division') && $requisition->division && isset(self::DIVISION_MAP[$requisition->division])) {
                $requisition->division_code = self::DIVISION_MAP[$requisition->division]['code'];

                $code = $requisition->division_code;

                $maxRcSeq = self::where('division_code', $code)
                    ->where('id', '!=', $requisition->id)
                    ->max('rc_sequence');

                $requisition->rc_sequence = ($maxRcSeq ?? 0) + 1;
                $requisition->responsibility_center_code = sprintf('RC-%s-%03d', $code, $requisition->rc_sequence);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function supplies(): HasManyThrough
    {
        return $this->hasManyThrough(
            Supply::class,
            RequisitionItem::class,
            'requisition_id',
            'id',
            'id',
            'supply_id'
        );
    }

    /**
     * Resolve the Requesting Party designation.
     *
     * The requester's `position` profile field is the single source of truth
     * for the Designation, so the form, view page, print sheet and any
     * download/export all render the same value. Falls back to the stored
     * audit snapshot when the profile has no position on file yet, and never
     * falls back to the RBAC role.
     */
    public function requesterPosition(): ?string
    {
        return $this->user?->position ?: $this->requested_by_designation;
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($targetStatus, $allowed, true);
    }

    /**
     * Move the RIS to its next workflow status.
     *
     * `$cancellationRemarks` is only meaningful for the cancel transition:
     * the free-text reason a Division Chief or Administrator provides, which
     * is persisted on the record and shared with the original requester in
     * the cancellation notification. It stays optional so existing
     * programmatic cancellation paths keep working unchanged.
     */
    public function transitionTo(string $targetStatus, ?string $cancellationRemarks = null): void
    {
        if (! $this->canTransitionTo($targetStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from '{$this->status}' to '{$targetStatus}'."
            );
        }

        $user = CurrentUser::get();

        // Enforce authorization for approval transition
        if ($targetStatus === self::STATUS_APPROVED) {
            abort_unless(
                $user && $user->canApproveRequisition($this),
                403
            );
        }

        // Enforce authorization for issue transition
        if ($targetStatus === self::STATUS_ISSUED) {
            abort_unless($user && $user->canIssueRequisition($this), 403);
        }

        // Enforce authorization for pending_receipt transition (only issue authorized users)
        if ($targetStatus === self::STATUS_PENDING_RECEIPT) {
            abort_unless($user && $user->canIssueRequisition($this), 403);
        }

        // Enforce authorization for receive transition (only original requester with permission)
        if ($targetStatus === self::STATUS_RECEIVED) {
            abort_unless(
                $user && $this->user_id === $user->id && $user->hasPermissionTo('requisitions.receive'),
                403
            );
        }

        // Enforce authorization for cancel transition (Admin, Division Chief for own division)
        if ($targetStatus === self::STATUS_CANCELLED) {
            abort_unless(
                $user && $user->canCancelRequisition($this),
                403
            );
        }

        // Map statuses to their audit trail field prefixes
        $auditMap = [
            self::STATUS_APPROVED  => 'approved_by',
            self::STATUS_ISSUED    => 'issued_by',
            self::STATUS_RECEIVED  => 'received_by',
            self::STATUS_CANCELLED => 'cancelled_by',
        ];

        $data = ['status' => $targetStatus];

        if ($user && isset($auditMap[$targetStatus])) {
            $prefix = $auditMap[$targetStatus];
            $data[$prefix . '_name'] = $user->name;
            // Designations are the actor's plantilla position from their
            // profile, never the RBAC role — mirrors the PR module.
            $data[$prefix . '_designation'] = $user->position;
            $data[$prefix . '_date'] = now();
        }

        if ($targetStatus === self::STATUS_CANCELLED && filled($cancellationRemarks)) {
            $data['cancellation_remarks'] = trim($cancellationRemarks);
        }

        $this->update($data);

        // Notify the original requester automatically whenever the RIS is
        // cancelled. Centralized here (instead of in each Filament action) so
        // every cancellation path — table action, view page, or any future
        // bulk/API flow — produces exactly one notification per cancellation.
        if ($targetStatus === self::STATUS_CANCELLED) {
            $this->notifyRequesterOfCancellation();
        }
    }

    /**
     * Send the cancellation notification to the original requester, guarded
     * against duplicates for the same cancellation event.
     *
     * The guard keys on the cancellation date plus the cancellation remarks:
     * re-firing the same cancellation cannot create a second notification.
     * (A RIS can only ever be cancelled once — `cancelled` is a terminal
     * status — so the guard is defence in depth against double-submits and
     * repeated notification dispatch.)
     */
    protected function notifyRequesterOfCancellation(): void
    {
        if (! $this->user) {
            return;
        }

        $cancelledAt = $this->cancelled_by_date?->toDateString();

        $alreadyNotified = $this->user->notifications()
            ->where('type', RisCancelled::class)
            ->where('data->requisition_id', $this->id)
            ->when($cancelledAt, fn ($query) => $query->where('data->cancelled_at', $cancelledAt))
            ->where('data->cancellation_remarks', (string) $this->cancellation_remarks)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $this->user->notify(new RisCancelled(
            $this,
            (string) $this->cancellation_remarks,
            (string) $this->cancelled_by_name,
        ));
    }

    public function scopeRequested($query)
    {
        return $query->where('status', self::STATUS_REQUESTED);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * RIS documents approved by the Administrator or Division Chief that are
     * now pending the Supply Officer's issuance action.
     *
     * A RIS is counted here only while it sits at the `approved` status. As
     * soon as it is issued the status moves on (issued/pending_receipt) and it
     * automatically drops out; cancelled and received RIS never match. The
     * count is row-based, so a single RIS transaction — regardless of how many
     * line items it carries — can never be counted twice.
     */
    public function scopeAwaitingIssuance($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function scopePendingReceipt($query)
    {
        return $query->where('status', self::STATUS_PENDING_RECEIPT);
    }

    public function scopeReceived($query)
    {
        return $query->where('status', self::STATUS_RECEIVED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_REQUESTED,
            self::STATUS_APPROVED,
            self::STATUS_ISSUED,
            self::STATUS_PENDING_RECEIPT,
        ]);
    }
}
