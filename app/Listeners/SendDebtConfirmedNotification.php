<?php

namespace App\Listeners;

use App\Events\DebtRecordConfirmed;
use App\Jobs\SendDebtConfirmedNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDebtConfirmedNotification implements ShouldQueue
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
    public function handle(DebtRecordConfirmed $event): void
    {
        // Dispatch job to queue to send notification to creator
        SendDebtConfirmedNotificationJob::dispatch(
            $event->debtRecord,
            $event->creator,
            $event->counterpart,
        )->onQueue('notifications');
    }
}
