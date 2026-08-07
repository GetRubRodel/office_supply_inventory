<?php

namespace App\Filament\Resources\PurchaseRequestResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\PurchaseRequestResource;
use App\Filament\Resources\PurchaseRequestResource\Widgets\PrStatsOverviewWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseRequests extends ListRecords
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool =>
                    ! CurrentUser::get()?->isRegionalDirector()
                    && ! CurrentUser::get()?->isPropertyCustodian()
                    && ! CurrentUser::get()?->isDivisionChief()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PrStatsOverviewWidget::class,
        ];
    }

    protected function getTablePollingInterval(): ?string
    {
        return '30s';
    }
}
