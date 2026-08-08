<?php

namespace App\Filament\Resources\RpciResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\RpciResource;
use App\Models\Rpci;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateRpci extends CreateRecord
{
    protected static string $resource = RpciResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Set header defaults. Inventory items are intentionally NOT auto-populated
     * here — the "Retrieve All Items" button on the form imports the latest
     * inventory from the Supplies module as the initial listing.
     */
    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'inventory_type' => 'Common-Office Supplies',
            'status' => 'draft',
            'created_by' => CurrentUser::get()?->name,
            // form->fill() replaces the whole state, so re-apply the
            // report_date default that would otherwise be lost.
            'report_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Before saving, check for duplicate reports.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Check for duplicate report on the same date and inventory type
        $existing = Rpci::where('report_date', $data['report_date'] ?? null)
            ->where('inventory_type', $data['inventory_type'] ?? null)
            ->first();

        if ($existing) {
            Notification::make()
                ->danger()
                ->title('Duplicate Report')
                ->body("An RPCI report for '{$data['inventory_type']}' dated {$data['report_date']} already exists (Report No: {$existing->report_no}). Use the edit function to update it or choose a different date/inventory type.")
                ->send();

            $this->halt();
        }

        // Set created_by if not set
        if (empty($data['created_by'])) {
            $data['created_by'] = CurrentUser::get()?->name;
        }

        return $data;
    }

    /**
     * After creating the record, compute shortage/overage for each item.
     * Items with a blank physical count (on_hand_per_count = null) keep their
     * shortage fields blank.
     */
    protected function afterCreate(): void
    {
        $this->record->load('items');
        foreach ($this->record->items as $item) {
            $item->computeShortageOverage();
            $item->save();
        }
    }
}
