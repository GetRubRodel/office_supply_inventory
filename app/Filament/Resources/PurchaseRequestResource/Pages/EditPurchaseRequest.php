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

        // Staff may only edit their own purchase requests.
        if ($user && $user->isOwnRecordsOnly() && $this->record->user_id !== $user->id) {
            abort(403, 'You are not authorized to edit this purchase request.');
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
                ->visible(fn (): bool => ! CurrentUser::get()?->isRegionalDirector() && ! CurrentUser::get()?->isStaff() && ! CurrentUser::get()?->isDivisionChief())
                ->url(fn () => route('pr.print', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
