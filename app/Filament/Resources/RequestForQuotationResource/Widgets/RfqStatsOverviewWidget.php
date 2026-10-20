<?php

namespace App\Filament\Resources\RequestForQuotationResource\Widgets;

use App\Support\CurrentUser;

use App\Models\RequestForQuotation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RfqStatsOverviewWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('request_for_quotations.view') ?? false;
    }

    protected function getStats(): array
    {
        $totalRfqs = RequestForQuotation::count();

        // Approval details are sourced from the originating PR, so the RFQ's
        // own approved_by_* columns are no longer populated. Pending/completed
        // are derived from the RFQ lifecycle instead: draft RFQs still awaiting
        // supplier quotations vs. RFQs already converted into an ABC.
        $pendingRfqs = RequestForQuotation::where('status', RequestForQuotation::STATUS_DRAFT)
            ->whereDoesntHave('abstractsOfCanvass')
            ->count();

        $completedRfqs = RequestForQuotation::whereHas('abstractsOfCanvass')
            ->count();

        return [
            Stat::make('Total RFQs', number_format($totalRfqs))
                ->description('All RFQ records')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('primary'),

            Stat::make('Pending RFQs', number_format($pendingRfqs))
                ->description('Awaiting supplier quotations')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Completed RFQs', number_format($completedRfqs))
                ->description('Ready for next procurement stage')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success'),
        ];
    }
}
