<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Single source of truth for the Philippine Standard Time clock used by every
 * generated form in this application (RSMI, Procurement Monitoring, Inventory,
 * RPCI, …).
 *
 * The application timezone is UTC, so a bare `now()` renders a timestamp that
 * is eight hours behind local time and can even land on the wrong calendar day
 * for reports generated between 00:00 and 08:00 local time. Every "Generated
 * on …" / "Effectivity Date: …" string must therefore go through this class
 * rather than calling `now()` directly.
 *
 * Note: this is deliberately a formatter, not a global config change. Reports
 * are *rendered* in Philippine time, but stored `created_at` / `updated_at`
 * values remain UTC so they round-trip correctly through the database.
 */
final class ReportClock
{
    /**
     * IANA identifier for Philippine Standard Time.
     */
    public const TIMEZONE = 'Asia/Manila';

    /**
     * Canonical "Generated on" format used across every generated form.
     */
    public const GENERATED_FORMAT = 'F d, Y h:i A';

    /**
     * Canonical file-name date format for downloaded reports.
     */
    public const FILENAME_FORMAT = 'Y-m-d';

    /**
     * The current moment, expressed in Philippine Standard Time.
     */
    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE);
    }

    /**
     * Format the current moment in Philippine Standard Time.
     */
    public static function format(string $format = self::GENERATED_FORMAT): string
    {
        return self::now()->format($format);
    }

    /**
     * Today's date in Philippine Standard Time, for report file names.
     */
    public static function fileDate(): string
    {
        return self::format(self::FILENAME_FORMAT);
    }

    /**
     * The "Generated on …" line for a report header.
     */
    public static function generatedOn(): string
    {
        return 'Generated on ' . self::format();
    }
}
