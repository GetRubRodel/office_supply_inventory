<?php

namespace App\Filament\Resources\InspectionAcceptanceResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\InspectionAcceptanceResource;
use App\Models\InspectionAcceptanceReport;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInspectionAcceptanceReport extends EditRecord
{
    protected static string $resource = InspectionAcceptanceResource::class;

    protected ?string $maxContentWidth = '7xl';

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

        // Prevent editing when status is stocked_in
        if ($this->record->isStockedIn()) {
            Notification::make()
                ->warning()
                ->title('Cannot Edit')
                ->body('This IAR has already been stocked in and cannot be edited.')
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
                ->label('Print IAR')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('iar.print', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
