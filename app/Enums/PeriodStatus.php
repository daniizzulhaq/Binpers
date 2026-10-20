<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Dibuka',
            self::Closed => 'Ditutup',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Open => 'success',
            self::Closed => 'dark',
        };
    }
}
