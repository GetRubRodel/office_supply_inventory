<?php

namespace App\Filament\Resources\BacResolutionResource\Widgets;

use App\Filament\Resources\BacResolutionResource;
use App\Models\BacResolution;
use App\Support\CurrentUser;
use Filament\Widgets\Widget;

/**
 * Persistent banner shown on the BAC Resolution list page whenever no
 * Inspection and Acceptance Report (IAR) is available to be converted.
 *
 * Complements the hidden "New BAC Resolution" button: instead of a
 * click-triggered notification, the validation message stays visible so
 * users understand why creation is not possible.
 */
class NoEligibleIarWarning extends Widget
{
    protected static string $view = 'filament.widgets.no-eligible-iar-warning';

    public static function canView(): bool
    {
        $user = CurrentUser::get();

        if (! $user) {
            return false;
        }

        return $user->hasPermissionTo('bac_resolutions.create')
            && ! BacResolutionResource::hasEligibleIar();
    }

    protected function getViewData(): array
    {
        return [
            'message' => BacResolution::NO_ELIGIBLE_IAR_MESSAGE,
        ];
    }
}
