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

        $pendingRfqs = RequestForQuotation::where('status', 'draft')
            ->whereNull('approved_by_name')
            ->count();

        $completedRfqs = RequestForQuotation::whereNotNull('approved_by_name')
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
