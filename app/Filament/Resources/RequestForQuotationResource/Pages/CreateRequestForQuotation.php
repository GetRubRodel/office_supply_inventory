<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Filament\Resources\RequestForQuotationResource;
use App\Models\RequestForQuotation;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateRequestForQuotation extends CreateRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected ?string $maxContentWidth = '4xl';

    public function mount(): void
    {
        if (! RequestForQuotationResource::hasEligibleSource()) {
            Notification::make()
                ->warning()
                ->title('Cannot create Request for Quotation')
                ->body(RequestForQuotation::NO_ELIGIBLE_PR_MESSAGE)
                ->send();

            $this->redirect(RequestForQuotationResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
