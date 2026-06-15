<?php

namespace App\Console\Commands;

use App\Enums\DebtStatus;
use App\Events\DebtRecordDueReminder;
use App\Models\DebtRecord;
use App\Models\ReminderLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDueDateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debt:check-due-dates {--queue=notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check debt due dates and send reminders for H-3 and H-1 (active debts only)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking debt due dates and sending reminders...');
        $this->info('═══════════════════════════════════════════════════════');

        $queue = $this->option('queue') ?? 'notifications';
        $remindersCount = 0;
        $today = now()->startOfDay();

        try {
            // Get all active debt records with future due dates
            $activeDebts = DebtRecord::where('status', DebtStatus::ACTIVE)
                ->where('due_date', '>', $today)
                ->with('creator', 'counterpart')
                ->orderBy('due_date', 'asc')
                ->get();

            $this->info("Found {$activeDebts->count()} active debts to check.");
            $this->newLine();

            foreach ($activeDebts as $debt) {
                // Check for H-3 (3 days before due date)
                if ($this->shouldSendReminder($debt, 3, $today)) {
                    $this->sendReminder($debt, 3, $queue);
                    $remindersCount++;
                }

                // Check for H-1 (1 day before due date)
                if ($this->shouldSendReminder($debt, 1, $today)) {
                    $this->sendReminder($debt, 1, $queue);
                    $remindersCount++;
                }
            }

            $this->info('═══════════════════════════════════════════════════════');
            $this->info("✓ Sent {$remindersCount} debt reminders successfully.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ Error sending reminders: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Determine if reminder should be sent for this debt and days before
     */
    private function shouldSendReminder(DebtRecord $debt, int $daysBefore, Carbon $today): bool
    {
        // Calculate the reminder date (due_date - daysBefore)
        $reminderDate = $debt->due_date->clone()->subDays($daysBefore)->startOfDay();

        // Check if today matches the reminder date
        if (!$today->equalTo($reminderDate)) {
            return false;
        }

        // Check if reminder has already been sent
        $reminderSent = ReminderLog::where('debt_record_id', $debt->id)
            ->where('days_before', $daysBefore)
            ->exists();

        return !$reminderSent;
    }

    /**
     * Send reminder notification to both creator and counterpart
     */
    private function sendReminder(DebtRecord $debt, int $daysBefore, string $queue): void
    {
        $daysText = $daysBefore === 3 ? 'H-3 (3 hari)' : 'H-1 (1 hari)';

        try {
            // Send to creator
            DebtRecordDueReminder::dispatch($debt, $debt->creator, $daysBefore)
                ->onQueue($queue);

            // Send to counterpart
            DebtRecordDueReminder::dispatch($debt, $debt->counterpart, $daysBefore)
                ->onQueue($queue);

            // Log reminders to prevent duplicates
            ReminderLog::create([
                'debt_record_id' => $debt->id,
                'user_id' => $debt->creator_id,
                'days_before' => $daysBefore,
                'sent_at' => now(),
            ]);

            ReminderLog::create([
                'debt_record_id' => $debt->id,
                'user_id' => $debt->counterpart_id,
                'days_before' => $daysBefore,
                'sent_at' => now(),
            ]);

            $this->line(
                "  ✓ Debt #{$debt->id} ({$daysText}): Rp " .
                number_format($debt->amount, 0, ',', '.') .
                " - Due: {$debt->due_date->format('d-m-Y')}"
            );
        } catch (\Exception $e) {
            $this->error(
                "  ✗ Debt #{$debt->id} ({$daysText}): Failed - {$e->getMessage()}"
            );
        }
    }
}
