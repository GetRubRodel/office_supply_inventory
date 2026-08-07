<?php

namespace App\Filament\Resources\RequisitionResource\Widgets;

use App\Support\CurrentUser;

use App\Models\Requisition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class RequisitionStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('requisitions.view') ?? false;
    }

    protected function getStats(): array
    {
        $user = CurrentUser::get();
        if (! $user) {
            return [];
        }

        $baseQuery = static::getScopedQuery($user);

        $totalRequests = (clone $baseQuery)->count();
        $pendingApproval = (clone $baseQuery)->where('status', Requisition::STATUS_REQUESTED)->count();
        $approvedRequests = (clone $baseQuery)->where('status', Requisition::STATUS_APPROVED)->count();
        $issuedRequests = (clone $baseQuery)->whereIn('status', [
            Requisition::STATUS_ISSUED,
            Requisition::STATUS_PENDING_RECEIPT,
        ])->count();
        $receivedRequests = (clone $baseQuery)->where('status', Requisition::STATUS_RECEIVED)->count();
        $cancelledRequests = (clone $baseQuery)->where('status', Requisition::STATUS_CANCELLED)->count();

        return [
            Stat::make('Total RIS Requests', number_format($totalRequests))
                ->description('All requisition and issue slips')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Pending Approval', number_format($pendingApproval))
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Approved Requests', number_format($approvedRequests))
                ->description('Approved, awaiting issuance')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Issued Requests', number_format($issuedRequests))
                ->description('Issued items pending receipt')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('Received Requests', number_format($receivedRequests))
                ->description('Confirmed as received')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),

            Stat::make('Cancelled Requests', number_format($cancelledRequests))
                ->description('Cancelled requisitions')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }

    /**
     * Build a base query scoped to the current user's role.
     * Mirrors the scoping logic in RequisitionResource::table().
     */
    protected static function getScopedQuery($user): Builder
    {
        $query = Requisition::query();

        // Staff: own records only
        if ($user->isOwnRecordsOnly()) {
            return $query->where('user_id', $user->id);
        }

        // Division Chief and all other roles see all RIS records.
        return $query;
    }
}
