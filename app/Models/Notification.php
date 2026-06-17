<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $notification_type_id
 * @property int|null $debt_record_id
 * @property string $title
 * @property string $message
 * @property array|null $data
 * @property \Carbon\Carbon|null $read_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read NotificationType $type
 * @property-read DebtRecord|null $debtRecord
 */
#[Fillable(['user_id', 'notification_type_id', 'debt_record_id', 'title', 'message', 'data', 'read_at'])]
class Notification extends Model
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
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    /**
     * User penerima notifikasi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tipe notifikasi
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class, 'notification_type_id');
    }

    /**
     * Debt record terkait (opsional)
     */
    public function debtRecord(): BelongsTo
    {
        return $this->belongsTo(DebtRecord::class);
    }

    /**
     * Scope: Get unread notifications
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope: Get read notifications
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope: Filter by type
     */
    public function scopeByType(Builder $query, string $typeCode): Builder
    {
        return $query->whereHas('type', function ($q) use ($typeCode) {
            $q->where('code', $typeCode);
        });
    }

    /**
     * Mark as read
     */
    public function markAsRead(): bool
    {
        return $this->update(['read_at' => now()]);
    }

    /**
     * Mark as unread
     */
    public function markAsUnread(): bool
    {
        return $this->update(['read_at' => null]);
    }

    /**
     * Check if read
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if unread
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
