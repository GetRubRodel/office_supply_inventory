<?php

namespace App\Filament\Resources\AbstractOfCanvassResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\AbstractOfCanvassResource;
use App\Filament\Resources\AbstractOfCanvassResource\Widgets\AbcStatsOverviewWidget;
use App\Filament\Widgets\ProcurementWorkflowNoticeWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAbstractsOfCanvass extends ListRecords
{
    protected static string $resource = AbstractOfCanvassResource::class;

    protected ?string $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => CurrentUser::get()?->canManageProcurement()
                    && AbstractOfCanvassResource::hasEligibleSource()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProcurementWorkflowNoticeWidget::make(['target' => 'abc']),
            AbcStatsOverviewWidget::class,
        ];
    }
}
