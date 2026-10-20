<?php

namespace App\Enums;

enum ApprovalAction: string
{
    case Submit = 'submit';
    case Return = 'return';
    case Approve = 'approve';
    case Finalize = 'finalize';

    public function label(): string
    {
        return match ($this) {
            self::Submit => 'Dikirim',
            self::Return => 'Dikembalikan untuk revisi',
            self::Approve => 'Disetujui',
            self::Finalize => 'Difinalisasi',
        };
    }
}
