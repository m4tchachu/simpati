<?php

namespace App\Listeners;

use App\Events\DebtRecordSettled;
use App\Jobs\SendDebtSettledNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDebtSettledNotification implements ShouldQueue
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
    public function handle(DebtRecordSettled $event): void
    {
        // Dispatch job to queue to send notification to both parties
        SendDebtSettledNotificationJob::dispatch(
            $event->debtRecord,
            $event->creator,
            $event->counterpart,
        )->onQueue('notifications');
    }
}
