<?php

namespace App\Jobs;

use App\Models\DebtRecord;
use App\Models\User;
use App\Notifications\DueReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDueReminderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private DebtRecord $debtRecord,
        private User $notifyUser,
        private int $daysBefore,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(\App\Services\NotificationService::class)->sendDueReminder($this->debtRecord, $this->notifyUser, $this->daysBefore);
    }
}
