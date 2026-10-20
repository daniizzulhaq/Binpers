<?php

namespace App\Enums;

enum AssessmentStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Revision = 'revision';
    case Approved = 'approved';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Menunggu Pemeriksaan',
            self::Revision => 'Perlu Revisi',
            self::Approved => 'Disetujui',
            self::Finalized => 'Final',
        };
    }

    /** Nama warna Bootstrap untuk badge. */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Submitted => 'warning',
            self::Revision => 'danger',
            self::Approved => 'info',
            self::Finalized => 'success',
        };
    }

    /** Status di mana penilai masih boleh mengubah isi penilaian. */
    public function editable(): bool
    {
        return in_array($this, [self::Draft, self::Revision], true);
    }
}
