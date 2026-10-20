<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Validation\ValidationException;

class InspectionAcceptanceReport extends Model
{

    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_STOCKED_IN = 'stocked_in';

    /**
     * Displayed whenever no Purchase Order is available
     * to be converted into an Inspection and Acceptance Report.
     */
    public const NO_ELIGIBLE_PO_MESSAGE = 'No Purchase Order is available. Please create a Purchase Order before creating an Inspection and Acceptance Report.';

    /**
     * Rendered whenever the supplier name or PO number cannot be resolved
     * from the source Purchase Order (and no legacy snapshot exists).
     */
    public const MISSING_VALUE_PLACEHOLDER = '—';

    protected $fillable = [
        'iar_no',
        'po_id',
        'supplier_name',
        'po_no',
        'date',
        'requisitioning_office_dept',
        'inspector_name',
        'inspector_designation',
        'inspection_date',
        'inspection_complete',
        'inspection_partial',
        'acceptor_name',
        'acceptor_designation',
        'acceptance_date',
        'acceptance_complete',
        'acceptance_partial',
        'status',
        'stocked_in_at',
        'stocked_in_by_user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'inspection_date' => 'date',
        'acceptance_date' => 'date',
        'inspection_complete' => 'boolean',
        'inspection_partial' => 'boolean',
        'acceptance_complete' => 'boolean',
        'editing_started_at' => 'datetime',
        'acceptance_partial' => 'boolean',
        'stocked_in_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (InspectionAcceptanceReport $iar) {
            $year = now()->year;
            $maxSeq = self::whereYear('created_at', $year)->max('id');
            $nextId = ($maxSeq ?? 0) + 1;
            $iar->iar_no = sprintf('IAR-%s-%04d', $year, $nextId);
            $iar->assertValidPoSource();
            $iar->hydrateFromPo();
        });

        static::updating(function (InspectionAcceptanceReport $iar) {
            if ($iar->isDirty('po_id')) {
                $iar->assertValidPoSource();
                $iar->hydrateFromPo();
            }
        });
    }

    /**
     * Backend guard: an IAR MUST be created from an existing, still-eligible
     * (not yet converted) Purchase Order. Throws when the source is missing,
     * already used, or no longer exists. Runs on every create (and on
     * po_id change) so direct URLs, API calls and manual form submissions
     * are all blocked.
     */
    public function assertValidPoSource(): void
    {
        if (! $this->po_id) {
            throw ValidationException::withMessages([
                'po_id' => 'An Inspection and Acceptance Report must be created from an existing Purchase Order.',
            ]);
        }

        $po = PurchaseOrder::find($this->po_id);

        if (! $po) {
            throw ValidationException::withMessages([
                'po_id' => 'The selected Purchase Order no longer exists.',
            ]);
        }

        if (self::where('po_id', $po->id)->whereKeyNot($this->id)->exists()) {
            throw ValidationException::withMessages([
                'po_id' => 'This Purchase Order has already been converted into an Inspection and Acceptance Report.',
            ]);
        }

        // Cache the validated source so the relationship is available to
        // hydrateFromPo() and the accessors without a second query.
        $this->setRelation('po', $po);
    }

    /**
     * Automatically retrieve and populate the supplier name and PO number
     * from the source Purchase Order this report was created from.
     *
     * Runs on every create (and on po_id change) so the values are populated
     * no matter how the IAR is created — Filament form, direct URL, API call
     * or manual model instantiation. This keeps the stored snapshot in sync
     * with the Purchase Order it came from; nothing is hard-coded.
     */
    public function hydrateFromPo(): void
    {
        $po = $this->po;

        if (! $po) {
            return;
        }

        // The PO's own supplier_name is itself sourced live from its BAC
        // Resolution (the ABC winning bid), so this stays a single,
        // authoritative chain: IAR → PO → BAC Resolution → ABC → RFQ → PR.
        if ($name = $po->supplier_name) {
            $this->supplier_name = $name;
        }

        if ($no = $po->po_no) {
            $this->po_no = $no;
        }
    }

    public function po(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    /**
     * The supplier behind this report, reached through the source Purchase
     * Order:
     *
     *   IAR → PO → Supplier
     *
     * Note: this relation is intentionally NOT used as the display source for
     * the Supplier column. SupplierSyncService deliberately keeps an existing
     * PO → Supplier link even when the PO's supplier name has drifted (to
     * avoid creating duplicate Supplier records), so `suppliers.name` can
     * legitimately differ from the PO's own supplier name. The PO's name is
     * the authoritative value for this report.
     */
    public function supplier(): HasOneThrough
    {
        return $this->hasOneThrough(
            Supplier::class,
            PurchaseOrder::class,
            'id',          // purchase_orders.id, matched against this report's po_id
            'id',          // suppliers.id
            'po_id',       // the column on this report that holds the source PO
            'supplier_id', // purchase_orders.supplier_id -> suppliers.id
        );
    }

    /**
     * The Supplier name is sourced live from the source Purchase Order (which
     * in turn derives from its BAC Resolution / ABC winning bid). This
     * accessor shadows the legacy stored column so every output — form, view,
     * print, download, table, stock-in — shows the same source-of-truth value.
     *
     * Falls back to the stored column for legacy IARs whose source Purchase
     * Order no longer exists or carries no supplier name, and returns null
     * (never throws) so callers can render a placeholder.
     */
    public function getSupplierNameAttribute(): ?string
    {
        return $this->po?->supplier_name
            ?? $this->getRawOriginal('supplier_name');
    }

    /**
     * The P.O. No. is sourced live from the source Purchase Order, using the
     * same accessor pattern as the supplier name so the table, form, view and
     * print output can never disagree.
     *
     * Falls back to the stored column for legacy IARs, and returns null
     * (never throws) when neither source is available.
     */
    public function getPoNoAttribute(): ?string
    {
        return $this->po?->po_no
            ?? $this->getRawOriginal('po_no');
    }

    /**
     * Resolve the Requisitioning Office / Department from the source
     * Purchase Request through the procurement document chain:
     *
     *   IAR → PO → BAC Resolution → ABC → RFQ → PR
     *
     * Falls back to the Purchase Order's direct ABC link for legacy
     * Purchase Orders that predate the BAC Resolution source, so reports
     * traversing PO → ABC → RFQ → PR keep working unchanged.
     */
    public function sourceOffice(): ?string
    {
        return $this->po?->bacResolution?->abc?->rfq?->purchaseRequest?->office_division
            ?? $this->po?->abc?->rfq?->purchaseRequest?->office_division;
    }

    /**
     * The Requisitioning Office / Department is sourced live from the
     * originating Purchase Request (through the procurement chain). This
     * accessor shadows the legacy stored column so every output — form,
     * view, print, download, table — shows the same source-of-truth value.
     *
     * Falls back to the stored column for legacy IARs whose source chain
     * is incomplete.
     */
    public function getRequisitioningOfficeDeptAttribute(): ?string
    {
        return $this->sourceOffice()
            ?? $this->getRawOriginal('requisitioning_office_dept');
    }

    public function items(): HasMany
    {
        return $this->hasMany(IarItem::class, 'iar_id');
    }

    public function stockInRecords(): HasMany
    {
        return $this->hasMany(StockInRecord::class, 'iar_id');
    }

    public function stockedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stocked_in_by_user_id');
    }

    /**
     * BAC Resolutions created from this IAR.
     *
     * An IAR is considered "Converted to BAC Resolution" when at least one
     * BAC Resolution references it via this relationship.
     */
    public function bacResolutions(): HasMany
    {
        return $this->hasMany(BacResolution::class, 'iar_id');
    }

    /**
     * Whether this IAR has already been converted into a BAC Resolution.
     */
    public function isConvertedToBac(): bool
    {
        return $this->bacResolutions()->exists();
    }

    /**
     * Only IARs that have not yet been converted into a BAC Resolution are
     * eligible to be used as the source document for a new BAC Resolution.
     */
    public function scopeEligibleForBacConversion(Builder $query): Builder
    {
        return $query->whereDoesntHave('bacResolutions');
    }

    /**
     * Human-readable summary of the inspection / acceptance details,
     * e.g. "COMPLETE INSPECTION; COMPLETE ACCEPTANCE".
     */
    public function acceptanceSummary(): string
    {
        $parts = [];

        if ($this->inspection_complete) {
            $parts[] = 'COMPLETE INSPECTION';
        }
        if ($this->inspection_partial) {
            $parts[] = 'PARTIAL INSPECTION';
        }
        if ($this->acceptance_complete) {
            $parts[] = 'COMPLETE ACCEPTANCE';
        }
        if ($this->acceptance_partial) {
            $parts[] = 'PARTIAL ACCEPTANCE';
        }

        return implode('; ', $parts) ?: '—';
    }

    /**
     * Get a comma-separated list of stock numbers from all IAR items.
     * Used for display in the list table.
     */
    public function getStockNumbersAttribute(): string
    {
        return $this->items->pluck('stock_no')->filter()->unique()->implode(', ');
    }

    /**
     * Get a comma-separated list of linked Supply stock numbers from all IAR items.
     * Shows the Supply module's stock_no for items that have been linked to a supply.
     * Used for display in the list table.
     */
    public function getSupplyNosAttribute(): string
    {
        return $this->items
            ->filter(fn (IarItem $item) => !empty($item->supply_id))
            ->map(fn (IarItem $item) => $item->supply?->stock_no)
            ->filter()
            ->unique()
            ->implode(', ');
    }

    public function isStockedIn(): bool
    {
        return $this->status === self::STATUS_STOCKED_IN;
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_STOCKED_IN => 'success',
            default => 'gray',
        };
    }
}
