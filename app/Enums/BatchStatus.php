<?php

namespace App\Enums;

enum BatchStatus: string
{
    case OPEN = 'OPEN';
    case CLOSE = 'CLOSE';
    case HOLD = 'HOLD';
    case REWORK = 'REWORK';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open (Sedang Berjalan)',
            self::CLOSE => 'Close (Selesai)',
            self::HOLD => 'Hold (Ditahan)',
            self::REWORK => 'Rework (Proses Ulang)',
        };
    }
}

