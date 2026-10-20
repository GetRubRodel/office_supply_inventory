<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Single source of truth for the BAC Resolution body text.
 *
 * The resolution wording is a fixed template containing five blanks:
 *
 *   Blank 1 — Division        (selected by the authorized user)
 *   Blank 2 — Division        (automatically retrieved from Blank 1)
 *   Blank 3 — Budget Amount   (entered by the authorized user)
 *   Blank 4 — Division        (automatically retrieved from Blank 1)
 *   Blank 5 — Budget Amount   (automatically retrieved from Blank 3)
 *
 * The closing statement additionally carries the date selected on the
 * resolution DatePicker ({@see RESOLVED_DATE_TOKEN}), appended after the
 * ordered clauses as "Done this <date>, City of Koronadal, Philippines."
 *
 * Nothing in this class contains a concrete Division or Budget value — the
 * blanks are tokenised ({@see DIVISION_TOKEN} / {@see BUDGET_TOKEN}) and are
 * substituted at render time. This guarantees the form, view, print and
 * download outputs all show the exact same retrieved values, and that no
 * Division, Budget or date is ever hardcoded into the document.
 */
final class BacResolutionText
{
    /**
     * Token standing in for the first blank (the Division Select).
     */
    public const DIVISION_TOKEN = '{{DIVISION}}';

    /**
     * Token standing in for the third blank (the Budget Amount input).
     */
    public const BUDGET_TOKEN = '{{BUDGET}}';

    /**
     * Token standing in for the date picked on the resolution DatePicker.
     *
     * Substituted at render time — never written into the stored template —
     * so the closing statement always reflects the value the user selected.
     */
    public const RESOLVED_DATE_TOKEN = '{{RESOLVED_DATE}}';

    /**
     * Fixed venue printed after the resolved date in the closing statement.
     */
    public const CLOSING_PLACE = 'City of Koronadal, Philippines';

    /**
     * Ordinal long-form date used in the closing statement, e.g.
     * "2nd day of October 2026".
     */
    public const RESOLVED_DATE_FORMAT = 'jS \d\a\y \o\f F Y';

    /**
     * Rendered in place of a token that has no value yet, so an unfilled
     * resolution still looks like a proper blank document.
     */
    public const BLANK_PLACEHOLDER = '________________________';

    /**
     * Divisions offered by the first blank.
     *
     * @var list<string>
     */
    public const DIVISIONS = [
        'Administrative Support Division',
        'Promotion and Advocacy Division',
        'Investigation Division',
        'Legal Division',
    ];

    /**
     * Title line — carries the first blank (Division).
     *
     * Set in sentence case; the document must not be rendered in all caps.
     */
    public const TITLE = 'A resolution authorizing the procurement of office supplies for '
        . self::DIVISION_TOKEN
        . ' through small value procurement as provided in the Implementing Rules and '
        . 'Regulations (IRR) of Republic Act No. 12009.';

    /**
     * WHEREAS clauses.
     *
     * - WHEREAS 1  carries the second blank (Division, read-only).
     * - WHEREAS 3  carries the third blank (Budget Amount).
     *
     * @var list<string>
     */
    public const PREAMBLE = [
        'WHEREAS, the Commission on Human Rights XII ' . self::DIVISION_TOKEN . ' requires various office '
            . 'supplies to ensure the continuous and efficient delivery of administrative services and '
            . 'daily operations;',

        'WHEREAS, Rule IV of the Implementing Rules and Regulations (IRR) of Republic Act No. 12009 allows '
            . 'for alternative modes of procurement for goods and services where the amount involved is '
            . 'within the prescribed threshold for the agency;',

        'WHEREAS, the estimated budget for this specific procurement is ' . self::BUDGET_TOKEN . ', which '
            . 'is within the approved budget and allowable limits for this procurement mode;',

        'WHEREAS, there is a need to facilitate the immediate acquisition of said supplies to avoid any '
            . 'disruption in the division’s support functions, in compliance with applicable government '
            . 'accounting and auditing rules.',
    ];

    /**
     * Operative part — the resolving clause and its three numbered items.
     *
     * - Item 1 carries the fourth blank (Division, read-only).
     * - Item 2 carries the fifth blank (Budget Amount, read-only).
     *
     * @var list<string>
     */
    public const OPERATIVE = [
        'NOW, THEREFORE, on motion duly seconded and approved, the Bids and Awards Committee (BAC) hereby '
            . 'resolves to:',

        '1. AUTHORIZE the procurement of office supplies for the ' . self::DIVISION_TOKEN . ' utilizing the '
            . 'appropriate procurement mode as provided under the IRR of Republic Act No. 12009;',

        '2. ENSURE that the total amount to be procured shall not exceed the approved budget of '
            . self::BUDGET_TOKEN . ' and that all purchases shall be properly documented with official '
            . 'receipts and necessary supporting documents;',

        '3. REQUIRE that all disbursements shall be subject to existing budgeting, accounting, and auditing '
            . 'rules and regulations.',
    ];

    /**
     * Closing section. The former "FURTHER RESOLVED" clause has been removed.
     *
     * The second paragraph carries the date selected on the resolution
     * DatePicker ({@see RESOLVED_DATE_TOKEN}). It is tokenised like every
     * other blank so the concrete date is only ever substituted at render
     * time and can never be hardcoded into the stored template.
     *
     * The blank line is preserved by the `white-space: pre-line` rule on the
     * closing paragraph in both the print view and the form preview.
     */
    public const CLOSING = "SO RESOLVED.\n\nDone this ".self::RESOLVED_DATE_TOKEN.', '.self::CLOSING_PLACE.'.';

    /**
     * The template exactly as it is persisted on the record (tokens intact).
     */
    public static function titleTemplate(): string
    {
        return self::TITLE;
    }

    public static function preambleTemplate(): string
    {
        return implode("\n\n", self::PREAMBLE);
    }

    public static function operativeTemplate(): string
    {
        return implode("\n\n", self::OPERATIVE);
    }

    public static function closingTemplate(): string
    {
        return self::CLOSING;
    }

    /**
     * Substitute the Division, Budget Amount and resolved-date blanks into a
     * template string.
     *
     * An empty or missing value renders as a blank line placeholder so the
     * document always looks correct, never as an empty gap or a stray token.
     */
    public static function resolve(?string $text, ?string $division = null, ?string $budgetAmount = null, mixed $resolvedOn = null): string
    {
        $division = self::clean($division);
        $budgetAmount = self::clean($budgetAmount);

        return str_replace(
            [self::DIVISION_TOKEN, self::BUDGET_TOKEN, self::RESOLVED_DATE_TOKEN],
            [$division !== '' ? $division : self::BLANK_PLACEHOLDER,
                $budgetAmount !== '' ? $budgetAmount : self::BLANK_PLACEHOLDER,
                self::resolvedOnLabel($resolvedOn)],
            (string) $text,
        );
    }

    /**
     * Format a raw date/datetime value for the closing statement.
     *
     * Accepts anything Carbon can parse — the model cast value, a Filament
     * DatePicker state string, or ISO-8601. Missing or unparseable input
     * falls back to the blank placeholder so the document still reads as a
     * proper blank rather than showing a raw token.
     */
    public static function resolvedOnLabel(mixed $resolvedOn): string
    {
        if ($resolvedOn === null || trim((string) $resolvedOn) === '') {
            return self::BLANK_PLACEHOLDER;
        }

        try {
            return Carbon::parse((string) $resolvedOn)->format(self::RESOLVED_DATE_FORMAT);
        } catch (\Throwable) {
            return self::BLANK_PLACEHOLDER;
        }
    }

    /**
     * The full resolution body as an ordered list of typed blocks.
     *
     * Every output (Filament form preview, view page, print page and the
     * browser's print-to-PDF download) renders this same list, so the wording
     * and the substituted Division / Budget Amount / resolved date can never
     * drift apart.
     *
     * @return list<array{type: string, text: string}>
     */
    public static function blocks(?string $division = null, ?string $budgetAmount = null, mixed $resolvedOn = null): array
    {
        $blocks = [
            ['type' => 'title', 'text' => self::TITLE],
        ];

        foreach (self::PREAMBLE as $whereas) {
            $blocks[] = ['type' => 'whereas', 'text' => $whereas];
        }

        foreach (self::OPERATIVE as $operative) {
            $blocks[] = ['type' => preg_match('/^\d+\.\s/', $operative) === 1 ? 'item' : 'operative', 'text' => $operative];
        }

        $blocks[] = ['type' => 'closing', 'text' => self::CLOSING];

        return array_map(
            fn (array $block): array => [
                'type' => $block['type'],
                'text' => self::resolve($block['text'], $division, $budgetAmount, $resolvedOn),
            ],
            $blocks,
        );
    }

    private static function clean(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }
}