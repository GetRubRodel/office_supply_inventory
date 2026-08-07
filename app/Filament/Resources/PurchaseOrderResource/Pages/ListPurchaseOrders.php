<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\PurchaseOrderResource\Widgets\PoStatsOverviewWidget;
use App\Filament\Widgets\ProcurementWorkflowNoticeWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected ?string $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => CurrentUser::get()?->canManageProcurement()
                    && PurchaseOrderResource::hasEligibleSource()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProcurementWorkflowNoticeWidget::make(['target' => 'po']),
            PoStatsOverviewWidget::class,
        ];
    }
}
