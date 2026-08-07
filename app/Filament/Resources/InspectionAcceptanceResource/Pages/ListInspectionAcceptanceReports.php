<?php

namespace App\Filament\Resources\InspectionAcceptanceResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\InspectionAcceptanceResource;
use App\Filament\Resources\InspectionAcceptanceResource\Widgets\IarStatsOverviewWidget;
use App\Filament\Widgets\ProcurementWorkflowNoticeWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInspectionAcceptanceReports extends ListRecords
{
    protected static string $resource = InspectionAcceptanceResource::class;

    protected ?string $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => CurrentUser::get()?->canManageProcurement()
                    && InspectionAcceptanceResource::hasEligibleSource()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProcurementWorkflowNoticeWidget::make(['target' => 'iar']),
            IarStatsOverviewWidget::class,
        ];
    }
}
