<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PrRejected extends Notification
{
    use Queueable;

    public function __construct(
        public PurchaseRequest $purchaseRequest,
        public string $rejectionReason,
        public string $rejectedByName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $pr = $this->purchaseRequest;

        $rejectedBy = $this->rejectedByName;
        $designation = $pr->rejected_by_designation;
        $rejectedAt = $pr->rejected_at;

        // Filament only lists database notifications whose data carries
        // `format => 'filament'` in the bell modal, and renders them from the
        // title/body/status/icon/actions keys. The body is sanitized HTML, so
        // safe tags (<strong>, <br>) are allowed while user-entered values
        // (approver name, designation, remarks) are escaped.
        $body = 'Your Purchase Request <strong>' . e($pr->pr_no) . '</strong> has been rejected.';

        if (filled($rejectedBy)) {
            $body .= '<br><strong>Rejected By:</strong> ' . e($rejectedBy)
                . (filled($designation) ? ' (' . e($designation) . ')' : '');
        }

        if ($rejectedAt) {
            $body .= '<br><strong>Rejection Date:</strong> ' . e($rejectedAt->format('F j, Y g:i A'));
        }

        $body .= '<br><strong>Reason:</strong> ' . e($this->rejectionReason);

        return [
            // Filament database-notification contract
            'format' => 'filament',
            'title' => 'Purchase Request Rejected',
            'body' => $body,
            'status' => 'danger',
            'icon' => 'heroicon-o-x-circle',
            'iconColor' => 'danger',
            'color' => 'danger',
            'duration' => 'persistent',
            'actions' => [
                [
                    'name' => 'view_pr',
                    'label' => 'View PR',
                    'url' => "/admin/purchase-requests/{$pr->id}",
                    'color' => 'primary',
                    'shouldMarkAsRead' => true,
                ],
            ],
            // Structured payload for reference / future use
            'purchase_request_id' => $pr->id,
            'pr_no' => $pr->pr_no,
            'rejected_by' => $rejectedBy,
            'rejected_by_designation' => $designation,
            'rejected_at' => $rejectedAt?->toDateTimeString(),
            'rejection_reason' => $this->rejectionReason,
            'message' => "Your Purchase Request {$pr->pr_no} has been rejected by {$rejectedBy}. Reason: {$this->rejectionReason}",
        ];
    }
}