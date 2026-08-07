<?php

namespace App\Filament\Resources\RequisitionResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\RequisitionResource;
use App\Filament\Resources\RequisitionResource\Widgets\RequisitionStatsOverviewWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequisitions extends ListRecords
{
    protected static string $resource = RequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool =>
                    ! CurrentUser::get()?->isRegionalDirector()
                    && ! CurrentUser::get()?->isDivisionChief()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RequisitionStatsOverviewWidget::class,
        ];
    }
}
