<?php

namespace App\Events;

use App\Models\DebtRecord;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DebtRecordDueReminder
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     * @param int $daysBefore Days before due date (3 or 1)
     */
    public function __construct(
        public DebtRecord $debtRecord,
        public User $notifyUser,
        public int $daysBefore,
    ) {
    }
}
