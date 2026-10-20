<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AbstractOfCanvassResource;
use App\Filament\Resources\InspectionAcceptanceResource;
use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\RequestForQuotationResource;
use App\Models\AbstractOfCanvass;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\RequestForQuotation;
use Filament\Widgets\Widget;

/**
 * Renders a warning banner on a procurement List page when there is no
 * eligible source document available to convert into the next workflow step.
 */
class ProcurementWorkflowNoticeWidget extends Widget
{
    protected static string $view = 'filament.widgets.procurement-workflow-notice';

    /**
     * Render inline (server-side) instead of lazy-loading over Livewire.
     * This is a validation notice explaining why creation is blocked — it
     * must always be visible immediately, without depending on a
     * JavaScript-triggered follow-up request.
     */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public string $target = '';

    public bool $blocked = false;

    public string $noticeTitle = '';

    public string $noticeBody = '';

    public function mount(string $target = ''): void
    {
        $this->target = $target;

        $config = match ($target) {
            'rfq' => [
                'eligible' => RequestForQuotationResource::hasEligibleSource(),
                'title' => 'No approved Purchase Request available',
                'body' => RequestForQuotation::NO_ELIGIBLE_PR_MESSAGE,
            ],
            'abc' => [
                'eligible' => AbstractOfCanvassResource::hasEligibleSource(),
                'title' => 'No Request for Quotation available',
                'body' => AbstractOfCanvass::NO_ELIGIBLE_RFQ_MESSAGE,
            ],
            'po' => [
                'eligible' => PurchaseOrderResource::hasEligibleBacResolution(),
                'title' => 'No BAC Resolution available',
                'body' => PurchaseOrder::NO_ELIGIBLE_BAC_MESSAGE,
            ],
            'iar' => [
                'eligible' => InspectionAcceptanceResource::hasEligibleSource(),
                'title' => 'No Purchase Order available',
                'body' => InspectionAcceptanceReport::NO_ELIGIBLE_PO_MESSAGE,
            ],
            default => ['eligible' => true, 'title' => '', 'body' => ''],
        };

        $this->blocked = ! $config['eligible'];
        $this->noticeTitle = $config['title'];
        $this->noticeBody = $config['body'];
    }
}
