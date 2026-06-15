# CheckDueDateCommand Documentation

## 📋 Overview

`CheckDueDateCommand` adalah Artisan command untuk otomatis memeriksa jatuh tempo debt records dan mengirim reminder notifications kepada creator dan counterpart.

**Command Name:** `debt:check-due-dates`

**Schedule:** Daily at 08:00 (Setiap hari pukul 08:00)

**Scope:** Active debt records only

---

## 🎯 Purpose

Sistem ini memastikan bahwa setiap mahasiswa dan akademik yang memiliki debt record dengan status **ACTIVE** akan menerima notifikasi reminder pada waktu yang tepat:

- **H-3 (3 hari sebelum jatuh tempo)** - Early reminder
- **H-1 (1 hari sebelum jatuh tempo)** - Final reminder

Hal ini membantu memastikan semua pihak tidak lupa dengan kewajiban mereka.

---

## 🏗️ Architecture

```
Scheduler (0 8 * * *)
    ↓
CheckDueDateCommand::handle()
    ↓
Query: Active debts with future due dates
    ↓
Loop each debt:
  ├─ Check if H-3 reminder needed
  │  └─ shouldSendReminder(debt, 3)
  │     ├─ Calculate: due_date - 3 days
  │     ├─ Check if today matches reminder date
  │     └─ Check if not already sent (via reminder_logs)
  │
  ├─ Check if H-1 reminder needed
  │  └─ shouldSendReminder(debt, 1)
  │     ├─ Calculate: due_date - 1 day
  │     ├─ Check if today matches reminder date
  │     └─ Check if not already sent (via reminder_logs)
  │
  └─ If needed:
     └─ sendReminder(debt, daysBefore, queue)
        ├─ Dispatch to creator
        ├─ Dispatch to counterpart
        └─ Log in reminder_logs (prevent duplicates)
    ↓
Database: Insert into notifications table (via Jobs)
    ↓
User: Retrieve via API
```

---

## 📝 Implementation Details

### File Location
```
app/Console/Commands/CheckDueDateCommand.php
```

### Command Signature
```php
protected $signature = 'debt:check-due-dates {--queue=notifications}';
```

### Command Description
```
Check debt due dates and send reminders for H-3 and H-1 (active debts only)
```

### Options
```
--queue=notifications    Queue name for jobs (default: notifications)
```

---

## 🔄 Execution Flow

### Step 1: Get Active Debts
```php
$activeDebts = DebtRecord::where('status', DebtStatus::ACTIVE)
    ->where('due_date', '>', $today)
    ->with('creator', 'counterpart')
    ->orderBy('due_date', 'asc')
    ->get();
```

**Filters:**
- Status must be `ACTIVE` (not pending, rejected, or settled)
- Due date must be in the future
- Includes relationships for notification dispatch
- Ordered by due date (earliest first)

### Step 2: Check Each Debt

For each debt, check if reminders are needed:

**H-3 Check:**
```php
// Calculate: due_date - 3 days
$reminderDate = $debt->due_date->subDays(3)->startOfDay();

// Only send if:
// 1. Today == reminder date
// 2. Reminder not already sent (via reminder_logs)
```

**H-1 Check:**
```php
// Calculate: due_date - 1 day
$reminderDate = $debt->due_date->subDays(1)->startOfDay();

// Only send if:
// 1. Today == reminder date
// 2. Reminder not already sent (via reminder_logs)
```

### Step 3: Send Reminders

If reminder should be sent:

```php
// Dispatch to creator
DebtRecordDueReminder::dispatch($debt, $debt->creator, $daysBefore)
    ->onQueue($queue);

// Dispatch to counterpart
DebtRecordDueReminder::dispatch($debt, $debt->counterpart, $daysBefore)
    ->onQueue($queue);

// Log to prevent duplicates
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
```

### Step 4: Event Dispatch

Event `DebtRecordDueReminder` is dispatched with:

```php
DebtRecordDueReminder::dispatch(
    debtRecord: DebtRecord,
    notifyUser: User,
    daysBefore: int (3 or 1)
)
```

### Step 5: Job Execution

The event is handled by listener which dispatches job:

```php
SendDueReminderNotificationJob::dispatch(...)
    ->onQueue('notifications');
```

### Step 6: Notification Storage

Job sends notification which is stored in `notifications` table:

```
INSERT INTO notifications (
    user_id,
    notification_type_id,
    debt_record_id,
    title,
    message,
    data,
    created_at
) VALUES (...)
```

---

## 🕐 Scheduler Configuration

### Bootstrap Configuration
**File:** `bootstrap/app.php`

```php
->withSchedule(function ($schedule): void {
    // Check due dates and send reminders daily at 08:00
    // H-3 (3 days before) and H-1 (1 day before) reminders
    // Status: active only
    $schedule->command('debt:check-due-dates')->dailyAt('08:00');
})->create();
```

### Cron Expression
```
0 8 * * *
```

**Meaning:**
- Minute: 0
- Hour: 8 (08:00)
- Day of month: * (every day)
- Month: * (every month)
- Day of week: * (every day)

### Schedule List
```bash
php artisan schedule:list

# Output:
0 8 * * *  php artisan debt:check-due-dates ..... Next Due: 2 hours from now
```

---

## ⚙️ Console Kernel

### File Location
```
app/Console/Kernel.php
```

### Purpose
- Loads all console commands from `app/Console/Commands`
- Registers command schedule (if using traditional Kernel approach)
- Loads routes from `routes/console.php`

---

## 🧪 Testing

### Manual Execution
```bash
# Run command manually
php artisan debt:check-due-dates

# Output example:
Checking debt due dates and sending reminders...
═══════════════════════════════════════════════════════
Found 5 active debts to check.

  ✓ Debt #1 (H-3 (3 hari)): Rp 50.000 - Due: 18-06-2026
  ✓ Debt #2 (H-1 (1 hari)): Rp 75.000 - Due: 16-06-2026
═══════════════════════════════════════════════════════
✓ Sent 2 debt reminders successfully.
```

### With Custom Queue
```bash
# Use different queue
php artisan debt:check-due-dates --queue=reminders
```

### Test During Development
```php
// In test or tinker
php artisan tinker

// Check schedule
>>> $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
>>> $schedule->dueEvents(now());

// Or check via command
>>> Artisan::call('schedule:list');
```

---

## 📊 Data Flow

```
Daily 08:00
    ↓
CheckDueDateCommand starts
    ↓
Query: SELECT * FROM debt_records 
       WHERE status = 'active' AND due_date > NOW()
    ↓
For each debt:
    ├─ Check if today == (due_date - 3 days)
    │  └─ Check reminder_logs table
    │     └─ If not exists: send H-3 reminder
    │
    └─ Check if today == (due_date - 1 day)
       └─ Check reminder_logs table
          └─ If not exists: send H-1 reminder
    ↓
Event: DebtRecordDueReminder
    ↓
Listener: SendDueReminderNotification
    ↓
Job: SendDueReminderNotificationJob (queued)
    ↓
Notification: DueReminderNotification (stored in DB)
    ↓
User receives via API: GET /api/v1/notifications
```

---

## 🔒 Safety Features

### 1. Duplicate Prevention
```php
// Check reminder_logs to prevent sending same reminder twice
$reminderSent = ReminderLog::where('debt_record_id', $debt->id)
    ->where('days_before', $daysBefore)
    ->exists();

return !$reminderSent;
```

### 2. Active Status Only
```php
// Only process active debts
->where('status', DebtStatus::ACTIVE)
```

### 3. Future Due Dates Only
```php
// Only process debts not yet due
->where('due_date', '>', $today)
```

### 4. Error Handling
```php
try {
    // Process reminders
} catch (\Exception $e) {
    $this->error('✗ Error sending reminders: ' . $e->getMessage());
    return Command::FAILURE;
}
```

### 5. Logging
```php
// Log each reminder sent
ReminderLog::create([
    'debt_record_id' => $debt->id,
    'user_id' => $debt->creator_id,
    'days_before' => $daysBefore,
    'sent_at' => now(),
]);
```

---

## 📈 Example Scenario

**Scenario:** Debt record with due date June 18, 2026

```
June 15, 2026 (08:00)
  ├─ Due date: June 18
  ├─ Days until due: 3 days
  └─ Check: 18 - 3 = 15 → Match! Send H-3 reminder
     ├─ Event: DebtRecordDueReminder (daysBefore=3)
     ├─ To: Creator + Counterpart
     └─ Log: reminder_logs (prevent duplicate)

June 17, 2026 (08:00)
  ├─ Due date: June 18
  ├─ Days until due: 1 day
  └─ Check: 18 - 1 = 17 → Match! Send H-1 reminder
     ├─ Event: DebtRecordDueReminder (daysBefore=1)
     ├─ To: Creator + Counterpart
     └─ Log: reminder_logs (prevent duplicate)

June 18, 2026 (08:00)
  ├─ Due date: June 18
  ├─ Days until due: 0 days (TODAY!)
  └─ Check: 18 - 0 = 18 → Match! But no H-0 reminder configured
     └─ Optional: Could add H-0 reminder if needed
```

---

## 🚀 Production Deployment

### Cron Job Setup (Linux/Mac)
```bash
# Edit crontab
crontab -e

# Add entry (run scheduler every minute)
* * * * * cd /path/to/simpati && php artisan schedule:run >> /dev/null 2>&1
```

### Systemd Service (Linux)
```ini
# /etc/systemd/system/simpati-scheduler.service
[Unit]
Description=SIMPATI Schedule Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/simpati
ExecStart=/usr/bin/php artisan schedule:work
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

### Docker (Container)
```dockerfile
# Dockerfile
CMD php artisan schedule:work
```

### Supervisor (Process Management)
```ini
# /etc/supervisor/conf.d/simpati-scheduler.conf
[program:simpati-scheduler]
process_name=%(program_name)s
command=php /path/to/simpati/artisan schedule:work
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/simpati/storage/logs/scheduler.log
```

---

## 📋 Monitoring

### Check Scheduled Tasks
```bash
php artisan schedule:list
```

### Monitor Scheduler Logs
```bash
tail -f storage/logs/scheduler.log
```

### Queue Status
```bash
php artisan queue:failed
php artisan queue:work --queue=notifications
```

### Reminder Logs
```sql
SELECT * FROM reminder_logs 
ORDER BY sent_at DESC 
LIMIT 10;
```

---

## 🐛 Troubleshooting

### Problem: Command not running at scheduled time

**Solution 1: Check cron job**
```bash
crontab -l  # List cron jobs
crontab -e  # Edit cron jobs
```

**Solution 2: Check schedule:run permission**
```bash
# Ensure artisan can execute
ls -la artisan
chmod +x artisan
```

**Solution 3: Test command manually**
```bash
php artisan debt:check-due-dates
```

### Problem: Reminders sending multiple times

**Check reminder_logs table:**
```sql
SELECT * FROM reminder_logs 
WHERE debt_record_id = ? AND days_before = ?;
```

**Solution: Clean duplicate reminders**
```sql
DELETE FROM reminder_logs 
WHERE debt_record_id = ? AND days_before = ? 
LIMIT -1 OFFSET 1;
```

### Problem: No reminders being sent

**Check:**
1. Are there active debts in the database?
   ```sql
   SELECT * FROM debt_records WHERE status = 'active';
   ```

2. Are due dates correct?
   ```sql
   SELECT id, due_date, DATEDIFF(due_date, CURDATE()) as days_until_due 
   FROM debt_records WHERE status = 'active';
   ```

3. Are queue jobs being processed?
   ```bash
   php artisan queue:work --queue=notifications
   ```

4. Check logs for errors
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 📚 Related Commands

```bash
# List all commands
php artisan list

# List scheduled tasks
php artisan schedule:list

# Run scheduler (daemon mode)
php artisan schedule:work

# Process queue jobs
php artisan queue:work --queue=notifications

# Test command with different queue
php artisan debt:check-due-dates --queue=test

# Run in tinker (interactive shell)
php artisan tinker
```

---

## 🔗 Related Files

- **Command:** `app/Console/Commands/CheckDueDateCommand.php`
- **Kernel:** `app/Console/Kernel.php`
- **Configuration:** `bootstrap/app.php`
- **Event:** `app/Events/DebtRecordDueReminder.php`
- **Listener:** `app/Listeners/SendDueReminderNotification.php`
- **Job:** `app/Jobs/SendDueReminderNotificationJob.php`
- **Notification:** `app/Notifications/DueReminderNotification.php`
- **Table:** `reminder_logs` (logs each reminder sent)

---

## ✅ Checklist

- ✅ CheckDueDateCommand created
- ✅ Scheduled to run daily at 08:00
- ✅ Handles H-3 reminders
- ✅ Handles H-1 reminders
- ✅ Active status only
- ✅ Duplicate prevention via reminder_logs
- ✅ Event dispatch for async notification
- ✅ Queue integration for background processing
- ✅ Error handling and logging
- ✅ Console Kernel created
- ✅ Scheduler registered in bootstrap/app.php
- ✅ All syntax validated
- ✅ Application optimized and cached

---

## 🎯 Summary

**CheckDueDateCommand** adalah automation tool yang:

1. ✅ Berjalan otomatis setiap hari pukul 08:00
2. ✅ Memeriksa semua active debt records
3. ✅ Mengirim H-3 reminder (3 hari sebelum jatuh tempo)
4. ✅ Mengirim H-1 reminder (1 hari sebelum jatuh tempo)
5. ✅ Prevents duplicate reminders
6. ✅ Uses queue untuk async notification
7. ✅ Logs all reminders untuk audit trail
8. ✅ Includes comprehensive error handling

**Production Ready!** 🚀

