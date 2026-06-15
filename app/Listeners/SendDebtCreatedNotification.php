<?php

namespace App\Listeners;

use App\Events\DebtRecordCreated;
use App\Jobs\SendDebtCreatedNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDebtCreatedNotification implements ShouldQueue
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
    public function handle(DebtRecordCreated $event): void
    {
        // Dispatch job to queue to send notification to counterpart
        SendDebtCreatedNotificationJob::dispatch(
            $event->debtRecord,
            $event->creator,
            $event->counterpart,
        )->onQueue('notifications');
    }
}
