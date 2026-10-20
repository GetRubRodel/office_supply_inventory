<?php

namespace App\Models;

use App\Models\Concerns\HasEditingLock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class RequestForQuotation extends Model
{
    use HasEditingLock;

    const STATUS_DRAFT = 'draft';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED = 'rejected';

    /**
     * Displayed whenever no approved Purchase Request is available
     * to be converted into a Request for Quotation.
     */
    public const NO_ELIGIBLE_PR_MESSAGE = 'No approved Purchase Request is available. Please create and approve a Purchase Request before creating a Request for Quotation.';

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
            $rfq->assertValidPrSource();
        });

        static::updating(function (RequestForQuotation $rfq) {
            if ($rfq->isDirty('purchase_request_id')) {
                $rfq->assertValidPrSource();
            }
        });
    }

    /**
     * Backend guard: an RFQ MUST be created from an existing, still-eligible
     * (approved and not yet converted) Purchase Request. Throws when the
     * source is missing, not approved, already used, or no longer exists.
     * Runs on every create (and on purchase_request_id change) so direct
     * URLs, API calls and manual form submissions are all blocked.
     */
    public function assertValidPrSource(): void
    {
        if (! $this->purchase_request_id) {
            throw ValidationException::withMessages([
                'purchase_request_id' => 'A Request for Quotation must be created from an existing Purchase Request.',
            ]);
        }

        $pr = PurchaseRequest::find($this->purchase_request_id);

        if (! $pr) {
            throw ValidationException::withMessages([
                'purchase_request_id' => 'The selected Purchase Request no longer exists.',
            ]);
        }

        if ($pr->status !== PurchaseRequest::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'purchase_request_id' => 'The selected Purchase Request must be approved before it can be converted into a Request for Quotation.',
            ]);
        }

        if (self::where('purchase_request_id', $pr->id)->whereKeyNot($this->id)->exists()) {
            throw ValidationException::withMessages([
                'purchase_request_id' => 'This Purchase Request has already been converted into a Request for Quotation.',
            ]);
        }
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * Approval details are sourced live from the originating Purchase Request.
     * The RFQ never stores its own approval data — the signatory is always the
     * approver recorded on the source PR (see PurchaseRequest::transitionTo()).
     *
     * These accessors shadow the legacy approved_by_* columns so every output
     * (form, view, print, download, table) shows the same PR-sourced data.
     */
    public function getApprovedByNameAttribute(): ?string
    {
        return $this->purchaseRequest?->approved_by_name;
    }

    public function getApprovedByDesignationAttribute(): ?string
    {
        return $this->purchaseRequest?->approved_by_designation;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class, 'rfq_id');
    }

    /**
     * Abstracts of Bids and Canvass created from this Request for Quotation.
     *
     * An RFQ is considered "Converted to ABC" when at least one ABC
     * references it via this relationship.
     */
    public function abstractsOfCanvass(): HasMany
    {
        return $this->hasMany(AbstractOfCanvass::class, 'rfq_id');
    }

    /**
     * Requests for Quotation that have not yet been converted into an
     * Abstract of Bids and Canvass — the source records for the ABC badge.
     * Cancelled and rejected RFQs are never eligible.
     */
    public function scopeEligibleForAbc(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_REJECTED])
            ->whereDoesntHave('abstractsOfCanvass');
    }
}
