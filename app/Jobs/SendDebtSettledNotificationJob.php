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
        // Send notification to both creator and counterpart
        $this->creator->notify(
            new DebtSettledNotification(
                $this->debtRecord,
                $this->creator,
                $this->counterpart,
            )
        );

        $this->counterpart->notify(
            new DebtSettledNotification(
                $this->debtRecord,
                $this->creator,
                $this->counterpart,
            )
        );
    }
}
