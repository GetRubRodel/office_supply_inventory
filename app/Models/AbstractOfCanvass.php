<?php

namespace App\Models;

use App\Models\Concerns\HasEditingLock;
use App\Services\AbstractOfCanvassService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class AbstractOfCanvass extends Model
{
    use HasEditingLock;

    protected $table = 'abstracts_of_canvass';

    const STATUS_DRAFT = 'draft';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED = 'rejected';

    /**
     * Displayed whenever no Request for Quotation is available
     * to be converted into an Abstract of Bids and Canvass.
     */
    public const NO_ELIGIBLE_RFQ_MESSAGE = 'No Request for Quotation is available. Please create an RFQ before creating an Abstract of Bids and Canvass.';

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
            $abc->assertValidRfqSource();
            $abc->assertValidDates();
            $abc->ensureCommitteeDesignations();
        });

        static::updating(function (AbstractOfCanvass $abc) {
            if ($abc->isDirty('rfq_id')) {
                $abc->assertValidRfqSource();
            }
            if ($abc->isDirty(['date_of_advertisement', 'date_of_opening'])) {
                $abc->assertValidDates();
            }
            $abc->ensureCommitteeDesignations();
        });
    }

    /**
     * The "Committee on Awards" designations are derived from each member's
     * role and are always persisted so downstream modules (e.g. BAC
     * Resolutions) can rely on the ABC record as the source of truth.
     * Existing values are never overwritten — only nulls are filled.
     */
    public function ensureCommitteeDesignations(): void
    {
        $designations = [
            'chairman_designation'      => 'Chairman',
            'vice_chairman_designation' => 'Vice Chairman',
            'member1_designation'       => 'Member',
            'member2_designation'       => 'Member',
            'member3_designation'       => 'Member',
        ];

        foreach ($designations as $column => $designation) {
            if (blank($this->{$column})) {
                $this->{$column} = $designation;
            }
        }
    }

    /**
     * Backend guard: an ABC MUST be created from an existing, still-eligible
     * (not yet converted) Request for Quotation. Throws when the source is
     * missing, already used, or no longer exists. Runs on every create (and
     * on rfq_id change) so direct URLs, API calls and manual form
     * submissions are all blocked.
     */
    public function assertValidRfqSource(): void
    {
        if (! $this->rfq_id) {
            throw ValidationException::withMessages([
                'rfq_id' => 'An Abstract of Bids and Canvass must be created from an existing Request for Quotation.',
            ]);
        }

        $rfq = RequestForQuotation::find($this->rfq_id);

        if (! $rfq) {
            throw ValidationException::withMessages([
                'rfq_id' => 'The selected Request for Quotation no longer exists.',
            ]);
        }

        if (self::where('rfq_id', $rfq->id)->whereKeyNot($this->id)->exists()) {
            throw ValidationException::withMessages([
                'rfq_id' => 'This Request for Quotation has already been converted into an Abstract of Bids and Canvass.',
            ]);
        }
    }

    /**
     * Backend guard: Date of Advertisement and Date of Opening must fall on
     * today or a future date. Any previous date — including dates from
     * previous months — is rejected even when submitted directly (API calls,
     * manual form submissions, etc.).
     *
     * Runs on every create and whenever either date changes on update, so
     * existing records with previously-valid dates remain editable.
     */
    public function assertValidDates(): void
    {
        $minDate = now()->startOfDay();

        foreach ([
            'date_of_advertisement' => 'Date of Advertisement',
            'date_of_opening' => 'Date of Opening',
        ] as $field => $label) {
            $value = $this->{$field};

            if ($value !== null && $value->lt($minDate)) {
                throw ValidationException::withMessages([
                    $field => "The {$label} must be today or a future date.",
                ]);
            }
        }
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    /**
     * Approval details are sourced live from the originating Request for
     * Quotation, which in turn sources them from the approved Purchase
     * Request (see RequestForQuotation::getApprovedByNameAttribute()).
     * The ABC never stores its own approval data.
     *
     * These accessors shadow the legacy approved_by_* columns so every output
     * (form, view, print, download, table) shows the same RFQ-sourced data.
     */
    public function getApprovedByNameAttribute(): ?string
    {
        return $this->rfq?->approved_by_name;
    }

    public function getApprovedByDesignationAttribute(): ?string
    {
        return $this->rfq?->approved_by_designation;
    }

    public function items(): HasMany
    {
        return $this->hasMany(AbcItem::class, 'abc_id');
    }

    public function winningSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'winning_supplier_id');
    }

    /**
     * BAC Resolutions created from this ABC.
     *
     * An ABC is considered "Converted to BAC Resolution" when at least one
     * BAC Resolution references it via this relationship.
     */
    public function bacResolutions(): HasMany
    {
        return $this->hasMany(BacResolution::class, 'abc_id');
    }

    /**
     * Whether this ABC has already been converted into a BAC Resolution.
     */
    public function isConvertedToBac(): bool
    {
        return $this->bacResolutions()->exists();
    }

    /**
     * Only ABCs that have not yet been converted into a BAC Resolution are
     * eligible to be used as the source document for a new BAC Resolution.
     * Cancelled and rejected ABCs are never eligible.
     */
    public function scopeEligibleForBacConversion(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_REJECTED])
            ->whereDoesntHave('bacResolutions');
    }

    /**
     * Index (0-2) of the supplier with the lowest *valid* (complete) total
     * quotation, resolved from the live bid data. Returns null when no
     * supplier has a usable complete bid (no items, or every bid is
     * incomplete).
     */
    public function computedWinningIndex(): ?int
    {
        $totals = AbstractOfCanvassService::completeBidTotals($this);
        $computedIndex = AbstractOfCanvassService::lowestTotalIndex(
            array_map(fn (?float $total): float => $total ?? 0.0, $totals)
        );

        if ($computedIndex !== null
            && ($totals[$computedIndex] ?? null) !== null
            && ($totals[$computedIndex] ?? 0) > 0) {
            return $computedIndex;
        }

        return null;
    }

    /**
     * Zero-based index (0-2) of the supplier column that holds the winning
     * bid prices for this ABC.
     *
     * Resolution order:
     *  1. Live bid data — the supplier with the lowest *valid* (complete)
     *     total quotation wins. This keeps the winner correct even when the
     *     stored winning_supplier_id / recommendation snapshot is missing
     *     or stale.
     *  2. The linked winning Supplier record (matched against the three
     *     supplier name columns), then the recommendation string — used as a
     *     fallback for legacy records with no usable bid data.
     *  3. The first supplier column as a last resort.
     */
    public function winningSupplierIndex(): int
    {
        $computedIndex = $this->computedWinningIndex();

        if ($computedIndex !== null) {
            return $computedIndex;
        }

        $supplierNames = [
            trim((string) $this->supplier1_name),
            trim((string) $this->supplier2_name),
            trim((string) $this->supplier3_name),
        ];

        $winner = $this->winningSupplier;

        if ($winner) {
            $matchedIndex = array_search(
                mb_strtolower(trim($winner->name)),
                array_map('mb_strtolower', $supplierNames)
            );

            if ($matchedIndex !== false) {
                return (int) $matchedIndex;
            }
        }

        if (! empty($this->recommendation)) {
            $matchedIndex = array_search(
                mb_strtolower(trim($this->recommendation)),
                array_map('mb_strtolower', $supplierNames)
            );

            if ($matchedIndex !== false) {
                return (int) $matchedIndex;
            }
        }

        return 0;
    }

    /**
     * The abc_items price column that holds the winning bid prices.
     */
    public function winningPriceColumn(): string
    {
        return 'supplier' . ($this->winningSupplierIndex() + 1) . '_price';
    }

    /**
     * Display name of the winning supplier.
     *
     * When a winner can be computed from the live bid data, the name comes
     * from the same supplier column that holds the winning prices — so the
     * name and the prices can never disagree. Legacy records with no usable
     * bid data fall back to the linked Supplier record, then the
     * recommendation string.
     */
    public function winningSupplierName(): ?string
    {
        $supplierNames = [
            trim((string) $this->supplier1_name),
            trim((string) $this->supplier2_name),
            trim((string) $this->supplier3_name),
        ];

        $computedIndex = $this->computedWinningIndex();

        if ($computedIndex !== null
            && isset($supplierNames[$computedIndex])
            && $supplierNames[$computedIndex] !== '') {
            return $supplierNames[$computedIndex];
        }

        return $this->winningSupplier?->name
            ?? (trim((string) $this->recommendation) ?: null);
    }
}
