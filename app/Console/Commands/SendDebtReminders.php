<?php

namespace App\Console\Commands;

use App\Events\DebtRecordDueReminder;
use App\Models\DebtRecord;
use App\Models\ReminderLog;
use Illuminate\Console\Command;

class SendDebtReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debt:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for debt records due in 3 days or 1 day';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sending debt reminders...');

        // Get all active debt records
        $activeDebts = DebtRecord::where('status', 'active')
            ->where('due_date', '>', now())
            ->with('creator', 'counterpart')
            ->get();

        $remindersCount = 0;

        foreach ($activeDebts as $debt) {
            // Check for H-3 (3 days before)
            if ($this->isReminderNeeded($debt, 3)) {
                $this->sendReminder($debt, 3);
                $remindersCount++;
            }

            // Check for H-1 (1 day before)
            if ($this->isReminderNeeded($debt, 1)) {
                $this->sendReminder($debt, 1);
                $remindersCount++;
            }
        }

        $this->info("Sent {$remindersCount} debt reminders.");

        return Command::SUCCESS;
    }

    /**
     * Check if reminder needs to be sent for this debt
     */
    private function isReminderNeeded(DebtRecord $debt, int $daysBefore): bool
    {
        $reminderDate = $debt->due_date->clone()->subDays($daysBefore);
        $today = now()->startOfDay();

        // Check if today is the reminder date and reminder hasn't been sent yet
        if ($today->equalTo($reminderDate->startOfDay())) {
            return !ReminderLog::where('debt_record_id', $debt->id)
                ->where('days_before', $daysBefore)
                ->exists();
        }

        return false;
    }

    /**
     * Send reminder notification
     */
    private function sendReminder(DebtRecord $debt, int $daysBefore): void
    {
        // Send to creator and counterpart
        $users = [$debt->creator, $debt->counterpart];

        foreach ($users as $user) {
            // Dispatch event
            DebtRecordDueReminder::dispatch($debt, $user, $daysBefore);

            // Log reminder
            ReminderLog::create([
                'debt_record_id' => $debt->id,
                'user_id' => $user->id,
                'days_before' => $daysBefore,
                'sent_at' => now(),
            ]);
        }

        $this->info("Reminder sent for debt #{$debt->id} ({$daysBefore} days before)");
    }
}
