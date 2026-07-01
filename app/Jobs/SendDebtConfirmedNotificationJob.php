<?php

namespace App\Jobs;

use App\Models\DebtRecord;
use App\Models\User;
use App\Notifications\DebtConfirmedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDebtConfirmedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private DebtRecord $debtRecord,
        private User $creator,
        private User $counterpart,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(\App\Services\NotificationService::class)->notifyDebtConfirmed($this->debtRecord);
    }
}
