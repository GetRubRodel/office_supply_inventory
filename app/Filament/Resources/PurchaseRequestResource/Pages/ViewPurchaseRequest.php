<?php

namespace App\Filament\Resources\PurchaseRequestResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseRequest extends ViewRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected ?string $maxContentWidth = '4xl';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Any role holding purchase_requests.view may open any purchase
        // request — Staff track the full pipeline read-only. Write access
        // remains gated by the policy and the per-action visibility checks.
    }

    protected function getHeaderActions(): array
    {
        return [
            // Submit for Review (original requester or Administrator only)
            Actions\Action::make('submit')
                ->label('Submit for Review')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('warning')
                ->visible(fn (): bool =>
                    CurrentUser::get()?->can('submit', $this->record)
                    && $this->record->canTransitionTo(PurchaseRequest::STATUS_FOR_REVIEW))
                ->requiresConfirmation()
                ->modalHeading('Submit Purchase Request')
                ->modalDescription('Are you sure you want to submit this PR for review? It will no longer be editable.')
                ->action(function () {
                    abort_unless(CurrentUser::get()?->can('submit', $this->record), 403);
                    $this->record->transitionTo(PurchaseRequest::STATUS_FOR_REVIEW);
                    Notification::make()
                        ->success()
                        ->title('PR Submitted')
                        ->body("PR {$this->record->pr_no} has been submitted for review.")
                        ->send();
                    $this->refreshFormData(['status', 'requested_by_name', 'requested_by_designation']);
                }),

            // Approve (Admin, Division Chief for own division)
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool =>
                    CurrentUser::get()?->canApprovePurchaseRequest($this->record)
                    && $this->record->canTransitionTo(PurchaseRequest::STATUS_APPROVED))
                ->requiresConfirmation()
                ->modalHeading('Approve Purchase Request')
                ->modalDescription('Are you sure you want to approve this PR? This action cannot be undone.')
                ->action(function () {
                    abort_unless(CurrentUser::get()?->canApprovePurchaseRequest($this->record), 403);
                    $this->record->transitionTo(PurchaseRequest::STATUS_APPROVED);
                    Notification::make()
                        ->success()
                        ->title('PR Approved')
                        ->body("PR {$this->record->pr_no} has been approved.")
                        ->send();
                    $this->refreshFormData(['status', 'approved_by_name', 'approved_by_designation']);
                }),

            // Reject (Admin, Regional Director)
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool =>
                    (CurrentUser::get()?->isAdmin() || CurrentUser::get()?->isRegionalDirector())
                    && $this->record->canTransitionTo(PurchaseRequest::STATUS_REJECTED))
                ->requiresConfirmation()
                ->modalHeading('Reject Purchase Request')
                ->modalDescription('Please provide a reason for rejecting this PR. The requester will be notified.')
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Remarks / Reason')
                        ->required()
                        ->rows(4)
                        ->placeholder('Explain why this purchase request is being rejected...')
                        ->helperText('This will be shown to the requester.'),
                ])
                ->action(function (array $data) {
                    abort_unless(CurrentUser::get()?->isAdmin() || CurrentUser::get()?->isRegionalDirector(), 403);
                    $this->record->transitionTo(PurchaseRequest::STATUS_REJECTED, $data['rejection_reason']);

                    Notification::make()
                        ->success()
                        ->title('PR Rejected')
                        ->body("PR {$this->record->pr_no} has been rejected.")
                        ->send();
                    $this->refreshFormData(['status', 'rejection_reason', 'rejected_by_name', 'rejected_by_designation', 'rejected_at']);
                }),

            // Revise (rejected → draft) — roles with update permission only
            Actions\Action::make('revise')
                ->label('Revise')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->visible(fn (): bool =>
                    CurrentUser::get()?->hasPermissionTo('purchase_requests.update')
                    && ! CurrentUser::get()?->isRegionalDirector()
                    && ! CurrentUser::get()?->isDivisionChief()
                    && $this->record->canTransitionTo(PurchaseRequest::STATUS_DRAFT))
                ->requiresConfirmation()
                ->modalHeading('Revise Purchase Request')
                ->modalDescription('Re-open this PR for editing as a draft.')
                ->action(function () {
                    abort_unless(CurrentUser::get()?->hasPermissionTo('purchase_requests.update'), 403);
                    $this->record->transitionTo(PurchaseRequest::STATUS_DRAFT);
                    Notification::make()
                        ->info()
                        ->title('PR Re-opened')
                        ->body("PR {$this->record->pr_no} has been returned to draft for revision.")
                        ->send();
                    $this->refreshFormData(['status']);
                }),

            // Cancel (Admin, Division Chief for own division)
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-stop-circle')
                ->color('danger')
                ->visible(fn (): bool =>
                    CurrentUser::get()?->canApprovePurchaseRequest($this->record)
                    && $this->record->canTransitionTo(PurchaseRequest::STATUS_APPROVED))
                ->requiresConfirmation()
                ->modalHeading('Cancel Purchase Request')
                ->modalDescription('Are you sure you want to cancel this PR? This action cannot be undone.')
                ->action(function () {
                    abort_unless(CurrentUser::get()?->canApprovePurchaseRequest($this->record), 403);
                    $this->record->transitionTo(PurchaseRequest::STATUS_CANCELLED);
                    Notification::make()
                        ->success()
                        ->title('PR Cancelled')
                        ->body("PR {$this->record->pr_no} has been cancelled.")
                        ->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('print')
                ->label('Print PR')
                ->icon('heroicon-o-printer')
                ->visible(fn (): bool => ! CurrentUser::get()?->isRegionalDirector() && ! CurrentUser::get()?->isStaff() && ! CurrentUser::get()?->isDivisionChief())
                ->url(fn () => route('pr.print', $this->record))
                ->openUrlInNewTab(),
            Actions\EditAction::make()
                ->visible(fn (): bool =>
                    CurrentUser::get()?->hasPermissionTo('purchase_requests.update')
                    && ! CurrentUser::get()?->isRegionalDirector()
                    && ! CurrentUser::get()?->isDivisionChief()
                    && ! CurrentUser::get()?->isSupplyOfficer()
                    && (CurrentUser::get()?->canManageProcurement() || $this->record->status === PurchaseRequest::STATUS_DRAFT)),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => CurrentUser::get()?->isAdmin()),
        ];
    }
}
