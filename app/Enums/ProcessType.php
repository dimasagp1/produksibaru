<?php

namespace App\Enums;

enum ProcessType: string
{
    case MIXING = 'mixing';
    case FILLING = 'filling';
    case SEKUNDER = 'sekunder';

    public function label(): string
    {
        return match ($this) {
            self::MIXING => 'Mixing Primer',
            self::FILLING => 'Filling Primer',
            self::SEKUNDER => 'Sekunder',
        };
    }
}

