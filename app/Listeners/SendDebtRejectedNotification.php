<?php

namespace App\Listeners;

use App\Events\DebtRecordRejected;
use App\Jobs\SendDebtRejectedNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDebtRejectedNotification implements ShouldQueue
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
    public function handle(DebtRecordRejected $event): void
    {
        // Dispatch job to queue to send notification to creator
        SendDebtRejectedNotificationJob::dispatch(
            $event->debtRecord,
            $event->creator,
            $event->counterpart,
            $event->reason,
        )->onQueue('notifications');
    }
}
