<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\BacResolution;
use App\Models\PurchaseOrder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected ?string $maxContentWidth = '7xl';

    /**
     * Prevent direct access (URL / API / navigation) to the Create page when
     * there is no eligible BAC Resolution available as the source document.
     */
    public function mount(): void
    {
        if (! PurchaseOrderResource::hasEligibleBacResolution()) {
            Notification::make()
                ->warning()
                ->title('Cannot create Purchase Order')
                ->body(PurchaseOrder::NO_ELIGIBLE_BAC_MESSAGE)
                ->persistent()
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Backend guard: reject any request whose items do not match the
        // source BAC Resolution's items_snapshot. This blocks direct URLs,
        // crafted API/Livewire payloads, or any other attempt to inject
        // extra items into the Purchase Order.
        $items = $this->data['items'] ?? [];
        if (filled($data['bac_resolution_id'] ?? null)) {
            PurchaseOrderResource::validateItemsMatchBacSnapshot($items, $data['bac_resolution_id']);
        }

        return $this->computeTotals($data);
    }

    /**
     * Compute total_amount and amount_in_words from items.
     *
     * The items Repeater is relationship-based, so Filament does not dehydrate
     * its items into $data (Repeater::relationship() sets dehydrated(false)).
     * Read the items from the live form state instead.
     */
    private function computeTotals(array $data): array
    {
        $items = $this->data['items'] ?? [];
        $total = 0;
        foreach ($items as &$item) {
            $qty = (float) ($item['quantity'] ?? 1);
            $cost = (float) ($item['unit_cost'] ?? 0);
            $amt = round($qty * $cost, 2);
            $item['amount'] = $amt;
            $total += $amt;
        }
        $data['total_amount'] = round($total, 2);
        $data['amount_in_words'] = PurchaseOrder::numberToWords($total);

        return $data;
    }

    /**
     * Create the Purchase Order inside a transaction, locking the source BAC
     * Resolution row so two concurrent submissions can never convert the same
     * BAC Resolution twice.
     *
     * On database-level unique constraint violations the retry logic only
     * applies to po_no collisions; an already-converted BAC Resolution
     * (unique bac_resolution_id index) fails fast with a clear validation
     * error.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $maxAttempts = 5;
        $attempt = 0;

        do {
            $attempt++;

            try {
                return DB::transaction(function () use ($data) {
                    /** @var BacResolution|null $bac */
                    $bac = BacResolution::query()
                        ->whereKey($data['bac_resolution_id'] ?? null)
                        ->lockForUpdate()
                        ->first();

                    if (! $bac || $bac->purchaseOrders()->exists()) {
                        throw ValidationException::withMessages([
                            'bac_resolution_id' => PurchaseOrder::NO_ELIGIBLE_BAC_MESSAGE,
                        ]);
                    }

                    return static::getModel()::create($data);
                });
            } catch (UniqueConstraintViolationException $e) {
                // Another Purchase Order already converted this BAC Resolution
                // (unique bac_resolution_id index).
                if (isset($data['bac_resolution_id']) && static::getModel()::where('bac_resolution_id', $data['bac_resolution_id'])->exists()) {
                    Notification::make()
                        ->danger()
                        ->title('Cannot create Purchase Order')
                        ->body('This BAC Resolution has already been converted into a Purchase Order.')
                        ->persistent()
                        ->send();

                    throw ValidationException::withMessages([
                        'bac_resolution_id' => 'This BAC Resolution has already been converted into a Purchase Order.',
                    ]);
                }

                if ($attempt >= $maxAttempts) {
                    Notification::make()
                        ->danger()
                        ->title('Failed to create Purchase Order')
                        ->body('Unable to generate a unique P.O. number after multiple attempts. Please try again.')
                        ->persistent()
                        ->send();

                    throw $e;
                }

                // Brief pause to let the other transaction complete,
                // then retry — the creating event will generate a fresh number.
                usleep(200_000);
            }
        } while (true);
    }
}
