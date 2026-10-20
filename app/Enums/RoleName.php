<?php

namespace App\Enums;

enum RoleName: string
{
    case Hrd = 'hrd';
    case Kapolres = 'kapolres';
    case Wakapolres = 'wakapolres';
    case Kabag = 'kabag';
    case Kasat = 'kasat';
    case Kasi = 'kasi';
    case Kanit = 'kanit';
    case Anggota = 'anggota';

    public function label(): string
    {
        return match ($this) {
            self::Hrd => 'HRD / Admin Kepegawaian',
            self::Kapolres => 'Kapolres',
            self::Wakapolres => 'Wakapolres',
            self::Kabag => 'Kabag',
            self::Kasat => 'Kasat',
            self::Kasi => 'Kasi',
            self::Kanit => 'Kanit',
            self::Anggota => 'Anggota',
        };
    }

    /** @return string[] */
    public static function leaders(): array
    {
        return [self::Kapolres->value, self::Wakapolres->value];
    }

    /** @return string[] */
    public static function assessors(): array
    {
        return [self::Kabag->value, self::Kasat->value, self::Kasi->value, self::Kanit->value];
    }

    /** Semua role yang boleh mengisi penilaian: pimpinan + penilai struktural. @return string[] */
    public static function canAssess(): array
    {
        return [...self::leaders(), ...self::assessors()];
    }
}
