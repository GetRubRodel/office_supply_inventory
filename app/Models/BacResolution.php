<?php

namespace App\Models;

use App\Models\Concerns\HasEditingLock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacResolution extends Model
{
    use HasEditingLock;

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

        static::creating(function (BacResolution $bac) {
            $bac->resolution_no = static::generateNextResolutionNo();
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

    public function getDisplayResolutionNo(): string
    {
        return "RESOLUTION NO. {$this->resolution_no}, series of 2024";
    }
}
