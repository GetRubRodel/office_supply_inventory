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
    abort_unless(
        $user && (
            $requisition->user_id === $user->id
            || (! $user->isOwnRecordsOnly() && $user->hasPermissionTo('requisitions.view'))
        ),
        403,
    );

    $requisition->load('items.supply');
    return view('ris.print', compact('requisition'));
})->name('ris.print')->middleware('auth');

Route::get('/pr/print/{purchaseRequest}', function (PurchaseRequest $purchaseRequest) {
    $user = auth()->user();
    abort_unless(
        $user && (
            $purchaseRequest->user_id === $user->id
            || (! $user->isOwnRecordsOnly() && $user->hasPermissionTo('purchase_requests.view'))
        ),
        403,
    );

    return view('pr.print', compact('purchaseRequest'));
})->name('pr.print')->middleware('auth');

Route::get('/rfq/print/{requestForQuotation}', function (RequestForQuotation $requestForQuotation) {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('request_for_quotations.view'),
        403,
    );

    $rfq = $requestForQuotation;
    return view('rfq.print', compact('rfq'));
})->name('rfq.print')->middleware('auth');

Route::get('/abc/print/{abstractOfCanvass}', function (AbstractOfCanvass $abstractOfCanvass) {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('abstracts_of_canvass.view'),
        403,
    );

    $abc = $abstractOfCanvass->load(['items', 'winningSupplier']);
    return view('abc.print', compact('abc'));
})->name('abc.print')->middleware('auth');

Route::get('/po/print/{purchaseOrder}', function (PurchaseOrder $purchaseOrder) {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('purchase_orders.view'),
        403,
    );

    $po = $purchaseOrder;
    return view('po.print', compact('po'));
})->name('po.print')->middleware('auth');

Route::get('/iar/print/{inspectionAcceptanceReport}', function (InspectionAcceptanceReport $inspectionAcceptanceReport) {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('inspection_acceptance_reports.view'),
        403,
    );

    $iar = $inspectionAcceptanceReport;
    return view('iar.print', compact('iar'));
})->name('iar.print')->middleware('auth');

Route::get('/bac/print/{bacResolution}', function (BacResolution $bacResolution) {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('bac_resolutions.view'),
        403,
    );

    $bac = $bacResolution;
    return view('bac.print', compact('bac'));
})->name('bac.print')->middleware('auth');

Route::get('/rpci/print/{rpci}', function (Rpci $rpci) {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('rpcis.view'),
        403,
    );

    $rpci->load('items.supply.category');
    return view('rpci.print', compact('rpci'));
})->name('rpci.print')->middleware('auth');

Route::get('/rpci/preview/{rpci}', function (Rpci $rpci) {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('rpcis.view'),
        403,
    );

    $rpci->load('items.supply.category');
    return view('rpci.preview', compact('rpci'));
})->name('rpci.preview')->middleware('auth');

Route::get('/rpci/pdf/{rpci}', function (Rpci $rpci) {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('rpcis.view'),
        403,
    );

    $rpci->load('items.supply.category');
    // Render print view as HTML for browser print-to-PDF workflow
    return view('rpci.print', compact('rpci'));
})->name('rpci.pdf')->middleware('auth');

Route::get('/ris/rsmi', function () {
    abort_unless(
        auth()->check() && auth()->user()->hasPermissionTo('rsmi_reports.view'),
        403,
    );
    $dateFrom = request('date_from');
    $dateTo = request('date_to');
    $categoryId = request('category_id');
    $supplyId = request('supply_id');

    $query = App\Models\RequisitionItem::with(['requisition', 'supply'])
        ->whereHas('requisition', function ($q) {
            $q->whereIn('status', ['issued', 'received']);
        });

    if ($dateFrom) {
        $query->whereHas('requisition', fn ($q) => $q->whereDate('issued_by_date', '>=', $dateFrom));
    }
    if ($dateTo) {
        $query->whereHas('requisition', fn ($q) => $q->whereDate('issued_by_date', '<=', $dateTo));
    }
    if ($categoryId) {
        $query->whereHas('supply', fn ($q) => $q->where('category_id', $categoryId));
    }
    if ($supplyId) {
        $query->where('supply_id', $supplyId);
    }

    $items = $query->orderBy('id')->get();

    $recapitulation = $items
        ->groupBy(fn ($item) => $item->supply?->stock_no ?? $item->stock_no)
        ->map(function ($group, $stockNo) {
            $first = $group->first();
            $supply = $first->supply;
            return [
                'stock_number' => $stockNo,
                'quantity' => $group->sum('quantity_issued'),
                'unit_cost' => (float) ($first->price ?? $supply?->unit_price ?? 0),
            ];
        })
        ->values()
        ->toArray();

    $issuedBy = auth()->user()->name ?? null;

    return view('ris.rsmi', compact('items', 'recapitulation', 'dateFrom', 'dateTo', 'issuedBy'));
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
