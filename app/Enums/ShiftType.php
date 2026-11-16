<?php

namespace App\Enums;

enum ShiftType: string
{
    case SHIFT_1 = 'shift_1';
    case SHIFT_2 = 'shift_2';
    case SHIFT_3 = 'shift_3';
    case MIDDLE = 'middle';

    public function label(): string
    {
        return match ($this) {
            self::SHIFT_1 => 'Shift 1 (07:00 - 15:30)',
            self::SHIFT_2 => 'Shift 2 (15:30 - 23:30)',
            self::SHIFT_3 => 'Shift 3 (23:30 - 07:00)',
            self::MIDDLE => 'Middle (Flexible/Overtime)',
        };
    }
}

