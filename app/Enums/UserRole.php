<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MAHASISWA = 'mahasiswa';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::MAHASISWA => 'Mahasiswa',
        };
    }

    /**
     * Get all roles as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
