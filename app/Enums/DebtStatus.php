<?php

namespace App\Enums;

enum DebtStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case REJECTED = 'rejected';
    case SETTLED = 'settled';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Konfirmasi',
            self::ACTIVE => 'Aktif',
            self::REJECTED => 'Ditolak',
            self::SETTLED => 'Lunas',
        };
    }

    /**
     * Get color code for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACTIVE => 'info',
            self::REJECTED => 'danger',
            self::SETTLED => 'success',
        };
    }

    /**
     * Check if status can be changed
     */
    public function isChangeable(): bool
    {
        return $this !== self::REJECTED && $this !== self::SETTLED;
    }

    /**
     * Get all statuses as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
