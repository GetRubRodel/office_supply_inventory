<?php

namespace App\Models;

use App\Models\Concerns\HasEditingLock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Supplier;
use Illuminate\Validation\ValidationException;

class PurchaseOrder extends Model
{
    use HasEditingLock;

    const STATUS_DRAFT = 'draft';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED = 'rejected';

    /**
     * Displayed whenever no BAC Resolution is available to be converted
     * into a Purchase Order.
     */
    public const NO_ELIGIBLE_BAC_MESSAGE = 'No BAC Resolution is available. Please create a BAC before creating a Purchase Order.';

    protected $fillable = [
        'po_no',
        'abc_id',
        'bac_resolution_id',
        'resolution_no',
        'supplier_id',
        'supplier_name',
        'contact_number',
        'address',
        'tin',
        'mode_of_procurement',
        'date',
        'place_of_delivery',
        'delivery_term',
        'date_of_delivery',
        'payment_term',
        'total_amount',
        'amount_in_words',
        'conforme_name',
        'conforme_date',
        'authorized_official_name',
        'authorized_official_designation',
        'funds_available_by',
        'funds_available_designation',
        'alobs_no',
        'alobs_amount',
    ];

    protected $casts = [
        'date' => 'date',
        'date_of_delivery' => 'date',
        'conforme_date' => 'date',
        'total_amount' => 'decimal:2',
        'alobs_amount' => 'decimal:2',
        'editing_started_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PurchaseOrder $po) {
            $year = now()->year;
            $maxSeq = self::whereYear('created_at', $year)->max('id');
            $nextId = ($maxSeq ?? 0) + 1;
            $po->po_no = sprintf('PO-%s-%04d', $year, $nextId);

            $po->assertValidBacSource();
            $po->hydrateFromBac();
        });

        static::updating(function (PurchaseOrder $po) {
            // The source BAC Resolution cannot be swapped once the record
            // exists; if it somehow is, re-validate and re-hydrate.
            if ($po->isDirty('bac_resolution_id')) {
                $po->assertValidBacSource();
                $po->hydrateFromBac();
            }
        });
    }

    /**
     * Whenever a Purchase Order is created or updated, its supplier
     * information is mirrored into the Suppliers module through the existing
     * PurchaseOrder -> Supplier relationship (see SupplierSyncService).
     *
     * The service reuses an existing Supplier when one matches, links the PO
     * via purchase_orders.supplier_id, and only fills in supplier fields that
     * are missing — existing supplier data is never overwritten.
     */
    protected static function booted(): void
    {
        static::saved(function (PurchaseOrder $po) {
            app(\App\Services\SupplierSyncService::class)->syncFromPurchaseOrder($po);
        });
    }

    public function abc(): BelongsTo
    {
        return $this->belongsTo(AbstractOfCanvass::class, 'abc_id');
    }

    /**
     * The BAC Resolution this Purchase Order was created from.
     */
    public function bacResolution(): BelongsTo
    {
        return $this->belongsTo(BacResolution::class, 'bac_resolution_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PoItem::class, 'po_id');
    }

    public function iar(): HasOne
    {
        return $this->hasOne(InspectionAcceptanceReport::class, 'po_id');
    }

    /**
     * Purchase Orders that have not yet been converted into an Inspection
     * and Acceptance Report — the source records for the IAR badge.
     * Cancelled and rejected Purchase Orders are never eligible.
     */
    public function scopeEligibleForIar(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_REJECTED])
            ->whereDoesntHave('iar');
    }

    /**
     * Backend guard: a Purchase Order MUST be created from an existing,
     * still-eligible BAC Resolution. Throws when the source is missing,
     * already used, or no longer exists. Runs on every create (and on
     * bac_resolution_id change) so direct URLs, API calls and manual form
     * submissions are all blocked.
     */
    public function assertValidBacSource(): void
    {
        if (! $this->bac_resolution_id) {
            throw ValidationException::withMessages([
                'bac_resolution_id' => 'A Purchase Order must be created from an existing BAC Resolution.',
            ]);
        }

        $bac = $this->bacResolution()->first();

        if (! $bac) {
            throw ValidationException::withMessages([
                'bac_resolution_id' => 'The selected BAC Resolution no longer exists.',
            ]);
        }

        if ($bac->purchaseOrders()->whereKeyNot($this->id)->exists()) {
            throw ValidationException::withMessages([
                'bac_resolution_id' => 'This BAC Resolution has already been converted into a Purchase Order.',
            ]);
        }
    }

    /**
     * Automatically retrieve and populate the relevant information from the
     * linked BAC Resolution: the resolution number snapshot, the supplier
     * name (and conforme name), and — to keep reports traversing
     * PO → ABC → RFQ → PR working — the ABC relationship, derived from
     * the source BAC Resolution's own ABC.
     */
    public function hydrateFromBac(): void
    {
        if (! $this->bac_resolution_id) {
            return;
        }

        $bac = $this->bacResolution()->first();

        if (! $bac) {
            return;
        }

        $this->resolution_no = $bac->resolution_no;

        if ($bac->abc_id && empty($this->abc_id)) {
            $this->abc_id = $bac->abc_id;
        }

        // Default the supplier name and conforme name from the BAC
        // Resolution's supplier (the ABC winning bid supplier) — but only when
        // this Purchase Order does not carry a value of its own. The stored
        // columns are what the rest of the system mirrors (see
        // capturedSupplierName()), so a value captured on this record —
        // including a deliberately blank one — must never be overwritten here.
        $supplierName = $bac->supplier_name;

        if ($supplierName !== null && $supplierName !== '') {
            if (! $this->hasSuppliedAttribute('supplier_name')) {
                $this->supplier_name = $supplierName;
            }

            if (! $this->hasSuppliedAttribute('conforme_name')) {
                $this->conforme_name = $supplierName;
            }
        }

        // Auto-populate the authorized official (approver) name
        // from the BAC Resolution's approved-by section
        // (which delegates to the originating ABC's approver).
        $approvedByName = $bac->approved_by_name;
        if ($approvedByName) {
            $this->authorized_official_name = $approvedByName;
        }
    }

    /**
     * The supplier name is sourced live from the linked BAC Resolution's
     * supplier (which in turn derives from the ABC winning bid). This
     * accessor shadows the legacy stored column so every output — form,
     * view, print, download, table — shows the same source-of-truth value.
     *
     * Falls back to the stored column for legacy Purchase Orders that
     * have no linked BAC Resolution.
     */
    public function getSupplierNameAttribute(): ?string
    {
        return $this->bacResolution?->supplier_name
            ?? $this->getRawOriginal('supplier_name');
    }

    /**
     * The supplier name actually captured on this Purchase Order.
     *
     * Unlike the `supplier_name` accessor above — which always reports the
     * live BAC Resolution / ABC winning bid, because that is what every
     * document output must show — this reads the stored column, i.e. the
     * supplier this Purchase Order actually names. Modules that mirror the
     * Purchase Order's own record into another table (see SupplierSyncService)
     * must use it, so a captured name is never masked by the BAC chain and a
     * Purchase Order that carries no supplier name is never silently given
     * one.
     *
     * Returns null when the Purchase Order carries no supplier name at all,
     * which is the signal for callers to skip the record.
     */
    public function capturedSupplierName(): ?string
    {
        $value = $this->getAttributes()['supplier_name'] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * Whether the given column already carries a value supplied for this
     * write. An explicit blank (null or '') counts as supplied, so hydration
     * never fills in a value the caller deliberately left empty.
     */
    private function hasSuppliedAttribute(string $key): bool
    {
        return array_key_exists($key, $this->getAttributes());
    }

    /**
     * The Conforme (Supplier) signature name is sourced from the same
     * BAC Resolution supplier. Falls back to the stored column for
     * legacy Purchase Orders without a BAC link.
     */
    public function getConformeNameAttribute(): ?string
    {
        return $this->bacResolution?->supplier_name
            ?? $this->getRawOriginal('conforme_name');
    }

    /**
     * The authorized official (approver) name is sourced live from the
     * linked BAC Resolution's approved-by section (which delegates to
     * the originating ABC). Falls back to the stored column for legacy
     * Purchase Orders without a BAC link.
     */
    public function getAuthorizedOfficialNameAttribute(): ?string
    {
        return $this->bacResolution?->approved_by_name
            ?? $this->getRawOriginal('authorized_official_name');
    }

    /**

    /**
     * Get a comma-separated list of stock numbers from all PO items.
     * Used for display in the list table.
     */
    public function getStockNumbersAttribute(): string
    {
        return $this->items->pluck('stock_no')->filter()->unique()->implode(', ');
    }

    /**
     * Convert a numeric amount to words in Philippine Peso format.
     * e.g., 5000.00 → "FIVE THOUSAND PESOS ONLY"
     */
    public static function numberToWords(float $number): string
    {
        $number = round($number, 2);
        $whole = floor($number);
        $cents = round(($number - $whole) * 100);

        if ($whole == 0 && $cents == 0) {
            return 'ZERO PESOS ONLY';
        }

        $words = strtoupper(self::convertNumberToWords($whole));

        $result = $words . ' PESOS';

        if ($cents > 0) {
            $centsWord = strtoupper(self::convertNumberToWords($cents));
            $result .= ' AND ' . $centsWord . ' CENTAVOS';
        }

        $result .= ' ONLY';

        return $result;
    }

    private static function convertNumberToWords(int $number): string
    {
        $ones = [
            0 => '', 1 => 'ONE', 2 => 'TWO', 3 => 'THREE', 4 => 'FOUR',
            5 => 'FIVE', 6 => 'SIX', 7 => 'SEVEN', 8 => 'EIGHT', 9 => 'NINE',
            10 => 'TEN', 11 => 'ELEVEN', 12 => 'TWELVE', 13 => 'THIRTEEN',
            14 => 'FOURTEEN', 15 => 'FIFTEEN', 16 => 'SIXTEEN', 17 => 'SEVENTEEN',
            18 => 'EIGHTEEN', 19 => 'NINETEEN',
        ];
        $tens = [
            2 => 'TWENTY', 3 => 'THIRTY', 4 => 'FORTY', 5 => 'FIFTY',
            6 => 'SIXTY', 7 => 'SEVENTY', 8 => 'EIGHTY', 9 => 'NINETY',
        ];

        if ($number < 20) {
            return $ones[$number] ?? '';
        }

        if ($number < 100) {
            $ten = intdiv($number, 10);
            $one = $number % 10;
            return $tens[$ten] . ($one > 0 ? ' ' . $ones[$one] : '');
        }

        if ($number < 1000) {
            $hundred = intdiv($number, 100);
            $remainder = $number % 100;
            $result = $ones[$hundred] . ' HUNDRED';
            if ($remainder > 0) {
                $result .= ' ' . self::convertNumberToWords($remainder);
            }
            return $result;
        }

        if ($number < 1000000) {
            $thousand = intdiv($number, 1000);
            $remainder = $number % 1000;
            $result = self::convertNumberToWords($thousand) . ' THOUSAND';
            if ($remainder > 0) {
                $result .= ' ' . self::convertNumberToWords($remainder);
            }
            return $result;
        }

        if ($number < 1000000000) {
            $million = intdiv($number, 1000000);
            $remainder = $number % 1000000;
            $result = self::convertNumberToWords($million) . ' MILLION';
            if ($remainder > 0) {
                $result .= ' ' . self::convertNumberToWords($remainder);
            }
            return $result;
        }

        // Billion
        $billion = intdiv($number, 1000000000);
        $remainder = $number % 1000000000;
        $result = self::convertNumberToWords($billion) . ' BILLION';
        if ($remainder > 0) {
            $result .= ' ' . self::convertNumberToWords($remainder);
        }
        return $result;
    }
}
