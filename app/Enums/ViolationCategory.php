<?php

namespace App\Enums;

/** Klasifikasi Riwayat Pelanggaran (diinput HRD). */
enum ViolationCategory: string
{
    case Disiplin = 'disiplin';
    case KodeEtik = 'kode_etik';

    public function label(): string
    {
        return match ($this) {
            self::Disiplin => 'Pelanggaran Disiplin',
            self::KodeEtik => 'Pelanggaran Kode Etik',
        };
    }
}
