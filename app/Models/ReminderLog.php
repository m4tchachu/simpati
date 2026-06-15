<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $debt_record_id
 * @property int $user_id
 * @property int $days_before
 * @property \Carbon\Carbon $sent_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read DebtRecord $debtRecord
 * @property-read User $user
 */
#[Fillable(['debt_record_id', 'user_id', 'days_before', 'sent_at'])]
class ReminderLog extends Model
{
    /**
     * Debt record yang diingatkan
     */
    public function debtRecord(): BelongsTo
    {
        return $this->belongsTo(DebtRecord::class);
    }

    /**
     * User yang menerima reminder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get reminders for H-3
     */
    public function scopeH3Reminders(Builder $query): Builder
    {
        return $query->where('days_before', 3);
    }

    /**
     * Scope: Get reminders for H-1
     */
    public function scopeH1Reminders(Builder $query): Builder
    {
        return $query->where('days_before', 1);
    }

    /**
     * Scope: Filter by debt record
     */
    public function scopeByDebtRecord(Builder $query, int $debtRecordId): Builder
    {
        return $query->where('debt_record_id', $debtRecordId);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by days before
     */
    public function scopeByDaysBefore(Builder $query, int $daysBefore): Builder
    {
        return $query->where('days_before', $daysBefore);
    }

    /**
     * Check if reminder already sent
     */
    public static function isReminderSent(int $debtRecordId, int $userId, int $daysBefore): bool
    {
        return self::where('debt_record_id', $debtRecordId)
            ->where('user_id', $userId)
            ->where('days_before', $daysBefore)
            ->exists();
    }

    /**
     * Get reminder label
     */
    public function getReminderLabel(): string
    {
        return "H-{$this->days_before}";
    }
}
