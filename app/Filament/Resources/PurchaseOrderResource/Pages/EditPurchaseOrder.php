<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected ?string $maxContentWidth = '7xl';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Authorization check: only users with procurement management can edit
        if (!CurrentUser::get()?->canManageProcurement()) {
            Notification::make()
                ->warning()
                ->title('Access Denied')
                ->body('You do not have permission to edit procurement records.')
                ->persistent()
                ->send();
            $this->redirect($this->getResource()::getUrl('index'));
            return;
        }

        $user = CurrentUser::get();
        if ($user && !$this->record->acquireEditingLock($user)) {
            $editor = $this->record->editingUser?->name ?? 'another user';
            Notification::make()
                ->warning()
                ->title('Currently being edited')
                ->body("{$editor} is already editing this record.")
                ->persistent()
                ->send();
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function afterSave(): void
    {
        $this->record->releaseEditingLock();
    }

    protected function afterDelete(): void
    {
        if ($this->record) {
            $this->record->releaseEditingLock();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print PO')
                ->icon('heroicon-o-printer')
                ->visible(fn (): bool => ! CurrentUser::get()?->isRegionalDirector() && ! CurrentUser::get()?->isStaff() && ! CurrentUser::get()?->isDivisionChief())
                ->url(fn () => route('po.print', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => ! CurrentUser::get()?->isSupplyOfficer()),
        ];
    }

    /**
     * Filament only applies the form's ->default() values when the form is
     * hydrated with no state (the create page). Purchase Orders that stored
     * nothing for the fixed-choice fields are therefore filled with the
     * same defaults here, so a legacy record without e.g. a mode of
     * procurement can still be saved.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (PurchaseOrderResource::FIELD_DEFAULTS as $field => $default) {
            if (blank($data[$field] ?? null)) {
                $data[$field] = $default;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Backend guard: reject any request that adds or removes items
        // relative to the items already attached to this Purchase Order.
        // This blocks direct URLs, crafted API/Livewire payloads, or any
        // other attempt to inject extra items into the Purchase Order.
        $items = $this->data['items'] ?? [];
        PurchaseOrderResource::validateItemsCount($items, $this->record->items()->count());

        // The items Repeater is relationship-based, so Filament does not
        // dehydrate its items into $data (Repeater::relationship() sets
        // dehydrated(false)). Read the items from the live form state instead.
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
}
