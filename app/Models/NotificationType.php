<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Collection<Notification> $notifications
 */
#[Fillable(['code', 'name', 'description'])]
class NotificationType extends Model
{
    /**
     * Notifications dengan tipe ini
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Notification type codes
     */
    public const DEBT_CREATED = 'debt_created';
    public const DEBT_CONFIRMED = 'debt_confirmed';
    public const DEBT_REJECTED = 'debt_rejected';
    public const DEBT_UPDATED = 'debt_updated';
    public const DEBT_DELETED = 'debt_deleted';
    public const DEBT_SETTLED = 'debt_settled';
    public const REMINDER_DUE_DATE = 'reminder_due_date';
}
