<?php

namespace App\Filament\Widgets;

use App\Support\CurrentUser;

use App\Models\PurchaseRequest;
use Filament\Widgets\ChartWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PrStatusChartWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Purchase Request Overview';

    protected static string $color = 'primary';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('dashboard.view') ?? false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'this_year' => 'This Year',
        ];
    }

    protected function getData(): array
    {
        $user = Auth::user();
        if (! $user) {
            return $this->emptyData();
        }

        $statuses = [
            PurchaseRequest::STATUS_DRAFT      => 'Pending',
            PurchaseRequest::STATUS_FOR_REVIEW => 'For Approval',
            PurchaseRequest::STATUS_APPROVED   => 'Approved',
            PurchaseRequest::STATUS_REJECTED   => 'Rejected',
            PurchaseRequest::STATUS_CANCELLED  => 'Cancelled',
        ];

        $counts = [];
        foreach ($statuses as $status => $label) {
            $query = $this->getScopedQuery($user);
            $query->where('status', $status);
            $this->applyTimeFilter($query);
            $counts[$label] = $query->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Purchase Requests',
                    'data' => array_values($counts),
                    'backgroundColor' => [
                        '#6B7280',  // Pending (gray)
                        '#F59E0B',  // For Approval (amber)
                        '#10B981',  // Approved (green)
                        '#EF4444',  // Rejected (red)
                        '#94A3B8',  // Cancelled (slate)
                    ],
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => array_keys($counts),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(ctx) { return ctx.dataset.label + \": \" + ctx.parsed.y; }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }

    protected function getScopedQuery($user): Builder
    {
        $query = PurchaseRequest::query();

        if ($user->isOwnRecordsOnly()) {
            return $query->where('user_id', $user->id);
        }

        // Division Chief and all other roles see all Purchase Requests.
        return $query;
    }

    protected function applyTimeFilter(Builder $query): void
    {
        match ($this->filter) {
            'today' => $query->whereDate('created_at', now()),
            'this_week' => $query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]),
            'this_month' => $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year),
            'this_year' => $query->whereYear('created_at', now()->year),
            default => null,
        };
    }

    protected function emptyData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Purchase Requests',
                    'data' => [0, 0, 0, 0, 0],
                    'backgroundColor' => [
                        '#6B7280', '#F59E0B', '#10B981', '#EF4444', '#94A3B8',
                    ],
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => ['Pending', 'For Approval', 'Approved', 'Rejected', 'Cancelled'],
        ];
    }
}
