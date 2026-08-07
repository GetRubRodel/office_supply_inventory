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
                ->url(fn () => route('po.print', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
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
