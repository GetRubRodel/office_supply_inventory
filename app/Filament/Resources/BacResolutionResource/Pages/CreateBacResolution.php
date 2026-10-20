<?php

namespace App\Filament\Resources\BacResolutionResource\Pages;

use App\Filament\Resources\BacResolutionResource;
use App\Models\BacResolution;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBacResolution extends CreateRecord
{
    protected static string $resource = BacResolutionResource::class;

    protected ?string $maxContentWidth = '4xl';

    /**
     * Prevent direct access (URL / API / navigation) to the Create page when
     * there is no eligible Abstract of Bids and Canvass (ABC) available as
     * the source document.
     */
    public function mount(): void
    {
        if (! BacResolutionResource::hasEligibleSource()) {
            Notification::make()
                ->warning()
                ->title('Cannot create BAC Resolution')
                ->body(BacResolution::NO_ELIGIBLE_ABC_MESSAGE)
                ->send();

            $this->redirect(BacResolutionResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
