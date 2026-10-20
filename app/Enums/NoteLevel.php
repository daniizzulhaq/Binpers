<?php

namespace App\Enums;

enum NoteLevel: string
{
    case Light = 'light';
    case Medium = 'medium';
    case Heavy = 'heavy';

    /** Apresiasi: Ringan/Sedang/Tinggi. Teguran: Ringan/Sedang/Berat. */
    public function label(?NoteType $type = null): string
    {
        return match ($this) {
            self::Light => 'Ringan',
            self::Medium => 'Sedang',
            self::Heavy => $type === NoteType::Positive ? 'Tinggi' : 'Berat',
        };
    }
}
