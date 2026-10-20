<?php

namespace App\Filament\Resources\RequisitionResource\Pages;

use App\Filament\Resources\RequisitionResource;
use App\Models\Supply;
use App\Support\CurrentUser;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditRequisition extends EditRecord
{
    protected static string $resource = RequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Backend authorization on every save attempt. RequisitionPolicy
        // scopes Staff and Regional Directors to their
        // own records; this guard also blocks direct POSTs that bypass
        // Filament's page-level authorization (canEdit).
        abort_unless(CurrentUser::get()?->can('update', $this->record), 403);

        $this->validateStock($data);
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
                $errors["items.{$index}.quantity_requested"] = 'Insufficient stock. The requested quantity cannot exceed the available stock.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
