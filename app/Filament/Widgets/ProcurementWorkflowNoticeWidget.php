<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AbstractOfCanvassResource;
use App\Filament\Resources\InspectionAcceptanceResource;
use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\RequestForQuotationResource;
use Filament\Widgets\Widget;

/**
 * Renders a warning banner on a procurement List page when there is no
 * eligible source document available to convert into the next workflow step.
 */
class ProcurementWorkflowNoticeWidget extends Widget
{
    protected static string $view = 'filament.widgets.procurement-workflow-notice';

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
                'body' => 'No approved Purchase Request is available. Please create an approved Purchase Request before creating a Request for Quotation.',
            ],
            'abc' => [
                'eligible' => AbstractOfCanvassResource::hasEligibleSource(),
                'title' => 'No Request for Quotation available',
                'body' => 'No Request for Quotation is available. Please create a Request for Quotation before creating an Abstract of Bids and Canvass.',
            ],
            'po' => [
                'eligible' => PurchaseOrderResource::hasEligibleSource(),
                'title' => 'No Abstract of Bids and Canvass available',
                'body' => 'No Abstract of Bids and Canvass is available. Please create an Abstract of Bids and Canvass before creating a Purchase Order.',
            ],
            'iar' => [
                'eligible' => InspectionAcceptanceResource::hasEligibleSource(),
                'title' => 'No Purchase Order available',
                'body' => 'No Purchase Order is available. Please create a Purchase Order before creating an Inspection and Acceptance Report.',
            ],
            default => ['eligible' => true, 'title' => '', 'body' => ''],
        };

        $this->blocked = ! $config['eligible'];
        $this->noticeTitle = $config['title'];
        $this->noticeBody = $config['body'];
    }
}
