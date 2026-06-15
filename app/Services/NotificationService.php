<?php

namespace App\Services;

use App\Models\DebtRecord;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\ReminderLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    /**
     * Send notification to user
     *
     * @param User $user
     * @param string $notificationTypeCode
     * @param string $title
     * @param string $message
     * @param DebtRecord|null $debtRecord
     * @param array|null $data
     * @return Notification
     */
    public function sendNotification(
        User $user,
        string $notificationTypeCode,
        string $title,
        string $message,
        ?DebtRecord $debtRecord = null,
        ?array $data = null
    ): Notification {
        $notificationType = NotificationType::where('code', $notificationTypeCode)->firstOrFail();

        $notification = Notification::create([
            'user_id' => $user->id,
            'notification_type_id' => $notificationType->id,
            'debt_record_id' => $debtRecord?->id,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        // Send FCM push notification if user has active FCM tokens
        $this->sendPushNotification($user, $notification);

        return $notification;
    }

    /**
     * Notify debt created
     *
     * @param DebtRecord $debtRecord
     * @return Notification
     */
    public function notifyDebtCreated(DebtRecord $debtRecord): Notification
    {
        return $this->sendNotification(
            user: $debtRecord->counterpart,
            notificationTypeCode: 'debt_created',
            title: 'Transaksi Hutang Baru',
            message: "{$debtRecord->creator->name} telah membuat transaksi hutang sebesar Rp " . number_format($debtRecord->amount, 0, ',', '.'),
            debtRecord: $debtRecord,
            data: [
                'debt_id' => $debtRecord->id,
                'creator_name' => $debtRecord->creator->name,
                'amount' => $debtRecord->amount,
            ]
        );
    }

    /**
     * Notify debt confirmed
     *
     * @param DebtRecord $debtRecord
     * @return Notification
     */
    public function notifyDebtConfirmed(DebtRecord $debtRecord): Notification
    {
        return $this->sendNotification(
            user: $debtRecord->creator,
            notificationTypeCode: 'debt_confirmed',
            title: 'Transaksi Dikonfirmasi',
            message: "{$debtRecord->counterpart->name} telah mengkonfirmasi transaksi hutang Anda sebesar Rp " . number_format($debtRecord->amount, 0, ',', '.'),
            debtRecord: $debtRecord,
            data: [
                'debt_id' => $debtRecord->id,
                'counterpart_name' => $debtRecord->counterpart->name,
                'amount' => $debtRecord->amount,
            ]
        );
    }

    /**
     * Notify debt rejected
     *
     * @param DebtRecord $debtRecord
     * @return Notification
     */
    public function notifyDebtRejected(DebtRecord $debtRecord): Notification
    {
        return $this->sendNotification(
            user: $debtRecord->creator,
            notificationTypeCode: 'debt_rejected',
            title: 'Transaksi Ditolak',
            message: "{$debtRecord->counterpart->name} telah menolak transaksi hutang Anda. Alasan: {$debtRecord->rejection_reason}",
            debtRecord: $debtRecord,
            data: [
                'debt_id' => $debtRecord->id,
                'counterpart_name' => $debtRecord->counterpart->name,
                'reason' => $debtRecord->rejection_reason,
            ]
        );
    }

    /**
     * Notify debt updated
     *
     * @param DebtRecord $debtRecord
     * @return Notification
     */
    public function notifyDebtUpdated(DebtRecord $debtRecord): Notification
    {
        return $this->sendNotification(
            user: $debtRecord->counterpart,
            notificationTypeCode: 'debt_updated',
            title: 'Transaksi Diperbarui',
            message: "{$debtRecord->creator->name} telah memperbarui transaksi hutang menjadi Rp " . number_format($debtRecord->amount, 0, ',', '.'),
            debtRecord: $debtRecord,
            data: [
                'debt_id' => $debtRecord->id,
                'creator_name' => $debtRecord->creator->name,
                'amount' => $debtRecord->amount,
            ]
        );
    }

    /**
     * Notify debt settled - sends to the other party
     *
     * @param DebtRecord $debtRecord
     * @param User $settledByUser The user who settled the debt
     * @return Notification
     */
    public function notifyDebtSettled(DebtRecord $debtRecord, User $settledByUser): Notification
    {
        // Determine who to notify (the other party)
        $receiverUser = $settledByUser->id === $debtRecord->creator_id 
            ? $debtRecord->counterpart 
            : $debtRecord->creator;

        return $this->sendNotification(
            user: $receiverUser,
            notificationTypeCode: 'debt_settled',
            title: 'Transaksi Diselesaikan',
            message: "{$settledByUser->name} telah menyelesaikan pembayaran hutang sebesar Rp " . number_format($debtRecord->amount, 0, ',', '.'),
            debtRecord: $debtRecord,
            data: [
                'debt_id' => $debtRecord->id,
                'settled_by_name' => $settledByUser->name,
                'amount' => $debtRecord->amount,
            ]
        );
    }

    /**
     * Send due date reminder
     *
     * @param DebtRecord $debtRecord
     * @param int $daysBefore
     * @param User $user
     * @return Notification|null
     */
    public function sendDueReminder(DebtRecord $debtRecord, int $daysBefore = 3, User $user): ?Notification
    {
        // Check if reminder already sent
        if (ReminderLog::isReminderSent($debtRecord->id, $user->id, $daysBefore)) {
            return null;
        }

        $notification = $this->sendNotification(
            user: $user,
            notificationTypeCode: 'reminder_due_date',
            title: 'Pengingat: Transaksi akan Jatuh Tempo',
            message: "Transaksi hutang dari {$debtRecord->creator->name} akan jatuh tempo dalam {$daysBefore} hari. Jumlah: Rp " . number_format($debtRecord->amount, 0, ',', '.'),
            debtRecord: $debtRecord,
            data: [
                'debt_id' => $debtRecord->id,
                'days_before' => $daysBefore,
                'counterpart_name' => $debtRecord->counterpart->name,
                'amount' => $debtRecord->amount,
            ]
        );

        // Log reminder sent
        ReminderLog::create([
            'debt_record_id' => $debtRecord->id,
            'user_id' => $user->id,
            'days_before' => $daysBefore,
            'sent_at' => now(),
        ]);

        return $notification;
    }

    /**
     * Get user notifications with filters
     *
     * @param User $user
     * @param array{
     *     type?: string,
     *     read?: bool|null,
     *     page?: int,
     *     per_page?: int
     * } $filters
     * @return LengthAwarePaginator
     */
    public function getUserNotifications(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;
        $type = $filters['type'] ?? null;
        $read = $filters['read'] ?? null;

        $query = Notification::where('user_id', $user->id)
            ->with('type', 'debtRecord');

        if ($type) {
            $query->whereHas('type', fn ($q) => $q->where('code', $type));
        }

        if ($read === true) {
            $query->whereNotNull('read_at');
        } elseif ($read === false) {
            $query->whereNull('read_at');
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get unread notifications
     *
     * @param User $user
     * @return Collection
     */
    public function getUnreadNotifications(User $user): Collection
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->with('type', 'debtRecord')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @return Notification
     */
    public function markAsRead(int $notificationId): Notification
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->update(['read_at' => now()]);

        return $notification;
    }

    /**
     * Mark notification as unread
     *
     * @param int $notificationId
     * @return Notification
     */
    public function markAsUnread(int $notificationId): Notification
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->update(['read_at' => null]);

        return $notification;
    }

    /**
     * Mark all notifications as read
     *
     * @param User $user
     * @return int
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread count
     *
     * @param User $user
     * @return int
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Delete notification
     *
     * @param int $notificationId
     * @return bool
     */
    public function deleteNotification(int $notificationId): bool
    {
        return Notification::destroy($notificationId) > 0;
    }

    /**
     * Delete all notifications for user
     *
     * @param User $user
     * @return int
     */
    public function deleteAllNotifications(User $user): int
    {
        return Notification::where('user_id', $user->id)->delete();
    }

    /**
     * Get notification statistics
     *
     * @param User $user
     * @return array{
     *     unread_count: int,
     *     total_count: int,
     *     by_type: array
     * }
     */
    public function getNotificationStats(User $user): array
    {
        $unreadCount = $this->getUnreadCount($user);
        $totalCount = Notification::where('user_id', $user->id)->count();

        $byType = Notification::where('user_id', $user->id)
            ->groupBy('notification_type_id')
            ->with('type')
            ->selectRaw('notification_type_id, COUNT(*) as count')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type->name,
                    'count' => $item->count,
                ];
            })
            ->toArray();

        return [
            'unread_count' => $unreadCount,
            'total_count' => $totalCount,
            'by_type' => $byType,
        ];
    }

    /**
     * Send FCM push notification
     *
     * @param User $user
     * @param Notification $notification
     * @return void
     */
    private function sendPushNotification(User $user, Notification $notification): void
    {
        $activeFcmTokens = $user->getActiveFcmTokens();

        foreach ($activeFcmTokens as $token) {
            // Send to FCM (Firebase Cloud Messaging)
            // This is a placeholder - implement with actual FCM integration
            try {
                // $this->fcmClient->sendToDevice(
                //     $token->token,
                //     $notification->title,
                //     $notification->message,
                //     $notification->data
                // );
            } catch (\Exception $e) {
                // Handle FCM errors
            }
        }
    }
}
