<?php

namespace App\Notifications;

use App\Models\Requisition;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseNotification;
use Illuminate\Notifications\Notification;

class RisReadyForReceipt extends Notification
{
    use Queueable;

    public function __construct(
        public Requisition $requisition,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'requisition_id' => $this->requisition->id,
            'ris_no' => $this->requisition->ris_no,
            'message' => "RIS {$this->requisition->ris_no} has been issued and is ready for receipt. Please confirm receipt of the issued supplies.",
            'status' => 'pending_receipt',
        ];
    }
}
