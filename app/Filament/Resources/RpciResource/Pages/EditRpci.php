<?php

namespace App\Filament\Resources\RpciResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\RpciResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRpci extends EditRecord
{
    protected static string $resource = RpciResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Prevent editing when status is finalized.
     */
    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->isFinalized()) {
            Notification::make()
                ->danger()
                ->title('Cannot Edit')
                ->body('This RPCI report has been finalized and cannot be edited.')
                ->send();

            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print RPCI')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('rpci.print', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('rpci.preview', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('finalize')
                ->label('Finalize')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn () =>
                    ! CurrentUser::get()?->isRegionalDirector() && ! $this->record->isFinalized())
                ->requiresConfirmation()
                ->modalHeading('Finalize RPCI Report')
                ->modalDescription('Are you sure you want to finalize this report? Once finalized, it cannot be edited or deleted.')
                ->action(function () {
                    $this->record->update(['status' => 'finalized']);
                    Notification::make()
                        ->success()
                        ->title('Report Finalized')
                        ->body("RPCI {$this->record->report_no} has been finalized.")
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
            Actions\DeleteAction::make()
                ->visible(fn () =>
                    ! CurrentUser::get()?->isRegionalDirector() && ! $this->record->isFinalized()),
            Actions\ViewAction::make(),
        ];
    }

    /**
     * After updating, re-compute shortage/overage for each item.
     */
    protected function afterSave(): void
    {
        $this->record->load('items');
        foreach ($this->record->items as $item) {
            $item->computeShortageOverage();
            $item->save();
        }
    }
}
