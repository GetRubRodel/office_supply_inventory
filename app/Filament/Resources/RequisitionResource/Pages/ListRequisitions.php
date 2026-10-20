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
            // The Create button is governed by the resource's canCreate()
            // permission gate (requisitions.create). Every authenticated role
            // — including Division Chief and Regional Director — holds the
            // permission, so each user can create and submit their own RIS.
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RequisitionStatsOverviewWidget::class,
        ];
    }
}
