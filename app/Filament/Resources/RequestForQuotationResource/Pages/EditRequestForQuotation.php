<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\RequestForQuotationResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRequestForQuotation extends EditRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected ?string $maxContentWidth = '4xl';

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
                ->label('Print RFQ')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('rfq.print', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
