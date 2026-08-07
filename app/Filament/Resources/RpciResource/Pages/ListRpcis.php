<?php

namespace App\Filament\Resources\RpciResource\Pages;

use App\Filament\Resources\RpciResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRpcis extends ListRecords
{
    protected static string $resource = RpciResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\RpciResource\Widgets\RpciStatsOverview::class,
        ];
    }
}
