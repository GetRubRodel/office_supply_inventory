<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\RequestForQuotationResource;
use App\Filament\Resources\RequestForQuotationResource\Widgets\RfqStatsOverviewWidget;
use App\Filament\Widgets\ProcurementWorkflowNoticeWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequestForQuotations extends ListRecords
{
    protected static string $resource = RequestForQuotationResource::class;

    protected ?string $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => CurrentUser::get()?->canManageProcurement()
                    && RequestForQuotationResource::hasEligibleSource()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProcurementWorkflowNoticeWidget::make(['target' => 'rfq']),
            RfqStatsOverviewWidget::class,
        ];
    }
}
