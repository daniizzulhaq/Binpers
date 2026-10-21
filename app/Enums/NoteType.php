<?php

namespace App\Enums;

use App\Models\User;

enum NoteType: string
{
    case Positive = 'positive';
    case Negative = 'negative';   // ditampilkan sebagai "Teguran"
    case Violation = 'violation';

    public function label(): string
    {
        return match ($this) {
            self::Positive => 'Apresiasi',
            self::Negative => 'Teguran',
            self::Violation => 'Riwayat Pelanggaran',
        };
    }

    /** Label pendek untuk ruang sempit. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Positive => 'Apresiasi',
            self::Negative => 'Teguran',
            self::Violation => 'Pelanggaran',
        };
    }

    /** Nama warna Bootstrap. */
    public function color(): string
    {
        return match ($this) {
            self::Positive => 'success',
            self::Negative => 'danger',
            self::Violation => 'dark',
        };
    }

    /** Kelas Bootstrap Icons (halaman yang belum memakai SVG). */
    public function icon(): string
    {
        return match ($this) {
            self::Positive => 'bi-hand-thumbs-up',
            self::Negative => 'bi-exclamation-triangle',
            self::Violation => 'bi-file-earmark-x',
        };
    }

    /** Nama ikon pada komponen <x-icon>. */
    public function svg(): string
    {
        return match ($this) {
            self::Positive => 'thumbs-up',
            self::Negative => 'alert',
            self::Violation => 'file-alert',
        };
    }

    /** Hanya HRD yang boleh menginput jenis ini. */
    public function hrdOnly(): bool
    {
        return $this === self::Violation;
    }

       /**
     * Jenis yang boleh diinput oleh pengguna ini.
     * HRD: ketiga jenis. Penilai: Apresiasi dan Teguran.
     *
     * @return self[]
     */
    public static function allowedFor(User $user): array
    {
        if ($user->hasRole(RoleName::Hrd->value)) {
            return self::cases();
        }

        return $user->can('assessments.create')
            ? [self::Positive, self::Negative]
            : [];
    }

     public function hasPoints(): bool
    {
        return $this !== self::Violation;
    }

    /** Arah poin: apresiasi menambah, teguran mengurangi. */
    public function sign(): int
    {
        return match ($this) {
            self::Positive => 1,
            self::Negative => -1,
            self::Violation => 0,
        };
    }
}
