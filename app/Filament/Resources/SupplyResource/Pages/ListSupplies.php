<?php

namespace App\Filament\Resources\SupplyResource\Pages;

use App\Filament\Resources\SupplyResource;
use Filament\Resources\Pages\ListRecords;

class ListSupplies extends ListRecords
{
    protected static string $resource = SupplyResource::class;

    protected function getHeaderActions(): array
    {
        // Supplies are only added via the IAR stock-in process — no manual creation
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SupplyResource\Widgets\SupplyStatsOverviewWidget::class,
        ];
    }
}
