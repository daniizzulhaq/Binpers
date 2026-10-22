<?php

namespace App\Enums;

enum RoleName: string
{
    case Hrd = 'hrd';
    case Kapolres = 'kapolres';
    case Wakapolres = 'wakapolres';
    case Kabag = 'kabag';
    case Kasat = 'kasat';
    case KasiPengawas = 'kasi_pengawas';
    case Kasi = 'kasi';
    case Kapolsek = 'kapolsek';
    case Wakapolsek = 'wakapolsek';
    case Kanit = 'kanit';
    case Kapolpos = 'kapolpos';
    case Pamapta = 'pamapta';
    case Kasubbag = 'kasubbag';
    case Kasubsi = 'kasubsi';
    case Kaur = 'kaur';
    case Paur = 'paur';
    case Kasubnit = 'kasubnit';
    case Anggota = 'anggota';

    public function label(): string
    {
        return match ($this) {
            self::Hrd => 'HRD / Admin Kepegawaian',
            self::Kapolres => 'Kapolres',
            self::Wakapolres => 'Wakapolres',
            self::Kabag => 'Kabag',
            self::Kasat => 'Kasat',
            self::KasiPengawas => 'Kasi Propam / Kasiwas',
            self::Kasi => 'Kasi (Hukum, dll.)',
            self::Kapolsek => 'Kapolsek',
            self::Wakapolsek => 'Wakapolsek',
            self::Kanit => 'Kanit / Kasium / Ka SPKT',
            self::Kapolpos => 'Kapolpos',
            self::Pamapta => 'Pamapta (Ka SPKT Polres)',
            self::Kasubbag => 'Kasubbag',
            self::Kasubsi => 'Kasubsi',
            self::Kaur => 'Kaur',
            self::Paur => 'Paur / Paurmin / Pasiaga',
            self::Kasubnit => 'Kasubnit',
            self::Anggota => 'Anggota',
        };
    }

    /** Pimpinan Polres: memakai layout desktop. @return string[] */
    public static function leaders(): array
    {
        return [self::Kapolres->value, self::Wakapolres->value];
    }

    /** Penilai struktural: memakai layout mobile. @return string[] */
    public static function assessors(): array
    {
        return [
            self::Kabag->value, self::Kasat->value, self::KasiPengawas->value, self::Kasi->value,
            self::Kapolsek->value, self::Wakapolsek->value, ...self::unitHeads(),
        ];
    }

    /** Kepala unit terkecil (menilai anggota unitnya, disetujui kepala unit induk). @return string[] */
    public static function unitHeads(): array
    {
        return [self::Kanit->value, self::Kapolpos->value, self::Pamapta->value];
    }

    /** Role yang hanya dinilai (tidak menilai): memakai halaman anggota. @return string[] */
    public static function members(): array
    {
        return [
            self::Kasubbag->value, self::Kasubsi->value, self::Kaur->value,
            self::Paur->value, self::Kasubnit->value, self::Anggota->value,
        ];
    }

    /** Semua role yang boleh mengisi penilaian. @return string[] */
    public static function canAssess(): array
    {
        return [...self::leaders(), ...self::assessors()];
    }
}
