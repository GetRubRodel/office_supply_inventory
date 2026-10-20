<?php

namespace App\Filament\Resources\PurchaseRequestResource\Widgets;

use App\Support\CurrentUser;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

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

        // Subquery of PR ids scoped to the user (reused for item aggregations)
        $scopedPrIds = fn () => $scopedQuery->select('id');

        // Total Purchase Requests (approved only)
        $totalApprovedPrs = (clone $scopedQuery)
            ->where('status', PurchaseRequest::STATUS_APPROVED)
            ->count();

        // Total Requested Items (sum of quantity across all line items)
        $totalRequestedItems = PurchaseRequestItem::whereIn(
            'purchase_request_id',
            $scopedPrIds()
        )->sum('quantity');

        // Total PR Rejected
        $totalRejectedPrs = (clone $scopedQuery)
            ->where('status', PurchaseRequest::STATUS_REJECTED)
            ->count();

        return [
            Stat::make('Total Purchase Requests', number_format($totalApprovedPrs))
                ->description('Approved PR records')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make('Total Requested Items', number_format($totalRequestedItems))
                ->description('Line items across all PRs')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('info'),

            Stat::make('Total PR Rejected', number_format($totalRejectedPrs))
                ->description('Rejected PR records')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }

    /**
     * Apply role-based scope to the Purchase Request query.
     *
     * Every role holding purchase_requests.view sees the full record set —
     * Staff track the whole procurement pipeline read-only, mirroring the
     * unscoped PurchaseRequestResource list query.
     */
    protected function getScopedQuery($user): Builder
    {
        return PurchaseRequest::query();
    }
}
