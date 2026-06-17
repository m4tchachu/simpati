<?php

namespace App\Models;

use App\Enums\DebtStatus;
use App\Enums\DebtType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $creator_id
 * @property int $counterpart_id
 * @property DebtType $type
 * @property float $amount
 * @property string $description
 * @property \Carbon\Carbon $transaction_date
 * @property \Carbon\Carbon $due_date
 * @property DebtStatus $status
 * @property \Carbon\Carbon|null $confirmed_at
 * @property \Carbon\Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property \Carbon\Carbon|null $settled_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $creator
 * @property-read User $counterpart
 * @property-read Collection<DebtStatusChange> $statusChanges
 * @property-read Collection<Notification> $notifications
 * @property-read Collection<ReminderLog> $reminderLogs
 */
#[Fillable(['creator_id', 'counterpart_id', 'type', 'amount', 'description', 'transaction_date', 'due_date', 'status', 'confirmed_at', 'rejected_at', 'rejection_reason', 'settled_at'])]
class DebtRecord extends Model
{
    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DebtType::class,
            'status' => DebtStatus::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'datetime',
            'due_date' => 'datetime',
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    /**
     * User yang membuat catatan (creator/pemberi hutang)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * User yang menerima catatan (counterpart/penerima hutang)
     */
    public function counterpart(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counterpart_id');
    }

    /**
     * History perubahan status
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(DebtStatusChange::class);
    }

    /**
     * Notifications terkait
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Reminder logs terkait
     */
    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    /**
     * Scope: Get pending transactions
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DebtStatus::PENDING);
    }

    /**
     * Scope: Get active transactions
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', DebtStatus::ACTIVE);
    }

    /**
     * Scope: Get rejected transactions
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', DebtStatus::REJECTED);
    }

    /**
     * Scope: Get settled transactions
     */
    public function scopeSettled(Builder $query): Builder
    {
        return $query->where('status', DebtStatus::SETTLED);
    }

    /**
     * Scope: Get transactions by user (creator or counterpart)
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('creator_id', $userId)->orWhere('counterpart_id', $userId);
    }

    /**
     * Scope: Get overdue transactions
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', DebtStatus::ACTIVE)
            ->where('due_date', '<', now());
    }

    /**
     * Scope: Get upcoming due (H-3 and H-1)
     */
    public function scopeUpcomingDue(Builder $query): Builder
    {
        $threeDaysLater = now()->addDays(3);
        $oneDayLater = now()->addDays(1);

        return $query->where('status', DebtStatus::ACTIVE)
            ->whereBetween('due_date', [now(), $oneDayLater])
            ->orWhere(function ($q) use ($threeDaysLater) {
                $q->where('status', DebtStatus::ACTIVE)
                    ->whereDate('due_date', $threeDaysLater->toDateString());
            });
    }

    /**
     * Check if can be confirmed
     */
    public function canBeConfirmed(): bool
    {
        return $this->status === DebtStatus::PENDING;
    }

    /**
     * Check if can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->status === DebtStatus::PENDING;
    }

    /**
     * Check if can be settled
     */
    public function canBeSettled(): bool
    {
        return $this->status === DebtStatus::ACTIVE;
    }

    /**
     * Check if transaction is editable
     */
    public function isEditable(): bool
    {
        return $this->status === DebtStatus::PENDING;
    }

    /**
     * Get transaction info untuk user
     */
    public function getInfoForUser(int $userId): array
    {
        $isCreator = $this->creator_id === $userId;
        $isCounterpart = $this->counterpart_id === $userId;

        if ($isCreator) {
            $otherUser = $this->counterpart;
            $userRole = 'creator';
        } elseif ($isCounterpart) {
            $otherUser = $this->creator;
            $userRole = 'counterpart';
        } else {
            return [];
        }

        return [
            'user_role' => $userRole,
            'other_user' => $otherUser,
            'amount' => $this->amount,
            'type' => $this->type,
            'status' => $this->status,
        ];
    }
}
