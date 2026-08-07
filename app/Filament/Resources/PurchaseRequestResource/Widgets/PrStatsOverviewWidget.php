<?php

namespace App\Filament\Resources\PurchaseRequestResource\Widgets;

use App\Support\CurrentUser;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
class PrStatsOverviewWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('purchase_requests.view') ?? false;
    }

    protected function getStats(): array
    {
        $user = CurrentUser::get();
        if (! $user) {
            return [];
        }

        $scopedQuery = $this->getScopedQuery($user);

        // Total Purchase Requests visible to this user
        $totalPrs = $scopedQuery->count();

        // Subquery of PR ids scoped to the user (reused for item aggregations)
        $scopedPrIds = fn () => $scopedQuery->select('id');

        // Total Requested Items (sum of quantity across all line items)
        $totalRequestedItems = PurchaseRequestItem::whereIn(
            'purchase_request_id',
            $scopedPrIds()
        )->sum('quantity');

        // Requests Created Today
        $createdToday = (clone $scopedQuery)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return [
            Stat::make('Total Purchase Requests', number_format($totalPrs))
                ->description('All PR records')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make('Total Requested Items', number_format($totalRequestedItems))
                ->description('Line items across all PRs')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('info'),

            Stat::make('Requests Created Today', number_format($createdToday))
                ->description('New PRs submitted today')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('warning'),
        ];
    }

    /**
     * Apply role-based scope to the Purchase Request query.
     *
     * - Administrator / Regional Director / Supply Officer / Property Custodian / Division Chief → all records
     * - Staff / Requester → only their own submitted records
     */
    protected function getScopedQuery($user): \Illuminate\Database\Eloquent\Builder
    {
        $query = PurchaseRequest::query();

        if ($user->isOwnRecordsOnly()) {
            return $query->where('user_id', $user->id);
        }

        // Division Chief and all other roles see all Purchase Requests.
        return $query;
    }
}
