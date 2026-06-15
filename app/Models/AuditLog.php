<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $action
 * @property string $table_name
 * @property int $record_id
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 */
#[Fillable(['user_id', 'action', 'table_name', 'record_id', 'old_values', 'new_values', 'ip_address', 'user_agent'])]
class AuditLog extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /**
     * Action constants
     */
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_CONFIRM = 'confirm';
    public const ACTION_REJECT = 'reject';
    public const ACTION_SETTLE = 'settle';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';

    /**
     * User yang melakukan aksi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by action
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Filter by table
     */
    public function scopeByTable(Builder $query, string $tableName): Builder
    {
        return $query->where('table_name', $tableName);
    }

    /**
     * Scope: Filter by record
     */
    public function scopeByRecord(Builder $query, string $tableName, int $recordId): Builder
    {
        return $query->where('table_name', $tableName)
            ->where('record_id', $recordId);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get action label
     */
    public function getActionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_CREATE => 'Dibuat',
            self::ACTION_UPDATE => 'Diubah',
            self::ACTION_DELETE => 'Dihapus',
            self::ACTION_CONFIRM => 'Dikonfirmasi',
            self::ACTION_REJECT => 'Ditolak',
            self::ACTION_SETTLE => 'Dilunasi',
            self::ACTION_LOGIN => 'Login',
            self::ACTION_LOGOUT => 'Logout',
            default => $this->action,
        };
    }

    /**
     * Get changed fields
     */
    public function getChangedFields(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changed = [];
        foreach ($this->new_values as $key => $newValue) {
            if (($this->old_values[$key] ?? null) !== $newValue) {
                $changed[$key] = [
                    'old' => $this->old_values[$key] ?? null,
                    'new' => $newValue,
                ];
            }
        }

        return $changed;
    }
}
