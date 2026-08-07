<?php

namespace App\Filament\Resources\AbstractOfCanvassResource\Pages;

use App\Filament\Resources\AbstractOfCanvassResource;
use App\Models\Supplier;
use App\Services\AbstractOfCanvassService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAbstractOfCanvass extends CreateRecord
{
    protected static string $resource = AbstractOfCanvassResource::class;

    protected ?string $maxContentWidth = '7xl';

    public function mount(): void
    {
        if (! AbstractOfCanvassResource::hasEligibleSource()) {
            Notification::make()
                ->warning()
                ->title('Cannot create Abstract of Bids and Canvass')
                ->body('No Request for Quotation is available. Please create a Request for Quotation before creating an Abstract of Bids and Canvass.')
                ->send();

            $this->redirect(AbstractOfCanvassResource::getUrl('index'));

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
        $totals = AbstractOfCanvassService::supplierTotalsFromItems($data['items'] ?? []);
        $winnerIndex = AbstractOfCanvassService::lowestTotalIndex($totals);
        $tiedIndexes = AbstractOfCanvassService::tiedLowestIndexes($totals);

        $supplierNames = [
            $data['supplier1_name'] ?? null,
            $data['supplier2_name'] ?? null,
            $data['supplier3_name'] ?? null,
        ];
        $computedWinnerName = $supplierNames[$winnerIndex] ?? null;

        // Prefer the user's manual selection when provided (used for tie-breaks
        // and legitimate overrides); otherwise resolve the winner from the
        // lowest total quotation.
        $winningSupplier = null;
        if (! empty($data['winning_supplier_id'])) {
            $winningSupplier = Supplier::find($data['winning_supplier_id']);
        }

        if (! $winningSupplier && $computedWinnerName) {
            $winningSupplier = AbstractOfCanvassService::resolveSupplierByName($computedWinnerName);
        }

        $data['winning_supplier_id'] = $winningSupplier?->id;
        $data['recommendation'] = $winningSupplier?->name
            ?? $computedWinnerName
            ?? ($data['recommendation'] ?? '');

        if (count($tiedIndexes) > 1) {
            Notification::make()
                ->warning()
                ->title('Tied lowest quotation')
                ->body('Two or more suppliers are tied at the lowest total quotation. Verify the Winning Supplier selection before finalizing this ABC.')
                ->persistent()
                ->send();
        }

        return $data;
    }
}
