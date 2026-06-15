<?php

namespace App\Enums;

enum DebtType: string
{
    case DEBT = 'debt';
    case RECEIVABLE = 'receivable';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::DEBT => 'Hutang',
            self::RECEIVABLE => 'Piutang',
        };
    }

    /**
     * Get all types as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
