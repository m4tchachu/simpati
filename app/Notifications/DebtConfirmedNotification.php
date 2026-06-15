<?php

namespace App\Notifications;

use App\Models\DebtRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DebtConfirmedNotification extends Notification implements ShouldQueue
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
            'counterpart_id' => $this->counterpart->id,
            'counterpart_name' => $this->counterpart->name,
            'amount' => $this->debtRecord->amount,
            'type' => $this->debtRecord->type->label(),
            'confirmed_at' => $this->debtRecord->confirmed_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => "{$this->counterpart->name} mengkonfirmasi {$this->debtRecord->type->label()}",
            'message' => "{$this->counterpart->name} telah mengkonfirmasi {$this->debtRecord->type->label()} Anda sebesar Rp " . number_format($this->debtRecord->amount, 0, ',', '.'),
            'data' => $this->toArray($notifiable),
        ];
    }
}
