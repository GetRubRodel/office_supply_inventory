<?php

namespace App\Filament\Widgets;

use App\Support\CurrentUser;

use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use Filament\Widgets\ChartWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Support\RawJs;

class ProcurementStatusChartWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Procurement Status';

    protected static string $color = 'primary';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        $user = CurrentUser::get();
        if (! $user) return false;

        // Staff (own-records-only) must not see procurement status for
        // modules they cannot access (RFQ, ABC, BAC, PO, IAR).
        return $user->hasPermissionTo('dashboard.view') && ! $user->isOwnRecordsOnly();
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function getDescription(): string | Htmlable | null
    {
        $data = $this->getCachedData();
        $total = array_sum($data['datasets'][0]['data'] ?? []);

        return 'Total procurement transactions: ' . number_format($total);
    }

    protected function getData(): array
    {
        $counts = [
            PurchaseRequest::count(),
            RequestForQuotation::count(),
            AbstractOfCanvass::count(),
            BacResolution::count(),
            PurchaseOrder::count(),
            InspectionAcceptanceReport::count(),
        ];

        $labels = ['PR', 'RFQ', 'ABC', 'BAC Resolution', 'PO', 'IAR'];

        return [
            'datasets' => [
                [
                    'data' => $counts,
                    'backgroundColor' => [
                        '#F59E0B',  // PR (amber)
                        '#3B82F6',  // RFQ (blue)
                        '#10B981',  // ABC (green)
                        '#8B5CF6',  // BAC (purple)
                        '#EF4444',  // PO (red)
                        '#14B8A6',  // IAR (teal)
                    ],
                    'borderWidth' => 3,
                    'borderColor' => '#ffffff',
                    'hoverOffset' => 8,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            cutout: '55%',
            plugins: {
                legend: {
                    display: true,
                    position: 'right',
                    labels: {
                        padding: 16,
                        usePointStyle: true,
                        pointStyleWidth: 12,
                        font: {
                            size: 12
                        },
                        generateLabels: function(chart) {
                            var data = chart.data;
                            if (!data.labels.length || !data.datasets.length) return [];
                            var total = data.datasets[0].data.reduce(function(a, b) { return a + b; }, 0);
                            return data.labels.map(function(label, i) {
                                var value = data.datasets[0].data[i];
                                var pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return {
                                    text: label + ': ' + value + ' (' + pct + '%)',
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: data.datasets[0].backgroundColor[i],
                                    lineWidth: 0,
                                    index: i,
                                    hidden: false,
                                };
                            });
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            var data = ctx.chart.data;
                            var total = data.datasets[0].data.reduce(function(a, b) { return a + b; }, 0);
                            var value = ctx.parsed;
                            var pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return ctx.label + ': ' + value + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
        JS);
    }
}
