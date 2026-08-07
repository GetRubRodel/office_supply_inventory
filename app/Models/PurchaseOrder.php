<?php

namespace App\Models;

use App\Models\Concerns\HasEditingLock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Supplier;

class PurchaseOrder extends Model
{
    use HasEditingLock;

    protected $fillable = [
        'po_no',
        'abc_id',
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
        });
    }

    public function abc(): BelongsTo
    {
        return $this->belongsTo(AbstractOfCanvass::class, 'abc_id');
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
