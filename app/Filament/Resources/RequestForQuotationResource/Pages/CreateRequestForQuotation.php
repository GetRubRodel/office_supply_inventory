<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Filament\Resources\RequestForQuotationResource;
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
                ->body('No approved Purchase Request is available. Please create an approved Purchase Request before creating a Request for Quotation.')
                ->send();

            $this->redirect(RequestForQuotationResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
