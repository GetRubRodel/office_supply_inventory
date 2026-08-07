<?php

namespace App\Filament\Resources\RequisitionResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\RequisitionResource;
use App\Models\Supply;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class EditRequisition extends EditRecord
{
    protected static string $resource = RequisitionResource::class;

    protected function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = CurrentUser::get();

        if (! $user) return $query;

        if ($user->isOwnRecordsOnly()) {
            $query->where('user_id', $user->id);
        }
        // Division Chief and all other roles see all RIS records.

        return $query;
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $user = CurrentUser::get();

        // Staff: only enforce ownership check for their own records
        if ($user && $user->isOwnRecordsOnly() && $this->record->user_id !== $user->id) {
            Notification::make()
                ->warning()
                ->title('Unauthorized')
                ->body('You are not authorized to edit this Requisition.')
                ->persistent()
                ->send();
            $this->redirect($this->getResource()::getUrl('index'));

            return;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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
                $errors["items.{$index}.quantity_requested"] = "Insufficient stock for \"{$supply->name}\". Requested: {$qty}, Available: {$supply->current_stock} {$supply->unit}.";
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
