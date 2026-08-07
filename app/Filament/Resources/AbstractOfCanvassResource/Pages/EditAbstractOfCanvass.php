<?php

namespace App\Filament\Resources\AbstractOfCanvassResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\AbstractOfCanvassResource;
use App\Models\Supplier;
use App\Services\AbstractOfCanvassService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAbstractOfCanvass extends EditRecord
{
    protected static string $resource = AbstractOfCanvassResource::class;

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
                ->label('Print ABC')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('abc.print', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $totals = AbstractOfCanvassService::supplierTotalsFromItems($data['items'] ?? []);
        $winnerIndex = AbstractOfCanvassService::lowestTotalIndex($totals);
        $tiedIndexes = AbstractOfCanvassService::tiedLowestIndexes($totals);

        $supplierNames = [
            $data['supplier1_name'] ?? null,
            $data['supplier2_name'] ?? null,
            $data['supplier3_name'] ?? null,
        ];
        $computedWinnerName = $supplierNames[$winnerIndex] ?? null;

        // Prefer the user's manual selection when provided (used for tie-breaks
        // and legitimate overrides); otherwise resolve the winner from the
        // lowest total quotation.
        $winningSupplier = null;
        if (! empty($data['winning_supplier_id'])) {
            $winningSupplier = Supplier::find($data['winning_supplier_id']);
        }

        if (! $winningSupplier && $computedWinnerName) {
            $winningSupplier = AbstractOfCanvassService::resolveSupplierByName($computedWinnerName);
        }

        $data['winning_supplier_id'] = $winningSupplier?->id;
        $data['recommendation'] = $winningSupplier?->name
            ?? $computedWinnerName
            ?? ($data['recommendation'] ?? '');

        if (count($tiedIndexes) > 1) {
            Notification::make()
                ->warning()
                ->title('Tied lowest quotation')
                ->body('Two or more suppliers are tied at the lowest total quotation. Verify the Winning Supplier selection before finalizing this ABC.')
                ->persistent()
                ->send();
        }

        return $data;
    }
}
