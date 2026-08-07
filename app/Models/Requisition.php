<?php

namespace App\Models;

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
        self::STATUS_ISSUED          => [self::STATUS_PENDING_RECEIPT, self::STATUS_CANCELLED],
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

            // Auto-populate requested_by from the authenticated user
            $user = CurrentUser::get();
            if ($user) {
                $requisition->requested_by_name ??= $user->name;
                $requisition->requested_by_designation ??= $user->role;
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

        // Enforce authorization for approval transition
        if ($targetStatus === self::STATUS_APPROVED) {
            abort_unless(
                $user && $user->canApproveRequisition($this),
                403
            );
        }

        // Enforce authorization for issue transition
        if ($targetStatus === self::STATUS_ISSUED) {
            abort_unless($user && $user->canIssueItems(), 403);
        }

        // Enforce authorization for pending_receipt transition (only issue authorized users)
        if ($targetStatus === self::STATUS_PENDING_RECEIPT) {
            abort_unless($user && $user->canIssueItems(), 403);
        }

        // Enforce authorization for receive transition (only original requester)
        if ($targetStatus === self::STATUS_RECEIVED) {
            abort_unless($user && $this->user_id === $user->id, 403);
        }

        // Enforce authorization for cancel transition (Admin, Division Chief for own division)
        if ($targetStatus === self::STATUS_CANCELLED) {
            abort_unless(
                $user && $user->canApproveRequisition($this),
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
            $data[$prefix . '_designation'] = $user->role;
            $data[$prefix . '_date'] = now();
        }

        $this->update($data);
    }

    public function scopeRequested($query)
    {
        return $query->where('status', self::STATUS_REQUESTED);
    }

    public function scopeApproved($query)
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
