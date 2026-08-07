<?php

namespace App\Filament\Resources\InspectionAcceptanceResource\Pages;

use App\Filament\Resources\InspectionAcceptanceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInspectionAcceptanceReport extends CreateRecord
{
    protected static string $resource = InspectionAcceptanceResource::class;

    protected ?string $maxContentWidth = '7xl';

    public function mount(): void
    {
        if (! InspectionAcceptanceResource::hasEligibleSource()) {
            Notification::make()
                ->warning()
                ->title('Cannot create Inspection and Acceptance Report')
                ->body('No Purchase Order is available. Please create a Purchase Order before creating an Inspection and Acceptance Report.')
                ->send();

            $this->redirect(InspectionAcceptanceResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
