<?php

namespace App\Notifications;

use App\Models\Requisition;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the ORIGINAL REQUESTER only when their Requisition (RIS) is
 * cancelled by the Division Chief or Administrator.
 *
 * The notification drives the Notification Bell badge: Filament only counts
 * and lists database notifications whose data carries `format => 'filament'`
 * (see Filament\Notifications\Livewire\DatabaseNotifications), and the badge
 * clears automatically once the notification is marked as read — either via
 * the "View RIS" action (shouldMarkAsRead) or the bell's "Mark all as read".
 */
class RisCancelled extends Notification
{
    use Queueable;

    public function __construct(
        public Requisition $requisition,
        public string $cancellationRemarks,
        public string $cancelledByName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $ris = $this->requisition;

        $cancelledBy = $this->cancelledByName;
        $designation = $ris->cancelled_by_designation;
        $cancelledAt = $ris->cancelled_by_date;

        // Filament only lists database notifications whose data carries
        // `format => 'filament'` in the bell modal, and renders them from the
        // title/body/status/icon/actions keys. The body is sanitized HTML, so
        // safe tags (<strong>, <br>) are allowed while user-entered values
        // (canceller name, designation, remarks) are escaped.
        $body = 'Your Requisition <strong>' . e($ris->ris_no) . '</strong> has been cancelled.';

        $body .= '<br><strong>Status:</strong> Cancelled';

        if (filled($cancelledBy)) {
            $body .= '<br><strong>Cancelled By:</strong> ' . e($cancelledBy)
                . (filled($designation) ? ' (' . e($designation) . ')' : '');
        }

        if ($cancelledAt) {
            $body .= '<br><strong>Cancellation Date:</strong> ' . e($cancelledAt->format('F j, Y'));
        }

        $body .= '<br><strong>Remarks:</strong> '
            . e(filled($this->cancellationRemarks) ? $this->cancellationRemarks : 'Not provided');

        return [
            // Filament database-notification contract
            'format' => 'filament',
            'title' => 'Requisition Cancelled',
            'body' => $body,
            'status' => 'danger',
            'icon' => 'heroicon-o-x-circle',
            'iconColor' => 'danger',
            'color' => 'danger',
            'duration' => 'persistent',
            'actions' => [
                [
                    'name' => 'view_ris',
                    'label' => 'View RIS',
                    'url' => "/admin/requisitions/{$ris->id}",
                    'color' => 'primary',
                    // Opening the RIS from the bell marks the notification
                    // read, which clears the unread badge automatically.
                    'shouldMarkAsRead' => true,
                ],
            ],
            // Structured payload for reference / future use
            'requisition_id' => $ris->id,
            'ris_no' => $ris->ris_no,
            'requisition_status' => Requisition::STATUS_CANCELLED,
            'status_label' => 'Cancelled',
            'cancelled_by' => $cancelledBy,
            'cancelled_by_designation' => $designation,
            'cancelled_at' => $cancelledAt?->toDateString(),
            'cancellation_remarks' => $this->cancellationRemarks,
            'message' => "Your Requisition {$ris->ris_no} has been cancelled"
                . (filled($cancelledBy) ? " by {$cancelledBy}" : '')
                . '. Remarks: ' . (filled($this->cancellationRemarks) ? $this->cancellationRemarks : 'Not provided'),
        ];
    }
}
