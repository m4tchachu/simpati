# Event, Listener, Notification & Job System Documentation

## 📋 Overview

SIMPATI menggunakan Laravel Event-Listener pattern dengan Queue untuk async notification system yang robust. Ketika transaksi debt terjadi, event di-dispatch dan listeners menangani pengiriman notifikasi melalui background jobs.

---

## 🏗️ Architecture

```
User Action
    ↓
DebtRecordService (dispatch event)
    ↓
Event (DebtRecordCreated, etc)
    ↓
Listener (implements ShouldQueue)
    ↓
Job (Queueable)
    ↓
Notification (format & content)
    ↓
Database (notifications table)
```

---

## 📌 Events (5 Total)

### 1. DebtRecordCreated
**File:** `app/Events/DebtRecordCreated.php`

Triggered ketika debt record baru dibuat.

```php
DebtRecordCreated::dispatch(
    debtRecord: DebtRecord,
    creator: User,
    counterpart: User
)
```

**Dispatch Point:** `DebtRecordService::createDebtRecord()`

---

### 2. DebtRecordConfirmed
**File:** `app/Events/DebtRecordConfirmed.php`

Triggered ketika debt record dikonfirmasi (pending → active).

```php
DebtRecordConfirmed::dispatch(
    debtRecord: DebtRecord,
    creator: User,
    counterpart: User
)
```

**Dispatch Point:** `DebtRecordService::confirmDebtRecord()`

---

### 3. DebtRecordRejected
**File:** `app/Events/DebtRecordRejected.php`

Triggered ketika debt record ditolak (pending → rejected).

```php
DebtRecordRejected::dispatch(
    debtRecord: DebtRecord,
    creator: User,
    counterpart: User,
    reason: string
)
```

**Dispatch Point:** `DebtRecordService::rejectDebtRecord()`

---

### 4. DebtRecordSettled
**File:** `app/Events/DebtRecordSettled.php`

Triggered ketika debt record diselesaikan (active → settled).

```php
DebtRecordSettled::dispatch(
    debtRecord: DebtRecord,
    creator: User,
    counterpart: User
)
```

**Dispatch Point:** `DebtRecordService::settleDebtRecord()`

---

### 5. DebtRecordDueReminder
**File:** `app/Events/DebtRecordDueReminder.php`

Triggered oleh scheduled command untuk send reminder notifikasi.

```php
DebtRecordDueReminder::dispatch(
    debtRecord: DebtRecord,
    notifyUser: User,
    daysBefore: int (3 or 1)
)
```

**Dispatch Point:** `App\Console\Commands\SendDebtReminders` (scheduled command)

---

## 🎧 Listeners (5 Total)

### 1. SendDebtCreatedNotification
**File:** `app/Listeners/SendDebtCreatedNotification.php`

Mendengarkan `DebtRecordCreated` event dan dispatch job untuk send notifikasi.

```php
implements ShouldQueue
- Listens to: DebtRecordCreated
- Dispatches: SendDebtCreatedNotificationJob
- Queue: notifications
```

**Notification Recipient:** Counterpart (penerima hutang/piutang)

---

### 2. SendDebtConfirmedNotification
**File:** `app/Listeners/SendDebtConfirmedNotification.php`

Mendengarkan `DebtRecordConfirmed` event.

```php
implements ShouldQueue
- Listens to: DebtRecordConfirmed
- Dispatches: SendDebtConfirmedNotificationJob
- Queue: notifications
```

**Notification Recipient:** Creator (pembuat transaksi)

---

### 3. SendDebtRejectedNotification
**File:** `app/Listeners/SendDebtRejectedNotification.php`

Mendengarkan `DebtRecordRejected` event.

```php
implements ShouldQueue
- Listens to: DebtRecordRejected
- Dispatches: SendDebtRejectedNotificationJob
- Queue: notifications
```

**Notification Recipient:** Creator (pembuat transaksi)

---

### 4. SendDebtSettledNotification
**File:** `app/Listeners/SendDebtSettledNotification.php`

Mendengarkan `DebtRecordSettled` event.

```php
implements ShouldQueue
- Listens to: DebtRecordSettled
- Dispatches: SendDebtSettledNotificationJob
- Queue: notifications
```

**Notification Recipients:** Creator & Counterpart (both parties)

---

### 5. SendDueReminderNotification
**File:** `app/Listeners/SendDueReminderNotification.php`

Mendengarkan `DebtRecordDueReminder` event.

```php
implements ShouldQueue
- Listens to: DebtRecordDueReminder
- Dispatches: SendDueReminderNotificationJob
- Queue: notifications
```

**Notification Recipient:** Specified user (creator or counterpart)

---

## 📧 Notifications (5 Total)

### 1. DebtCreatedNotification
**File:** `app/Notifications/DebtCreatedNotification.php`

Content untuk notifikasi debt baru.

**Channels:** database

**Content:**
```
Title: "{creator_name} membuat {type}"
Message: "Anda menerima {type} sebesar Rp {amount} dari {creator_name}"
```

**Data:**
```php
[
    'debt_record_id' => int,
    'creator_id' => int,
    'creator_name' => string,
    'amount' => float,
    'type' => string (label),
    'description' => string,
    'transaction_date' => string (Y-m-d),
]
```

---

### 2. DebtConfirmedNotification
**File:** `app/Notifications/DebtConfirmedNotification.php`

Content untuk notifikasi debt confirmed.

**Channels:** database

**Content:**
```
Title: "{counterpart_name} mengkonfirmasi {type}"
Message: "{counterpart_name} telah mengkonfirmasi {type} Anda sebesar Rp {amount}"
```

**Data:**
```php
[
    'debt_record_id' => int,
    'counterpart_id' => int,
    'counterpart_name' => string,
    'amount' => float,
    'type' => string (label),
    'confirmed_at' => string (Y-m-d H:i),
]
```

---

### 3. DebtRejectedNotification
**File:** `app/Notifications/DebtRejectedNotification.php`

Content untuk notifikasi debt rejected.

**Channels:** database

**Content:**
```
Title: "{counterpart_name} menolak {type}"
Message: "{counterpart_name} telah menolak {type} Anda sebesar Rp {amount}. Alasan: {reason}"
```

**Data:**
```php
[
    'debt_record_id' => int,
    'counterpart_id' => int,
    'counterpart_name' => string,
    'amount' => float,
    'type' => string (label),
    'reason' => string,
    'rejected_at' => string (Y-m-d H:i),
]
```

---

### 4. DebtSettledNotification
**File:** `app/Notifications/DebtSettledNotification.php`

Content untuk notifikasi debt settled.

**Channels:** database

**Content:**
```
Title: "{type} sebesar Rp {amount} telah dilunasi"
Message: "{type} Anda dengan {counterpart_name} sebesar Rp {amount} telah dilunasi"
```

**Data:**
```php
[
    'debt_record_id' => int,
    'counterpart_name' => string,
    'amount' => float,
    'type' => string (label),
    'settled_at' => string (Y-m-d H:i),
]
```

---

### 5. DueReminderNotification
**File:** `app/Notifications/DueReminderNotification.php`

Content untuk notifikasi reminder jatuh tempo.

**Channels:** database

**Content:**
```
Title: "Pengingat: {days_text} sebelum jatuh tempo"
Message: "{days_text} lagi {type} Anda sebesar Rp {amount} akan jatuh tempo pada {due_date}"
```

**Data:**
```php
[
    'debt_record_id' => int,
    'amount' => float,
    'type' => string (label),
    'due_date' => string (Y-m-d),
    'days_before' => int (3 or 1),
]
```

---

## ⚙️ Jobs (5 Total)

### 1. SendDebtCreatedNotificationJob
**File:** `app/Jobs/SendDebtCreatedNotificationJob.php`

Mengirim notifikasi debt created ke counterpart.

```php
class SendDebtCreatedNotificationJob implements ShouldQueue
- Parameters: DebtRecord, User $creator, User $counterpart
- Handle: Notify counterpart with DebtCreatedNotification
- Queue: notifications
```

---

### 2. SendDebtConfirmedNotificationJob
**File:** `app/Jobs/SendDebtConfirmedNotificationJob.php`

Mengirim notifikasi debt confirmed ke creator.

```php
class SendDebtConfirmedNotificationJob implements ShouldQueue
- Parameters: DebtRecord, User $creator, User $counterpart
- Handle: Notify creator with DebtConfirmedNotification
- Queue: notifications
```

---

### 3. SendDebtRejectedNotificationJob
**File:** `app/Jobs/SendDebtRejectedNotificationJob.php`

Mengirim notifikasi debt rejected ke creator.

```php
class SendDebtRejectedNotificationJob implements ShouldQueue
- Parameters: DebtRecord, User $creator, User $counterpart, string $reason
- Handle: Notify creator with DebtRejectedNotification
- Queue: notifications
```

---

### 4. SendDebtSettledNotificationJob
**File:** `app/Jobs/SendDebtSettledNotificationJob.php`

Mengirim notifikasi debt settled ke creator & counterpart.

```php
class SendDebtSettledNotificationJob implements ShouldQueue
- Parameters: DebtRecord, User $creator, User $counterpart
- Handle: Notify both creator and counterpart with DebtSettledNotification
- Queue: notifications
```

---

### 5. SendDueReminderNotificationJob
**File:** `app/Jobs/SendDueReminderNotificationJob.php`

Mengirim notifikasi reminder jatuh tempo.

```php
class SendDueReminderNotificationJob implements ShouldQueue
- Parameters: DebtRecord, User $notifyUser, int $daysBefore
- Handle: Notify specified user with DueReminderNotification
- Queue: notifications
```

---

## 🔗 Event Service Provider

**File:** `app/Providers/EventServiceProvider.php`

Mengatur mapping antara events dan listeners.

```php
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
```

---

## 📅 Scheduled Command

### SendDebtReminders
**File:** `app/Console/Commands/SendDebtReminders.php`

Command untuk send reminder notifikasi H-3 dan H-1 sebelum jatuh tempo.

```bash
# Manual execution
php artisan debt:send-reminders

# Register in scheduler (app/Console/Kernel.php)
$schedule->command('debt:send-reminders')->daily();
```

**Functionality:**
1. Get semua debt records dengan status active dan due_date di masa depan
2. Check jika hari ini adalah H-3 atau H-1
3. Dispatch DebtRecordDueReminder event untuk creator dan counterpart
4. Log reminder di reminder_logs table untuk prevent duplicate

**Output:**
```
Sending debt reminders...
Reminder sent for debt #1 (3 days before)
Reminder sent for debt #2 (1 day before)
Sent 2 debt reminders.
```

---

## ⚙️ Queue Configuration

### .env Settings
```
QUEUE_CONNECTION=database
```

### Database Setup
Jobs disimpan di `jobs` table (auto-created migration: `0001_01_01_000002_create_jobs_table`)

### Queue Processing

**Development (Manual):**
```bash
# Process jobs synchronously (for testing)
php artisan queue:work

# Process specific queue
php artisan queue:work --queue=notifications
```

**Production:**
```bash
# Run as daemon
php artisan queue:work --queue=notifications --timeout=30

# With supervisor/systemd for auto-restart
```

---

## 🔄 Workflow Example

### Creating Debt Record

1. **Controller receives request**
   ```php
   POST /api/v1/debts
   {
       "counterpart_id": 2,
       "type": "debt",
       "amount": 50000,
       ...
   }
   ```

2. **Service creates debt & dispatches event**
   ```php
   // DebtRecordService::createDebtRecord()
   $debtRecord = DebtRecord::create([...]);
   DebtRecordCreated::dispatch($debtRecord, $creator, $counterpart);
   ```

3. **Listener receives event (async)**
   ```php
   // SendDebtCreatedNotification::handle()
   SendDebtCreatedNotificationJob::dispatch(...)->onQueue('notifications');
   ```

4. **Job sends notification (queued)**
   ```php
   // SendDebtCreatedNotificationJob::handle()
   $counterpart->notify(new DebtCreatedNotification(...));
   ```

5. **Notification stored in DB**
   ```
   INSERT INTO notifications (user_id, notification_type_id, title, message, data, created_at)
   VALUES (2, 1, "...", "...", {...}, now())
   ```

6. **User receives notification**
   - Via API: `GET /api/v1/notifications`
   - Via real-time: WebSocket/Polling

---

## 🧪 Testing

### Test Event Dispatch
```php
// tests/Feature/DebtRecordTest.php
use Illuminate\Support\Facades\Event;

Event::fake();

$response = $this->post('/api/v1/debts', [...]);

Event::assertDispatched(DebtRecordCreated::class, function ($event) {
    return $event->debtRecord->id === $debtRecord->id;
});
```

### Test Queue Jobs
```php
use Illuminate\Support\Facades\Queue;

Queue::fake();

DebtRecordCreated::dispatch($debtRecord, $creator, $counterpart);

Queue::assertPushed(SendDebtCreatedNotificationJob::class, function ($job) {
    return $job->debtRecord->id === $debtRecord->id;
});
```

### Test Notifications
```php
$user->notify(new DebtCreatedNotification($debtRecord, $creator, $counterpart));

$this->assertDatabaseHas('notifications', [
    'user_id' => $user->id,
    'notification_type_id' => 1, // debt_created
]);
```

---

## 📊 Data Flow Diagram

```
Debt Record Status Change Event
├─ DebtRecordCreated (pending → pending)
│  └─ SendDebtCreatedNotification (async)
│     └─ SendDebtCreatedNotificationJob
│        └─ DebtCreatedNotification → notifications table
│
├─ DebtRecordConfirmed (pending → active)
│  └─ SendDebtConfirmedNotification (async)
│     └─ SendDebtConfirmedNotificationJob
│        └─ DebtConfirmedNotification → notifications table
│
├─ DebtRecordRejected (pending → rejected)
│  └─ SendDebtRejectedNotification (async)
│     └─ SendDebtRejectedNotificationJob
│        └─ DebtRejectedNotification → notifications table
│
├─ DebtRecordSettled (active → settled)
│  └─ SendDebtSettledNotification (async)
│     └─ SendDebtSettledNotificationJob
│        └─ DebtSettledNotification → notifications table
│
└─ DebtRecordDueReminder (scheduled daily)
   └─ SendDueReminderNotification (async)
      └─ SendDueReminderNotificationJob
         └─ DueReminderNotification → notifications table
```

---

## 🔧 Configuration

### Register Scheduler (app/Console/Kernel.php)
```php
protected function schedule(Schedule $schedule)
{
    // Send debt reminders daily at 08:00
    $schedule->command('debt:send-reminders')->daily();
}
```

### Update Services (DebtRecordService)
```php
// All debt status change methods dispatch events:
- createDebtRecord() → DebtRecordCreated
- confirmDebtRecord() → DebtRecordConfirmed
- rejectDebtRecord() → DebtRecordRejected
- settleDebtRecord() → DebtRecordSettled
```

---

## ✅ Checklist Implementation

- ✅ 5 Event classes created and registered
- ✅ 5 Listener classes created (implements ShouldQueue)
- ✅ 5 Notification classes created with database channel
- ✅ 5 Job classes created (implements ShouldQueue)
- ✅ EventServiceProvider configured with event-listener mapping
- ✅ DebtRecordService updated to dispatch events
- ✅ Queue configuration set to database
- ✅ SendDebtReminders Artisan command created
- ✅ All syntax validated (php -l checks)
- ✅ Application optimized and cached

---

## 🚀 Future Enhancements

- [ ] Firebase Cloud Messaging (FCM) channel for push notifications
- [ ] Email notifications via MAIL_MAILER
- [ ] SMS notifications via Twilio/third-party provider
- [ ] Real-time WebSocket notifications
- [ ] Notification preferences per user
- [ ] Notification templates with customization
- [ ] Retry failed jobs with exponential backoff
- [ ] Job timeout and failed job handling
- [ ] Event sourcing for audit trail
- [ ] GraphQL subscription for real-time updates

