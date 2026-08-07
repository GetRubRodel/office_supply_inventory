<?php

namespace App\Filament\Resources\RequisitionResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\RequisitionResource;
use App\Models\Supply;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ViewRequisition extends ViewRecord
{
    protected static string $resource = RequisitionResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $user = CurrentUser::get();
        if ($user && $user->isOwnRecordsOnly() && $this->record->user_id !== $user->id) {
            abort(403, 'You are not authorized to view this Requisition.');
        }
    }

    protected function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = CurrentUser::get();

        if (! $user) return $query;

        if ($user->isOwnRecordsOnly()) {
            $query->where('user_id', $user->id);
        }
        // Division Chief and all other roles see all RIS records.

        return $query;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Approve: requested → approved
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => CurrentUser::get()?->canApproveRequisition($this->record)
                    && $this->record->canTransitionTo(\App\Models\Requisition::STATUS_APPROVED))
                ->requiresConfirmation()
                ->modalHeading('Approve Requisition')
                ->modalDescription('Are you sure you want to approve this requisition?')
                ->action(function () {
                    abort_unless(CurrentUser::get()?->canApproveRequisition($this->record), 403);
                    $this->record->transitionTo(\App\Models\Requisition::STATUS_APPROVED);
                    Notification::make()
                        ->success()
                        ->title('Requisition Approved')
                        ->body("RIS {$this->record->ris_no} has been approved.")
                        ->send();
                }),

            // Issue: approved → issued → pending_receipt (deducts stock)
            Actions\Action::make('issue')
                ->label('Issue')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->visible(fn () => CurrentUser::get()?->canIssueItems() && $this->record->canTransitionTo(\App\Models\Requisition::STATUS_ISSUED))
                ->requiresConfirmation()
                ->modalHeading('Issue Items')
                ->modalDescription('This will deduct the issued quantities from inventory and set the status to Pending Receipt. Are you sure?')
                ->action(function () {
                    abort_unless(CurrentUser::get()?->canIssueItems(), 403);

                    DB::transaction(function () {
                        foreach ($this->record->items as $item) {
                            if ($item->quantity_issued > 0 && $item->supply_id) {
                                $supply = Supply::findOrFail($item->supply_id);
                                $qtyToIssue = (int) $item->quantity_issued;

                                if ($qtyToIssue > $supply->current_stock) {
                                    throw new \RuntimeException(
                                        "Insufficient stock for \"{$supply->name}\". " .
                                        "Required: {$qtyToIssue}, Available: {$supply->current_stock} {$supply->unit}."
                                    );
                                }

                                // Mark stock as available and persist the issued quantity
                                $item->update([
                                    'stock_available' => true,
                                    'quantity_issued' => $qtyToIssue,
                                ]);

                                // Deduct current stock and total_cost (unit_cost/MAC unchanged)
                                $supply->issueStock($qtyToIssue, $this->record->ris_no);
                            }
                        }

                        // Transition to issued (records who issued)
                        $this->record->transitionTo(\App\Models\Requisition::STATUS_ISSUED);
                    });

                    // Auto-transition to pending_receipt
                    $this->record->transitionTo(\App\Models\Requisition::STATUS_PENDING_RECEIPT);

                    // Notify the requester
                    $requester = $this->record->user;
                    if ($requester) {
                        $requester->notify(new \App\Notifications\RisReadyForReceipt($this->record));
                    }

                    Notification::make()
                        ->success()
                        ->title('Items Issued')
                        ->body("RIS {$this->record->ris_no} has been issued and set to Pending Receipt. The requester has been notified.")
                        ->send();
                }),

            // Receive: pending_receipt → received (only original requester)
            Actions\Action::make('receive')
                ->label('Receive')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->visible(fn () => $this->record->status === \App\Models\Requisition::STATUS_PENDING_RECEIPT
                    && CurrentUser::get()?->canReceiveRequisition($this->record))
                ->requiresConfirmation()
                ->modalHeading('Confirm Receipt')
                ->modalDescription('Confirm that the requested items have been received.')
                ->action(function () {
                    $user = CurrentUser::get();
                    abort_unless($user && $user->canReceiveRequisition($this->record), 403);
                    $this->record->transitionTo(\App\Models\Requisition::STATUS_RECEIVED);
                    Notification::make()
                        ->success()
                        ->title('Items Received')
                        ->body("RIS {$this->record->ris_no} has been marked as received. The transaction is now complete.")
                        ->send();
                }),

            // Cancel: pending approval → cancelled (Admin, Division Chief for own division)
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () =>
                    CurrentUser::get()?->canApproveRequisition($this->record)
                    && $this->record->canTransitionTo(\App\Models\Requisition::STATUS_APPROVED))
                ->requiresConfirmation()
                ->modalHeading('Cancel Requisition')
                ->modalDescription('Are you sure you want to cancel this requisition?')
                ->action(function () {
                    abort_unless(CurrentUser::get()?->canApproveRequisition($this->record), 403);
                    $this->record->transitionTo(\App\Models\Requisition::STATUS_CANCELLED);
                    Notification::make()
                        ->success()
                        ->title('Requisition Cancelled')
                        ->body("RIS {$this->record->ris_no} has been cancelled.")
                        ->send();
                }),

            Actions\EditAction::make()
                ->visible(fn (): bool =>
                    CurrentUser::get()?->hasPermissionTo('requisitions.update')
                    && $this->record->status === 'requested'),
            Actions\Action::make('print')
                ->label('Print RIS')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('ris.print', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
