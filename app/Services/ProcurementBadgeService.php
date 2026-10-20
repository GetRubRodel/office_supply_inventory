<?php

namespace App\Services;

use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for the sequential Purchase Management workflow
 * badges (PR → RFQ → ABC → BAC Resolution → PO → IAR).
 *
 * Every stage exposes the same "eligible source records waiting for the next
 * document" query, so Filament navigation badges, create-page guards and
 * backend queries all agree on the same counts.
 *
 * A source record leaves its stage's badge as soon as the next document
 * references it (the existing FK relationships), and cancelled / rejected /
 * already-converted records are never counted. Badges are only rendered for
 * roles authorized to process (create) the target module.
 */
class ProcurementBadgeService
{
    public const STAGE_RFQ = 'rfq';
    public const STAGE_ABC = 'abc';
    public const STAGE_BAC = 'bac';
    public const STAGE_PO = 'po';
    public const STAGE_IAR = 'iar';

    /**
     * Permission that authorizes a user to process (create) each stage's
     * module. Badge counts are only shown to roles holding it.
     */
    public const STAGE_PERMISSIONS = [
        self::STAGE_RFQ => 'request_for_quotations.create',
        self::STAGE_ABC => 'abstracts_of_canvass.create',
        self::STAGE_BAC => 'bac_resolutions.create',
        self::STAGE_PO => 'purchase_orders.create',
        self::STAGE_IAR => 'inspection_acceptance_reports.create',
    ];

    /**
     * Query of source records eligible for conversion into the next
     * procurement stage.
     *
     * @throws \InvalidArgumentException for unknown stages
     */
    public static function eligibleQuery(string $stage): Builder
    {
        return match ($stage) {
            self::STAGE_RFQ => PurchaseRequest::query()->eligibleForRfq(),
            self::STAGE_ABC => RequestForQuotation::query()->eligibleForAbc(),
            self::STAGE_BAC => AbstractOfCanvass::query()->eligibleForBacConversion(),
            self::STAGE_PO => BacResolution::query()->eligibleForPo(),
            self::STAGE_IAR => PurchaseOrder::query()->eligibleForIar(),
            default => throw new \InvalidArgumentException("Unknown procurement badge stage: {$stage}"),
        };
    }

    /**
     * Number of source records currently waiting for the next stage.
     */
    public static function count(string $stage): int
    {
        return static::eligibleQuery($stage)->count();
    }

    /**
     * Whether the given user is authorized to see the badge for a stage.
     */
    public static function canViewBadge(string $stage, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $permission = self::STAGE_PERMISSIONS[$stage] ?? null;

        return $permission !== null && $user->hasPermissionTo($permission);
    }

    /**
     * Navigation badge value for a stage, or null when the user is not
     * authorized to process the module or nothing is waiting.
     */
    public static function badgeFor(string $stage, ?User $user): ?string
    {
        if (! static::canViewBadge($stage, $user)) {
            return null;
        }

        $count = static::count($stage);

        return $count > 0 ? (string) $count : null;
    }
}