<?php

namespace App\Events;

use App\Models\DebtRecord;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DebtRecordConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public DebtRecord $debtRecord,
        public User $creator,
        public User $counterpart,
    ) {
    }
}
