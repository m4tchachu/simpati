<?php

namespace App\Models;

use App\Enums\DebtStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $debt_record_id
 * @property int $changed_by_user_id
 * @property DebtStatus $old_status
 * @property DebtStatus $new_status
 * @property string|null $reason
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read DebtRecord $debtRecord
 * @property-read User $changedByUser
 */
#[Fillable(['debt_record_id', 'changed_by_user_id', 'old_status', 'new_status', 'reason'])]
class DebtStatusChange extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_status' => DebtStatus::class,
            'new_status' => DebtStatus::class,
        ];
    }

    /**
     * Debt record yang berubah statusnya
     */
    public function debtRecord(): BelongsTo
    {
        return $this->belongsTo(DebtRecord::class);
    }

    /**
     * User yang melakukan perubahan status
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Get change description
     */
    public function getChangeDescription(): string
    {
        return "{$this->old_status->label()} → {$this->new_status->label()}";
    }
}
