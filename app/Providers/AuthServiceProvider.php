<?php

namespace App\Providers;

use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\Category;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\Requisition;
use App\Models\Rpci;
use App\Models\StockInRecord;
use App\Models\Supply;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => \App\Policies\UserPolicy::class,
        Supply::class => \App\Policies\SupplyPolicy::class,
        Supplier::class => \App\Policies\SupplierPolicy::class,
        Category::class => \App\Policies\CategoryPolicy::class,
        PurchaseRequest::class => \App\Policies\PurchaseRequestPolicy::class,
        RequestForQuotation::class => \App\Policies\RequestForQuotationPolicy::class,
        AbstractOfCanvass::class => \App\Policies\AbstractOfCanvassPolicy::class,
        PurchaseOrder::class => \App\Policies\PurchaseOrderPolicy::class,
        InspectionAcceptanceReport::class => \App\Policies\InspectionAcceptanceReportPolicy::class,
        BacResolution::class => \App\Policies\BacResolutionPolicy::class,
        Requisition::class => \App\Policies\RequisitionPolicy::class,
        Rpci::class => \App\Policies\RpciPolicy::class,
        StockInRecord::class => \App\Policies\StockInRecordPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // ─── Gate Definitions for Pages/Widgets ────────────────────
        Gate::define('view-dashboard', fn ($user) => $user->hasPermissionTo('dashboard.view'));

        Gate::define('view-rsmi-report', fn ($user) => $user->hasPermissionTo('rsmi_reports.view'));
        Gate::define('print-rsmi-report', fn ($user) => $user->hasPermissionTo('rsmi_reports.print'));

        Gate::define('view-inventory-report', fn ($user) => $user->hasPermissionTo('inventory_reports.view'));
        Gate::define('print-inventory-report', fn ($user) => $user->hasPermissionTo('inventory_reports.print'));

        Gate::define('view-audit-logs', fn ($user) => $user->hasPermissionTo('audit_logs.view'));
        Gate::define('manage-system-settings', fn ($user) => $user->hasPermissionTo('system_settings.view'));
    }
}
