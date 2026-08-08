<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\InventoryReport;
use App\Filament\Pages\RsmiReport;
use App\Filament\Pages\UserProfile;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\DashboardStatsOverviewWidget;
use App\Filament\Widgets\LowStockSuppliesWidget;
use App\Filament\Widgets\PrStatusChartWidget;
use App\Filament\Widgets\ProcurementStatusChartWidget;
use App\Filament\Widgets\RecentTransactionsWidget;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Web-Based Supply Management and Procurement System')
            ->brandLogo(fn (): HtmlString => new HtmlString(
                '<div style="display: flex; align-items: center; gap: 0.75rem;">' .
                    '<img src="' . asset('images/logo.png') . '" alt="System Logo" style="height: 2.5rem; width: auto; object-fit: contain; flex-shrink: 0;" />' .
                    '<span class="dark:hidden" style="font-size: 0.75rem; font-weight: 700; line-height: 1.4; color: #111827; overflow-wrap: break-word;">Web-Based Supply Management and Procurement System</span>' .
                    '<span class="hidden dark:block" style="font-size: 0.75rem; font-weight: 700; line-height: 1.4; color: #f9fafb; overflow-wrap: break-word;">Web-Based Supply Management and Procurement System</span>' .
                '</div>'
            ))
            ->brandLogoHeight('auto')
            ->login(Login::class)
            ->registration(Register::class)
            ->globalSearch(false)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                InventoryReport::class,
                RsmiReport::class,
                UserProfile::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                DashboardStatsOverviewWidget::class,
                PrStatusChartWidget::class,
                ProcurementStatusChartWidget::class,
                LowStockSuppliesWidget::class,
                RecentTransactionsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        // Auto-refresh sidebar navigation badges every 60 seconds
        // This ensures the pending PR count badge stays up-to-date
        // without requiring manual page navigation.
        FilamentView::registerRenderHook(
            'panels::body.end',
            fn (): string => Blade::render(<<<'HTML'
                <div
                    x-data="{}"
                    x-init="setInterval(() => { $wire.$refresh() }, 60000)"
                    style="display: none"
                ></div>
            HTML),
        );
    }
}
