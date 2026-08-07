<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected ?string $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print PO')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('po.print', $this->record))
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
