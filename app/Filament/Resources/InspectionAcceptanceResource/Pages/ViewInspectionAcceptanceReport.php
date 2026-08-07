<?php

namespace App\Filament\Resources\InspectionAcceptanceResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\InspectionAcceptanceResource;
use App\Models\InspectionAcceptanceReport;
use App\Services\StockInService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInspectionAcceptanceReport extends ViewRecord
{
    protected static string $resource = InspectionAcceptanceResource::class;

    protected ?string $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        $actions = [];

        // "Approve & Stock In" action - only for non-stocked-in IARs
        if ($this->record->status !== InspectionAcceptanceReport::STATUS_STOCKED_IN) {
            $actions[] = Actions\Action::make('approveAndStockIn')
                ->label('Approve & Stock In')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool =>
                    ! CurrentUser::get()?->isRegionalDirector()
                    && CurrentUser::get()?->canManageInventory()
                )
                ->action(function () {
                    $iar = $this->record;
                    $iar->loadMissing('items');

                    // Quick pre-validation: at least one accepted item exists
                    $acceptedItems = $iar->items->where('quantity_accepted', '>', 0);

                    if ($acceptedItems->isEmpty()) {
                        Notification::make()
                            ->danger()
                            ->title('Validation Failed')
                            ->body('At least one item must have a quantity accepted greater than 0.')
                            ->send();
                        return;
                    }

                    try {
                        $service = app(StockInService::class);
                        $result = $service->process($iar);

                        Notification::make()
                            ->success()
                            ->title('IAR Approved & Stocked In')
                            ->body($result['message'] . ' The IAR status has been updated to "Stocked In".')
                            ->persistent()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $errors = $e->errors();
                        $firstError = collect($errors)->flatten()->first() ?? 'Validation failed.';

                        Notification::make()
                            ->danger()
                            ->title('Stock In Failed')
                            ->body($firstError)
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Stock In Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Approve & Stock In')
                ->modalDescription('This will approve the IAR, automatically create stock-in records for all accepted items, update inventory quantities, and change the IAR status to "Stocked In". This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Approve & Stock In');
        }

        $actions[] = Actions\Action::make('print')
            ->label('Print IAR')
            ->icon('heroicon-o-printer')
            ->url(fn () => route('iar.print', $this->record))
            ->openUrlInNewTab();

        return $actions;
    }
}
