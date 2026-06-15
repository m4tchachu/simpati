<?php

namespace App\Providers;

use App\Events\DebtRecordConfirmed;
use App\Events\DebtRecordCreated;
use App\Events\DebtRecordDueReminder;
use App\Events\DebtRecordRejected;
use App\Events\DebtRecordSettled;
use App\Listeners\SendDebtConfirmedNotification;
use App\Listeners\SendDebtCreatedNotification;
use App\Listeners\SendDebtRejectedNotification;
use App\Listeners\SendDebtSettledNotification;
use App\Listeners\SendDueReminderNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        DebtRecordCreated::class => [
            SendDebtCreatedNotification::class,
        ],
        DebtRecordConfirmed::class => [
            SendDebtConfirmedNotification::class,
        ],
        DebtRecordRejected::class => [
            SendDebtRejectedNotification::class,
        ],
        DebtRecordSettled::class => [
            SendDebtSettledNotification::class,
        ],
        DebtRecordDueReminder::class => [
            SendDueReminderNotification::class,
        ],
    ];

    /**
     * Enable or disable event discovery.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
