<?php

namespace App\Models;

use App\Models\Concerns\HasEditingLock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class BacResolution extends Model
{
    use HasEditingLock;

    /**
     * Displayed whenever no Inspection and Acceptance Report (IAR) is
     * available to be converted into a BAC Resolution.
     */
    public const NO_ELIGIBLE_IAR_MESSAGE = 'No Inspection and Acceptance Report (IAR) is available. Please create an IAR before creating a BAC Resolution.';

    protected $fillable = [
        'iar_id',
        'resolution_no',
        'title',
        'preamble',
        'operative_part',
        'further_resolved',
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
        // Snapshot of the source IAR (auto-populated, not user-editable)
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
            $bac->assertValidIarSource();
            $bac->hydrateFromIar();
        });

        static::updating(function (BacResolution $bac) {
            // The source IAR cannot be swapped once the record exists; if it
            // somehow is, re-validate and re-hydrate the snapshot.
            if ($bac->isDirty('iar_id')) {
                $bac->assertValidIarSource();
                $bac->hydrateFromIar();
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
        $maxAttempts = 50;
        $attempt = 0;

        do {
            $attempt++;

            // Find the highest existing sequence number from resolution_no values
            $lastResolution = self::where('resolution_no', 'like', '01-%')
                ->orderByRaw('CAST(SUBSTRING(resolution_no, 4) AS UNSIGNED) DESC')
                ->value('resolution_no');

            $nextSeq = 1;
            if ($lastResolution) {
                $parts = explode('-', $lastResolution);
                if (count($parts) === 2 && is_numeric($parts[1])) {
                    $nextSeq = ((int) $parts[1]) + 1;
                }
            }

            // Account for any existing records that may have been created
            // since we queried the max (covers the sequential gap case)
            $candidate = sprintf('01-%03d', $nextSeq);

            if (!self::where('resolution_no', $candidate)->exists()) {
                return $candidate;
            }

            // The candidate exists — force-insert a temporary increment
            // so the next query finds a higher max
            if ($attempt >= $maxAttempts) {
                throw new \RuntimeException(
                    'Unable to generate a unique BAC Resolution number after ' . $maxAttempts . ' attempts.'
                );
            }

            // Prime the next iteration by making the next seq higher
            // than what currently exists
            $nextSeq++;
        } while (true);
    }

    /**
     * Get the full display resolution number.
     */
    public function iar(): BelongsTo
    {
        return $this->belongsTo(InspectionAcceptanceReport::class, 'iar_id');
    }

    /**
     * Backend guard: a BAC Resolution MUST be created from an existing,
     * still-eligible IAR. Throws when the source is missing, already used,
     * or no longer exists. Runs on every create (and on iar_id change) so
     * direct URLs, API calls and manual form submissions are all blocked.
     */
    public function assertValidIarSource(): void
    {
        if (! $this->iar_id) {
            throw ValidationException::withMessages([
                'iar_id' => 'A BAC Resolution must be created from an existing Inspection and Acceptance Report (IAR).',
            ]);
        }

        $iar = $this->iar()->first();

        if (! $iar) {
            throw ValidationException::withMessages([
                'iar_id' => 'The selected Inspection and Acceptance Report (IAR) no longer exists.',
            ]);
        }

        if ($iar->bacResolutions()->whereKeyNot($this->id)->exists()) {
            throw ValidationException::withMessages([
                'iar_id' => 'This Inspection and Acceptance Report (IAR) has already been converted into a BAC Resolution.',
            ]);
        }
    }

    /**
     * Automatically retrieve and populate all relevant information from the
     * linked IAR: IAR number, PO number, supplier, inspection/acceptance
     * details and a per-item snapshot (description, quantity, unit, unit
     * cost, total amount).
     */
    public function hydrateFromIar(): void
    {
        if (! $this->iar_id) {
            return;
        }

        $iar = $this->iar()->with('items')->first();

        if (! $iar) {
            return;
        }

        $this->iar_no = $iar->iar_no;
        $this->po_no = $iar->po_no;
        $this->supplier_name = $iar->supplier_name;
        $this->inspection_date = $iar->inspection_date;
        $this->acceptance_date = $iar->acceptance_date;
        $this->acceptance_details = $iar->acceptanceSummary();
        $this->items_snapshot = $iar->items->map(function (IarItem $item) {
            $quantity = (int) ($item->quantity_accepted ?: $item->quantity);
            $unitCost = (float) $item->unit_cost;

            return [
                'stock_no' => $item->stock_no,
                'description' => $item->description,
                'quantity' => $quantity,
                'unit' => $item->unit,
                'unit_cost' => $unitCost,
                'total' => round($quantity * $unitCost, 2),
            ];
        })->values()->toArray();
    }

    /**
     * Grand total of the source IAR snapshot (sum of per-item totals).
     */
    public function getTotalAmount(): ?float
    {
        $items = is_array($this->items_snapshot) ? $this->items_snapshot : [];

        return round(array_sum(array_column($items, 'total')), 2);
    }

    public function getDisplayResolutionNo(): string
    {
        return "RESOLUTION NO. {$this->resolution_no}, series of 2024";
    }
}
