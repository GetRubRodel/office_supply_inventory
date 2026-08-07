<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected ?string $maxContentWidth = '7xl';

    public function mount(): void
    {
        if (! PurchaseOrderResource::hasEligibleSource()) {
            Notification::make()
                ->warning()
                ->title('Cannot create Purchase Order')
                ->body('No Abstract of Bids and Canvass is available. Please create an Abstract of Bids and Canvass before creating a Purchase Order.')
                ->send();

            $this->redirect(PurchaseOrderResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Compute total_amount and amount_in_words from items
        $items = $data['items'] ?? [];
        $total = 0;
        foreach ($items as &$item) {
            $qty = (float) ($item['quantity'] ?? 1);
            $cost = (float) ($item['unit_cost'] ?? 0);
            $amt = round($qty * $cost, 2);
            $item['amount'] = $amt;
            $total += $amt;
        }
        $data['items'] = $items;
        $data['total_amount'] = round($total, 2);
        $data['amount_in_words'] = PurchaseOrder::numberToWords($total);

        return $data;
    }
}
