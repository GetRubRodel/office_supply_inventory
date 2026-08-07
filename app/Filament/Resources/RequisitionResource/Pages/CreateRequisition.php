<?php

namespace App\Filament\Resources\RequisitionResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\RequisitionResource;
use App\Models\Supply;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateRequisition extends CreateRecord
{
    protected static string $resource = RequisitionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('RIS Created')
            ->body("RIS request {$this->record->ris_no} has been created successfully.")
            ->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateStock($data);
        $data['user_id'] = CurrentUser::id();
        return $data;
    }

    protected function validateStock(array $data): void
    {
        $errors = [];
        foreach ($data['items'] ?? [] as $index => $item) {
            if (empty($item['supply_id'])) continue;

            $supply = Supply::find($item['supply_id']);
            if (!$supply) continue;

            $qty = (int) ($item['quantity_requested'] ?? 0);
            if ($qty > $supply->current_stock) {
                $errors["items.{$index}.quantity_requested"] = "Insufficient stock for \"{$supply->name}\". Requested: {$qty}, Available: {$supply->current_stock} {$supply->unit}.";
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
