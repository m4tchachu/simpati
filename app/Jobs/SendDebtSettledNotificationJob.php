<?php

namespace App\Jobs;

use App\Models\DebtRecord;
use App\Models\User;
use App\Notifications\DebtSettledNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDebtSettledNotificationJob implements ShouldQueue
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
        // Find the user who did the settlement from status changes
        $statusChange = $this->debtRecord->statusChanges()
            ->where('new_status', \App\Enums\DebtStatus::SETTLED->value)
            ->latest()
            ->first();
        
        $settledByUser = $statusChange ? $statusChange->changedByUser : $this->creator;

        app(\App\Services\NotificationService::class)->notifyDebtSettled($this->debtRecord, $settledByUser);
    }
}
