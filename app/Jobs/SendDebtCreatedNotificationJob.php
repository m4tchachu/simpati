<?php

namespace App\Jobs;

use App\Models\DebtRecord;
use App\Models\User;
use App\Notifications\DebtCreatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDebtCreatedNotificationJob implements ShouldQueue
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
        // Send notification to counterpart (penerima hutang/piutang)
        $this->counterpart->notify(
            new DebtCreatedNotification(
                $this->debtRecord,
                $this->creator,
                $this->counterpart,
            )
        );
    }
}
