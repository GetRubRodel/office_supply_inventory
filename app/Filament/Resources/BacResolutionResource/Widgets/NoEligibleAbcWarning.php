<?php

namespace App\Filament\Resources\BacResolutionResource\Widgets;

use App\Filament\Resources\BacResolutionResource;
use App\Models\BacResolution;
use App\Support\CurrentUser;
use Filament\Widgets\Widget;

/**
 * Persistent banner shown on the BAC Resolution list page whenever no
 * Abstract of Bids and Canvass (ABC) is available to be converted.
 *
 * Complements the hidden "New BAC Resolution" button: instead of a
 * click-triggered notification, the validation message stays visible so
 * users understand why creation is not possible.
 */
class NoEligibleAbcWarning extends Widget
{
    protected static string $view = 'filament.widgets.no-eligible-abc-warning';

    /**
     * Render inline (server-side) instead of lazy-loading over Livewire.
     * This is a validation notice explaining why creation is blocked — it
     * must always be visible immediately, without depending on a
     * JavaScript-triggered follow-up request.
     */
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        $user = CurrentUser::get();

        if (! $user) {
            return false;
        }

        // Mirrors the "New BAC Resolution" button visibility: the banner is
        // shown to any user who could otherwise create a BAC Resolution
        // whenever no eligible ABC is available, so the reason creation is
        // blocked stays visible.
        return $user->canManageProcurement()
            && $user->hasPermissionTo('bac_resolutions.create')
            && ! BacResolutionResource::hasEligibleSource();
    }

    protected function getViewData(): array
    {
        return [
            'message' => BacResolution::NO_ELIGIBLE_ABC_MESSAGE,
        ];
    }
}
