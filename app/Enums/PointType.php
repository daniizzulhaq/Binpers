<?php

namespace App\Enums;

enum PointType: string
{
    case Earned = 'earned';
    case Adjustment = 'adjustment';
    case Redeemed = 'redeemed';

    public function label(): string
    {
        return match ($this) {
            self::Earned => 'Perolehan',
            self::Adjustment => 'Penyesuaian',
            self::Redeemed => 'Pemberian Apresiasi',
        };
    }
}
