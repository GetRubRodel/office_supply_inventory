<?php

namespace App\Filament\Widgets;

use App\Support\CurrentUser;

use App\Models\RequisitionItem;
use App\Models\Supply;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class DashboardStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('dashboard.view') ?? false;
    }

    /**
     * How long (seconds) the aggregate stats are cached. The widget polls
     * every 30s, so a 60s window halves the aggregate query load while
     * keeping the dashboard effectively live.
     */
    private const STATS_TTL = 60;

    protected function getStats(): array
    {
        $user = CurrentUser::get();
        $stats = [];

        // Issued Items — visible to all dashboard viewers (RIS-related).
        // Cached per scope: staff (own-records-only) get their own key so
        // they never see another user's totals.
        $issuedScope = $user?->isOwnRecordsOnly() ? 'user:' . $user->id : 'all';

        $issuedQty = Cache::remember(
            "dashboard.issued_qty.{$issuedScope}",
            self::STATS_TTL,
            function () use ($user) {
                return RequisitionItem::whereHas('requisition', function ($q) use ($user) {
                    $q->whereIn('status', ['issued', 'pending_receipt', 'received']);

                    // Staff (own-records-only) see only their own issued quantities.
                    if ($user?->isOwnRecordsOnly()) {
                        $q->where('user_id', $user->id);
                    }
                })->sum('quantity_issued');
            }
        );

        $stats[] = Stat::make('Issued Items', number_format($issuedQty))
            ->description('Total quantity issued via RIS')
            ->descriptionIcon('heroicon-m-document-text')
            ->color('info');

        // Supply-specific stats — only for users with supplies.view.
        // The three aggregates are global, so they share one cache entry.
        if ($user?->hasPermissionTo('supplies.view')) {
            $supplyStats = Cache::remember('dashboard.supply_stats', self::STATS_TTL, function () {
                return [
                    'total' => Supply::count(),
                    'available' => Supply::where('status', 'active')->sum('current_stock'),
                    'value' => Supply::where('status', 'active')->sum('total_cost'),
                ];
            });

            $stats[] = Stat::make('Total Supplies', number_format($supplyStats['total']))
                ->description('Unique supply items')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary');

            $stats[] = Stat::make('Available Inventory', number_format($supplyStats['available']))
                ->description('Total available units')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('warning');

            $stats[] = Stat::make('Inventory Value', '₱' . number_format($supplyStats['value'], 2))
                ->description('Total value at moving average cost')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success');
        }

        return $stats;
    }
}
