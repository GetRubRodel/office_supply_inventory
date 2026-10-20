<?php

namespace App\Models;

use App\Models\Concerns\HasEditingLock;
use App\Support\BacResolutionText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class BacResolution extends Model
{
    use HasEditingLock;

    const STATUS_DRAFT = 'draft';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED = 'rejected';

    /**
     * Displayed whenever no Abstract of Bids and Canvass (ABC) is available
     * to be converted into a BAC Resolution.
     */
    public const NO_ELIGIBLE_ABC_MESSAGE = 'No Abstract of Bids and Canvass is available. Please create an ABC before creating a BAC Resolution.';

    /**
     * Divisions offered by the first blank of the resolution.
     *
     * @see BacResolutionText::DIVISIONS
     */
    public const DIVISIONS = BacResolutionText::DIVISIONS;

    /**
     * Every entry here must exist as a column on `bac_resolutions`.
     *
     * Note: `further_resolved` is intentionally absent. The "FURTHER RESOLVED"
     * clause is no longer part of the resolution, so the field is obsolete and
     * must never be mass-assigned or persisted again.
     */
    protected $fillable = [
        'abc_id',
        'iar_id',
        'resolution_no',
        // First blank (Division Select) and third blank (Budget Amount input).
        // The remaining three blanks are always derived from these two.
        'division',
        'budget_amount',
        'title',
        'preamble',
        'operative_part',
        'closing',
        'date',
        'place',
        'chairperson_name',
        'chairperson_designation',
        'vice_chairperson_name',
        'vice_chairperson_designation',
        'member1_name',
        'member1_designation',
        'member2_name',
        'member2_designation',
        'member3_name',
        'member3_designation',
        'approved_by_name',
        'approved_by_designation',
        // Snapshot of the source document (auto-populated, not user-editable)
        'abc_no',
        'iar_no',
        'po_no',
        'supplier_name',
        'inspection_date',
        'acceptance_date',
        'acceptance_details',
        'items_snapshot',
    ];

    protected $casts = [
        'date' => 'date',
        'inspection_date' => 'date',
        'acceptance_date' => 'date',
        'items_snapshot' => 'array',
        'editing_started_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BacResolution $bac) {
            $bac->resolution_no = static::generateNextResolutionNo();
            $bac->assertValidAbcSource();
            $bac->hydrateFromAbc();
            $bac->hydrateResolutionTemplate();
            $bac->assertValidResolutionBlanks();
        });

        static::updating(function (BacResolution $bac) {
            // The source ABC cannot be swapped once the record exists; if it
            // somehow is, re-validate and re-hydrate the snapshot.
            if ($bac->isDirty('abc_id')) {
                $bac->assertValidAbcSource();
                $bac->hydrateFromAbc();
            }

            $bac->hydrateResolutionTemplate();

            if ($bac->isDirty(['division', 'budget_amount'])) {
                $bac->assertValidResolutionBlanks();
            }
        });
    }

    /**
     * Generate the next available unique resolution number.
     *
     * Queries the highest existing sequence from resolution_no values directly,
     * then checks for existence before returning to avoid duplicates.
     */
    protected static function generateNextResolutionNo(): string
    {
        $year = now()->year;
        $prefix = "BAC RESO-{$year}-";
        $maxAttempts = 50;
        $attempt = 0;

        do {
            $attempt++;

            $lastResolution = self::where('resolution_no', 'like', "{$prefix}%")
                ->orderByRaw('CAST(SUBSTRING(resolution_no, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
                ->value('resolution_no');

            $nextSeq = 1;
            if ($lastResolution) {
                $seqPart = substr($lastResolution, strlen($prefix));
                if (is_numeric($seqPart)) {
                    $nextSeq = ((int) $seqPart) + 1;
                }
            }

            $candidate = sprintf('%s%04d', $prefix, $nextSeq);

            if (!self::where('resolution_no', $candidate)->exists()) {
                return $candidate;
            }

            if ($attempt >= $maxAttempts) {
                throw new \RuntimeException(
                    'Unable to generate a unique BAC Resolution number after ' . $maxAttempts . ' attempts.'
                );
            }

            $nextSeq++;
        } while (true);
    }

    /**
     * Get the full display resolution number.
     */
    public function abc(): BelongsTo
    {
        return $this->belongsTo(AbstractOfCanvass::class, 'abc_id');
    }

    /**
     * BAC member details are sourced live from the originating ABC's
     * "Committee on Awards" section. The BAC never stores its own member
     * data — the ABC record is the source of truth.
     *
     * These accessors shadow the legacy chairperson and member columns so
     * every output (form, view, print, download, table) shows the same
     * ABC-sourced data.
     */
    public function getChairpersonNameAttribute(): ?string
    {
        return $this->abc?->chairman_name;
    }

    public function getChairpersonDesignationAttribute(): ?string
    {
        return $this->abc?->chairman_designation;
    }

    public function getViceChairpersonNameAttribute(): ?string
    {
        return $this->abc?->vice_chairman_name;
    }

    public function getViceChairpersonDesignationAttribute(): ?string
    {
        return $this->abc?->vice_chairman_designation;
    }

    public function getMember1NameAttribute(): ?string
    {
        return $this->abc?->member1_name;
    }

    public function getMember1DesignationAttribute(): ?string
    {
        return $this->abc?->member1_designation;
    }

    public function getMember2NameAttribute(): ?string
    {
        return $this->abc?->member2_name;
    }

    public function getMember2DesignationAttribute(): ?string
    {
        return $this->abc?->member2_designation;
    }

    public function getMember3NameAttribute(): ?string
    {
        return $this->abc?->member3_name;
    }

    public function getMember3DesignationAttribute(): ?string
    {
        return $this->abc?->member3_designation;
    }

    /**
     * Approval details are sourced live from the originating ABC's
     * "Approved by" section, which in turn sources them from the RFQ and
     * the approved Purchase Request. The BAC never stores its own approval
     * data.
     *
     * These accessors shadow the legacy approved_by_* columns so every
     * output (form, view, print, download, table) shows the same
     * ABC-sourced data.
     */
    public function getApprovedByNameAttribute(): ?string
    {
        return $this->abc?->approved_by_name;
    }

    public function getApprovedByDesignationAttribute(): ?string
    {
        return $this->abc?->approved_by_designation;
    }

    /**
     * The winning supplier is sourced live from the originating ABC's bid
     * data — the supplier with the lowest valid total quotation. The BAC
     * never stores its own supplier data.
     */
    public function getSupplierNameAttribute(): ?string
    {
        return $this->abc?->winningSupplierName();
    }

    /**
     * Per-item snapshot sourced live from the originating ABC using the
     * winning supplier's bid prices. The BAC never stores its own item
     * data — the ABC record is the source of truth.
     *
     * Each entry mirrors the legacy snapshot shape consumed by the
     * Purchase Order conversion (stock_no, description, quantity, unit,
     * unit_cost, total).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getItemsSnapshotAttribute(): array
    {
        $abc = $this->abc;

        if (! $abc) {
            return [];
        }

        $priceColumn = $abc->winningPriceColumn();

        return $abc->items
            ->sortBy('item_number')
            ->values()
            ->map(function (AbcItem $item) use ($priceColumn) {
                $quantity = (int) ($item->quantity ?? 0);
                $unitCost = (float) ($item->{$priceColumn} ?? 0);

                return [
                    'stock_no' => null,
                    'description' => $item->description,
                    'quantity' => $quantity,
                    'unit' => $item->unit,
                    'unit_cost' => $unitCost,
                    'total' => round($quantity * $unitCost, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Legacy source relationship kept for historical records that were
     * created from an Inspection and Acceptance Report (IAR). New BAC
     * Resolutions are created from an Abstract of Bids and Canvass (ABC).
     */
    public function iar(): BelongsTo
    {
        return $this->belongsTo(InspectionAcceptanceReport::class, 'iar_id');
    }

    /**
     * Purchase Orders created from this BAC Resolution.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'bac_resolution_id');
    }

    /**
     * BAC Resolutions that have not yet been converted into a Purchase
     * Order — the source records for the PO badge. Cancelled and rejected
     * BAC Resolutions are never eligible.
     */
    public function scopeEligibleForPo(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_REJECTED])
            ->whereDoesntHave('purchaseOrders');
    }

    /**
     * Backend guard: a BAC Resolution MUST be created from an existing,
     * still-eligible ABC. Throws when the source is missing, already used,
     * or no longer exists. Runs on every create (and on abc_id change) so
     * direct URLs, API calls and manual form submissions are all blocked.
     */
    public function assertValidAbcSource(): void
    {
        if (! $this->abc_id) {
            throw ValidationException::withMessages([
                'abc_id' => 'A BAC Resolution must be created from an existing Abstract of Bids and Canvass (ABC).',
            ]);
        }

        $abc = $this->abc()->first();

        if (! $abc) {
            throw ValidationException::withMessages([
                'abc_id' => 'The selected Abstract of Bids and Canvass (ABC) no longer exists.',
            ]);
        }

        if ($abc->bacResolutions()->whereKeyNot($this->id)->exists()) {
            throw ValidationException::withMessages([
                'abc_id' => 'This Abstract of Bids and Canvass (ABC) has already been converted into a BAC Resolution.',
            ]);
        }
    }

    /**
     * Automatically retrieve and populate the relevant information from the
     * linked ABC: the ABC number snapshot. The winning supplier and the
     * per-item breakdown (description, quantity, unit, winning unit cost,
     * total amount) are sourced live through the supplier_name and
     * items_snapshot accessors, so they can never go stale.
     */
    public function hydrateFromAbc(): void
    {
        if (! $this->abc_id) {
            return;
        }

        $abc = $this->abc()->first();

        if (! $abc) {
            return;
        }

        $this->abc_no = $abc->abc_no;
    }

    /**
     * Write the canonical resolution template onto the record.
     *
     * The stored columns always hold the *tokenised* template
     * ({@see BacResolutionText::DIVISION_TOKEN} / {@see BUDGET_TOKEN}); the
     * concrete Division and Budget Amount are never written into them, which
     * keeps the form, view, print and download outputs consistent and makes
     * hardcoded values impossible.
     *
     * The obsolete "FURTHER RESOLVED" clause is deliberately absent from this
     * save path. It is not part of the document any more, so it is never
     * written again; legacy values are cleared once by the data migration
     * that introduced this template.
     */
    public function hydrateResolutionTemplate(): void
    {
        $this->title = BacResolutionText::titleTemplate();
        $this->preamble = BacResolutionText::preambleTemplate();
        $this->operative_part = BacResolutionText::operativeTemplate();
        $this->closing = BacResolutionText::closingTemplate();
    }

    /**
     * Backend guard for the two user-entered blanks.
     *
     * Blank 1 (Division) may only be one of the four selectable divisions and
     * Blank 3 (Budget Amount) may not exceed the column length. Blanks 2, 4
     * and 5 are derived and are therefore never accepted from user input.
     *
     * Presence is enforced by the form (`->required()`); a record saved
     * without them still renders correctly because unresolved blanks fall back
     * to {@see BacResolutionText::BLANK_PLACEHOLDER}.
     */
    public function assertValidResolutionBlanks(): void
    {
        $division = trim((string) $this->division);
        $budgetAmount = trim((string) $this->budget_amount);

        $messages = [];

        if ($division !== '' && ! in_array($division, self::DIVISIONS, true)) {
            $messages['division'] = 'The selected Division is not valid. Choose one of: '.implode(', ', self::DIVISIONS).'.';
        }

        if (mb_strlen($budgetAmount) > 255) {
            $messages['budget_amount'] = 'The budget amount may not be longer than 255 characters.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * The Division retrieved into blanks 2 and 4.
     */
    public function getResolvedDivision(): string
    {
        return trim((string) $this->division);
    }

    /**
     * The Budget Amount retrieved into blank 5.
     */
    public function getResolvedBudgetAmount(): string
    {
        return trim((string) $this->budget_amount);
    }

    /**
     * The date selected on the resolution DatePicker, formatted for
     * the closing statement.
     *
     * Returns a blank placeholder when no date has been chosen yet, so a
     * partially filled record still renders as a proper blank document.
     *
     * @see BacResolutionText::RESOLVED_DATE_FORMAT
     */
    public function getResolvedOn(): string
    {
        return BacResolutionText::resolvedOnLabel($this->date);
    }

    /**
     * The resolution body with every blank filled in.
     *
     * Shared by the Filament form preview, the view page, the print page and
     * the print-to-PDF download so all four are identical by construction.
     *
     * @return list<array{type: string, text: string}>
     */
    public function getResolutionBlocks(): array
    {
        return BacResolutionText::blocks(
            $this->getResolvedDivision(),
            $this->getResolvedBudgetAmount(),
            $this->date,
        );
    }

    /**
     * Grand total of the source document snapshot (sum of per-item totals).
     */
    public function getTotalAmount(): ?float
    {
        $items = is_array($this->items_snapshot) ? $this->items_snapshot : [];

        return round(array_sum(array_column($items, 'total')), 2);
    }

    public function getDisplayResolutionNo(): string
    {
        return $this->resolution_no;
    }
}
