<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Models\AbstractOfCanvass;
use App\Models\BacResolution;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestForQuotation;
use App\Models\Requisition;
use App\Models\Rpci;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('filament.admin.pages.dashboard');
    }

    return redirect()->route('filament.admin.auth.login');
})->name('home');

Route::get('/ris/print/{requisition}', function (Requisition $requisition) {
    $user = auth()->user();
    abort_unless($user && $user->hasPermissionTo('requisitions.view'), 403);

    // Staff may only print the RIS requests they submitted themselves.
    // Division Chief and Regional Director may not print RIS documents.
    if ($user->isStaff() && $requisition->user_id !== $user->id) {
        abort(403);
    }

    if (in_array($user->role, ['division_chief', 'regional_director'], true)) {
        abort(403);
    }

    // `user` is loaded so the Requesting Party designation resolves from the
    // requester's profile position without an extra query per render.
    $requisition->load(['items.supply', 'user']);
    return view('ris.print', compact('requisition'));
})->name('ris.print')->middleware('auth');

Route::get('/pr/print/{purchaseRequest}', function (PurchaseRequest $purchaseRequest) {
    $user = auth()->user();
    // Any role with purchase_requests.view may print PRs (Staff included —
    // they track the full pipeline read-only).
    abort_unless($user && $user->hasPermissionTo('purchase_requests.view'), 403);

    // Regional Director, Staff, and Division Chief may not print PRs.
    if ($user->isRegionalDirector() || $user->isStaff() || $user->isDivisionChief()) {
        abort(403);
    }

    return view('pr.print', compact('purchaseRequest'));
})->name('pr.print')->middleware('auth');

Route::get('/rfq/print/{requestForQuotation}', function (RequestForQuotation $requestForQuotation) {
    $user = auth()->user();
    abort_unless(
        $user && $user->hasPermissionTo('request_for_quotations.view'),
        403,
    );

    // Regional Director, Staff, and Division Chief may not print RFQs.
    if ($user->isRegionalDirector() || $user->isStaff() || $user->isDivisionChief()) {
        abort(403);
    }

    $rfq = $requestForQuotation;
    return view('rfq.print', compact('rfq'));
})->name('rfq.print')->middleware('auth');

Route::get('/abc/print/{abstractOfCanvass}', function (AbstractOfCanvass $abstractOfCanvass) {
    $user = auth()->user();
    abort_unless(
        $user && $user->hasPermissionTo('abstracts_of_canvass.view'),
        403,
    );

    // Regional Director, Staff, and Division Chief may not print ABCs.
    if ($user->isRegionalDirector() || $user->isStaff() || $user->isDivisionChief()) {
        abort(403);
    }

    $abc = $abstractOfCanvass->load(['items', 'winningSupplier', 'rfq.purchaseRequest']);
    return view('abc.print', compact('abc'));
})->name('abc.print')->middleware('auth');

Route::get('/po/print/{purchaseOrder}', function (PurchaseOrder $purchaseOrder) {
    $user = auth()->user();
    abort_unless(
        $user && $user->hasPermissionTo('purchase_orders.view'),
        403,
    );

    // Regional Director, Staff, and Division Chief may not print POs.
    if ($user->isRegionalDirector() || $user->isStaff() || $user->isDivisionChief()) {
        abort(403);
    }

    $po = $purchaseOrder->load('bacResolution');
    return view('po.print', compact('po'));
})->name('po.print')->middleware('auth');

Route::get('/procurement-monitoring/print', function () {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('procurement_monitoring_reports.print'),
        403,
    );

    $filters = request()->only(['date_from', 'date_to', 'office_division']);
    $rows = app(\App\Services\ProcurementMonitoringService::class)->rows($filters);

    return view('procurement_monitoring.print', compact('rows', 'filters'));
})->name('procurement_monitoring.print')->middleware('auth');

Route::get('/iar/print/{inspectionAcceptanceReport}', function (InspectionAcceptanceReport $inspectionAcceptanceReport) {
    $user = auth()->user();
    abort_unless(
        $user && $user->hasPermissionTo('inspection_acceptance_reports.view'),
        403,
    );

    // Regional Director, Staff, and Division Chief may not print IARs.
    if ($user->isRegionalDirector() || $user->isStaff() || $user->isDivisionChief()) {
        abort(403);
    }

    $iar = $inspectionAcceptanceReport;
    // Supplier / P.O. No. are sourced from the source Purchase Order, so
    // eager-load the chain the model accessors traverse.
    $iar->loadMissing(['po.bacResolution', 'items.supply']);

    return view('iar.print', compact('iar'));
})->name('iar.print')->middleware('auth');

Route::get('/bac/print/{bacResolution}', function (BacResolution $bacResolution) {
    $user = auth()->user();
    abort_unless(
        $user && $user->hasPermissionTo('bac_resolutions.view'),
        403,
    );

    // Regional Director, Staff, and Division Chief may not print BAC Resolutions.
    if ($user->isRegionalDirector() || $user->isStaff() || $user->isDivisionChief()) {
        abort(403);
    }

    $bac = $bacResolution->load('abc');
    return view('bac.print', compact('bac'));
})->name('bac.print')->middleware('auth');

Route::get('/rpci/print/{rpci}', function (Rpci $rpci) {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('rpcis.print'),
        403,
    );

    $rpci->load('items.supply.category');
    return view('rpci.print', compact('rpci'));
})->name('rpci.print')->middleware('auth');

Route::get('/rpci/preview/{rpci}', function (Rpci $rpci) {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('rpcis.preview'),
        403,
    );

    $rpci->load('items.supply.category');
    return view('rpci.preview', compact('rpci'));
})->name('rpci.preview')->middleware('auth');

Route::get('/rpci/pdf/{rpci}', function (Rpci $rpci) {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('rpcis.print'),
        403,
    );

    $rpci->load('items.supply.category');
    // Render print view as HTML for browser print-to-PDF workflow
    return view('rpci.print', compact('rpci'));
})->name('rpci.pdf')->middleware('auth');

Route::get('/ris/rsmi', function () {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('rsmi_reports.print'),
        403,
    );

    // Both the Print and the Download action read their rows from
    // RsmiReportService, so the two outputs can never drift apart.
    $report = app(App\Services\RsmiReportService::class)->build([
        'date_from' => request('date_from'),
        'date_to' => request('date_to'),
        'category_id' => request('category_id'),
        'supply_id' => request('supply_id'),
    ], auth()->user()->name ?? null);

    return view('ris.rsmi', compact('report'));
})->name('ris.rsmi')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Password Reset (OTP) Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('forgot-password', [ForgotPasswordController::class, 'showRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendOtp'])->name('password.otp.send');

    Route::get('verify-otp', [ForgotPasswordController::class, 'showVerifyForm'])->name('password.otp.verify');
    Route::post('verify-otp', [ForgotPasswordController::class, 'verifyOtp'])->name('password.otp.verify.submit');
    Route::post('resend-otp', [ForgotPasswordController::class, 'resendOtp'])->name('password.otp.resend');

    Route::get('reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ForgotPasswordController::class, 'updatePassword'])->name('password.update');

    Route::get('password-reset-success', [ForgotPasswordController::class, 'showSuccess'])->name('password.reset.success');
});
