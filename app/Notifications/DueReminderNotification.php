<?php

namespace App\Notifications;

use App\Models\DebtRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private DebtRecord $debtRecord,
        private User $notifyUser,
        private int $daysBefore,
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
            'amount' => $this->debtRecord->amount,
            'type' => $this->debtRecord->type->label(),
            'due_date' => $this->debtRecord->due_date->format('Y-m-d'),
            'days_before' => $this->daysBefore,
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $daysText = $this->daysBefore == 3 ? '3 hari' : '1 hari';
        
        return [
            'title' => "Pengingat: {$daysText} sebelum jatuh tempo",
            'message' => "{$daysText} lagi {$this->debtRecord->type->label()} Anda sebesar Rp " . number_format($this->debtRecord->amount, 0, ',', '.') . " akan jatuh tempo pada " . $this->debtRecord->due_date->format('d-m-Y'),
            'data' => $this->toArray($notifiable),
        ];
    }
}
