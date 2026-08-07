<?php

namespace App\Filament\Resources\RpciResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\RpciResource;
use App\Models\Rpci;
use App\Models\RpciItem;
use App\Models\Supply;
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
     * Auto-populate items from active supplies on mount, set defaults.
     */
    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'items' => $this->getInventoryItems(),
            'inventory_type' => 'Common-Office Supplies',
            'status' => 'draft',
            'created_by' => CurrentUser::get()?->name,
        ]);
    }

    /**
     * Before saving, check for duplicates.
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

        // Ensure items are populated
        if (! isset($data['items']) || count($data['items']) === 0) {
            $data['items'] = $this->getInventoryItems();
        }

        // Set created_by if not set
        if (empty($data['created_by'])) {
            $data['created_by'] = CurrentUser::get()?->name;
        }

        return $data;
    }

    /**
     * Get all active supplies as default RPCI items, sorted by category then name.
     */
    protected function getInventoryItems(): array
    {
        $supplies = Supply::with('category')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $items = [];
        $sortOrder = 1;

        foreach ($supplies as $supply) {
            $unitValue = (float) (
                $supply->unit_cost > 0
                    ? $supply->unit_cost
                    : ($supply->unit_price > 0 ? $supply->unit_price : 0)
            );

            $items[] = [
                'supply_id' => $supply->id,
                'article' => $supply->category?->name ?? '',
                'description' => $supply->name,
                'stock_number' => $supply->stock_no,
                'unit_of_measure' => $supply->unit,
                'unit_value' => $unitValue,
                'balance_per_card' => $supply->current_stock,
                'on_hand_per_count' => $supply->current_stock,
                'shortage_quantity' => 0,
                'shortage_value' => 0,
                'remarks' => '',
                'sort_order' => $sortOrder++,
            ];
        }

        return $items;
    }

    /**
     * After creating the record, compute shortage/overage for each item.
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
