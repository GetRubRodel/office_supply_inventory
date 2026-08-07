<?php

namespace App\Filament\Resources\BacResolutionResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\BacResolutionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBacResolution extends EditRecord
{
    protected static string $resource = BacResolutionResource::class;

    protected ?string $maxContentWidth = '4xl';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Authorization check: only users with legal management can edit BAC resolutions
        if (!CurrentUser::get()?->canManageLegal()) {
            Notification::make()
                ->warning()
                ->title('Access Denied')
                ->body('You do not have permission to edit BAC resolutions.')
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
                ->label('Print Resolution')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('bac.print', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
