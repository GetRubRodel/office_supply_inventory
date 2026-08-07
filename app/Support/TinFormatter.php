<?php

namespace App\Support;

class TinFormatter
{
    /**
     * Format a stored TIN for display using the standard Philippine layout
     * (e.g., 123-456-789 or 123-456-789-000). Values that are not valid 9 or
     * 12 digit numeric TINs are returned unchanged so legacy data is preserved.
     */
    public static function format(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (! preg_match('/^\d{9}$|^\d{12}$/', $digits)) {
            return $value;
        }

        return implode('-', str_split($digits, 3));
    }
}
