<?php

namespace App\Enums;

enum PlantLocation: string
{
    case PLANT_A_COD = 'PLANT_A_COD';
    case PLANT_A_COL = 'PLANT_A_COL';
    case PLANT_B_SERBUK = 'PLANT_B_SERBUK';
    case PLANT_B_OT = 'PLANT_B_OT';

    public function label(): string
    {
        return match ($this) {
            self::PLANT_A_COD => 'Plant A COD (Cair)',
            self::PLANT_A_COL => 'Plant A COL (Minyak)',
            self::PLANT_B_SERBUK => 'Plant B Serbuk (Pangan)',
            self::PLANT_B_OT => 'Plant B OT (Obat Tradisional)',
        };
    }
}

