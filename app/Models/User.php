<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property UserRole $role
 * @property string|null $nim
 * @property int|null $study_program_id
 * @property string|null $fcm_token
 * @property string|null $remember_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read StudyProgram|null $studyProgram
 * @property-read Collection<DebtRecord> $createdDebts
 * @property-read Collection<DebtRecord> $receivedDebts
 * @property-read Collection<FcmToken> $fcmTokens
 * @property-read Collection<Notification> $notifications
 * @property-read Collection<AuditLog> $auditLogs
 * @property-read Collection<ReminderLog> $reminderLogs
 */
#[Fillable(['name', 'email', 'password', 'role', 'nim', 'study_program_id', 'fcm_token', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Study program relationship (hanya untuk mahasiswa)
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * Debt records yang dibuat user (sebagai creator)
     */
    public function createdDebts(): HasMany
    {
        return $this->hasMany(DebtRecord::class, 'creator_id');
    }

    /**
     * Debt records yang diterima user (sebagai counterpart)
     */
    public function receivedDebts(): HasMany
    {
        return $this->hasMany(DebtRecord::class, 'counterpart_id');
    }

    /**
     * FCM tokens untuk push notification
     */
    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    /**
     * Notifications yang diterima user
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Audit logs dari user ini
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Reminder logs untuk user ini
     */
    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Check if user is mahasiswa
     */
    public function isMahasiswa(): bool
    {
        return $this->role === UserRole::MAHASISWA;
    }

    /**
     * Get all debt records (both created and received)
     */
    public function getAllDebts(): Collection
    {
        return $this->createdDebts->merge($this->receivedDebts);
    }

    /**
     * Get active FCM tokens
     */
    public function getActiveFcmTokens(): Collection
    {
        return $this->fcmTokens()->where('is_active', true)->get();
    }
}
