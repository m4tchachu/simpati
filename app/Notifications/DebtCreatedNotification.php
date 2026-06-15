<?php

namespace App\Notifications;

use App\Models\DebtRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DebtCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private DebtRecord $debtRecord,
        private User $creator,
        private User $counterpart,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'debt_record_id' => $this->debtRecord->id,
            'creator_id' => $this->creator->id,
            'creator_name' => $this->creator->name,
            'amount' => $this->debtRecord->amount,
            'type' => $this->debtRecord->type->label(),
            'description' => $this->debtRecord->description,
            'transaction_date' => $this->debtRecord->transaction_date->format('Y-m-d'),
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => "{$this->creator->name} membuat {$this->debtRecord->type->label()}",
            'message' => "Anda menerima {$this->debtRecord->type->label()} sebesar Rp " . number_format($this->debtRecord->amount, 0, ',', '.') . " dari {$this->creator->name}",
            'data' => $this->toArray($notifiable),
        ];
    }
}
