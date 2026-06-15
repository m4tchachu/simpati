<?php

namespace App\Listeners;

use App\Events\DebtRecordDueReminder;
use App\Jobs\SendDueReminderNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDueReminderNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     */
    public function handle(DebtRecordDueReminder $event): void
    {
        // Dispatch job to queue to send reminder notification
        SendDueReminderNotificationJob::dispatch(
            $event->debtRecord,
            $event->notifyUser,
            $event->daysBefore,
        )->onQueue('notifications');
    }
}
