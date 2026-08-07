<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\RequestForQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRequestForQuotation extends ViewRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected ?string $maxContentWidth = '4xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print RFQ')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('rfq.print', $this->record))
                ->openUrlInNewTab(),
            Actions\EditAction::make()
                ->visible(fn (): bool =>
                    CurrentUser::get()?->canManageProcurement()),
            Actions\DeleteAction::make()
                ->visible(fn (): bool =>
                    CurrentUser::get()?->canManageProcurement()),
        ];
    }
}
