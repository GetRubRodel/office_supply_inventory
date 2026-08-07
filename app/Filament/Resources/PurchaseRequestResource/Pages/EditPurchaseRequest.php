<?php

namespace App\Filament\Resources\PurchaseRequestResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected ?string $maxContentWidth = '4xl';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $user = CurrentUser::get();

        // Staff: only enforce ownership check for their own records
        if ($user && $user->isOwnRecordsOnly() && $this->record->user_id !== $user->id) {
            Notification::make()
                ->warning()
                ->title('Unauthorized')
                ->body('You are not authorized to edit this Purchase Request.')
                ->persistent()
                ->send();
            $this->redirect($this->getResource()::getUrl('index'));

            return;
        }

        // Only admin or the original drafter can edit
        if ($user && !$user->canManageProcurement() && $this->record->status !== PurchaseRequest::STATUS_DRAFT) {
            Notification::make()
                ->warning()
                ->title('Cannot edit')
                ->body('Only draft PRs can be edited. Submit the PR for review or contact an admin.')
                ->persistent()
                ->send();
            $this->redirect($this->getResource()::getUrl('index'));

            return;
        }

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
                ->label('Print PR')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('pr.print', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
