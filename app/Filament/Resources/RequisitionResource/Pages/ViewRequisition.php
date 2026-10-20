<?php

namespace App\Filament\Resources\RequisitionResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\RequisitionResource;
use App\Models\Supply;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewRequisition extends ViewRecord
{
    protected static string $resource = RequisitionResource::class;

    // Most roles with requisitions.view may open any Requisition and Issue
    // Slip. Staff access is restricted to their own records (scoped query +
    // RequisitionPolicy ownership checks; see header action gates).

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
                    abort_unless(
                        CurrentUser::get()?->hasPermissionTo('requisitions.approve')
                        && CurrentUser::get()?->canApproveRequisition($this->record),
                        403
                    );
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
                ->visible(fn () => CurrentUser::get()?->canIssueRequisition($this->record) && $this->record->canTransitionTo(\App\Models\Requisition::STATUS_ISSUED))
                ->requiresConfirmation()
                ->modalHeading('Issue Items')
                ->modalDescription('This will deduct the issued quantities from inventory and set the status to Pending Receipt. Are you sure?')
                ->action(function () {
                    $user = CurrentUser::get();
                    abort_unless($user && $user->canIssueRequisition($this->record), 403);

                    DB::transaction(function () {
                        foreach ($this->record->items as $item) {
                            if ($item->quantity_issued > 0 && $item->supply_id) {
                                // lockForUpdate guards against concurrent issues
                                // over-deducting the same supply row.
                                $supply = Supply::whereKey($item->supply_id)->lockForUpdate()->firstOrFail();
                                $qtyToIssue = (int) $item->quantity_issued;

                                 if ($qtyToIssue > $supply->current_stock) {
                                     throw new \RuntimeException(
                                         'Insufficient stock. The requested quantity cannot exceed the available stock.'
                                     );
                                 }

                                // Mark stock as available, persist the issued
                                // quantity, and snapshot the MAC at issue time so
                                // RSMI/inventory reports value the issue correctly
                                // even if the MAC changes later.
                                $item->update([
                                    'stock_available' => true,
                                    'quantity_issued' => $qtyToIssue,
                                    'issued_unit_cost' => $supply->unit_cost,
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

            // Receive: issued/pending_receipt → received (only original requester)
            Actions\Action::make('receive')
                ->label('Receive')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->visible(fn () => in_array($this->record->status, [\App\Models\Requisition::STATUS_ISSUED, \App\Models\Requisition::STATUS_PENDING_RECEIPT], true)
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
                    CurrentUser::get()?->canCancelRequisition($this->record)
                    && $this->record->canTransitionTo(\App\Models\Requisition::STATUS_APPROVED))
                ->requiresConfirmation()
                ->modalHeading('Cancel Requisition')
                ->modalDescription('Are you sure you want to cancel this requisition? Any remarks you provide are sent to the requester as a notification.')
                ->form([
                    Forms\Components\Textarea::make('cancellation_remarks')
                        ->label('Cancellation Remarks / Reason')
                        ->rows(4)
                        ->placeholder('Explain why this requisition is being cancelled...')
                        ->helperText('Shown to the original requester in the cancellation notification.'),
                ])
                ->action(function (array $data) {
                    abort_unless(CurrentUser::get()?->canCancelRequisition($this->record), 403);
                    $this->record->transitionTo(
                        \App\Models\Requisition::STATUS_CANCELLED,
                        $data['cancellation_remarks'] ?? null,
                    );
                    Notification::make()
                        ->success()
                        ->title('Requisition Cancelled')
                        ->body("RIS {$this->record->ris_no} has been cancelled."
                            . ($this->record->user ? ' The requester has been notified.' : ''))
                        ->send();
                    $this->refreshFormData([
                        'status',
                        'cancelled_by_name',
                        'cancelled_by_designation',
                        'cancelled_by_date',
                        'cancellation_remarks',
                    ]);
                }),

            Actions\EditAction::make()
                ->visible(function (): bool {
                    $user = CurrentUser::get();

                    if (! $user?->hasPermissionTo('requisitions.update')) {
                        return false;
                    }

                    if ($this->record->status !== 'requested') {
                        return false;
                    }

                    // Staff and Regional Directors may
                    // only edit the RIS requests they submitted themselves.
                    if ($user->isStaff() || $user->isRegionalDirector()) {
                        return $this->record->user_id === $user->id;
                    }

                    return true;
                }),
            Actions\Action::make('print')
                ->label('Print RIS')
                ->icon('heroicon-o-printer')
                ->visible(fn (): bool => ! in_array(CurrentUser::get()?->role, [
                    \App\Models\User::ROLE_STAFF,
                    \App\Models\User::ROLE_DIVISION_CHIEF,
                    \App\Models\User::ROLE_REGIONAL_DIRECTOR,
                ], true))
                ->url(fn () => route('ris.print', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
