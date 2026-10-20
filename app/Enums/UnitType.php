<?php

namespace App\Enums;

enum UnitType: string
{
    case Polres = 'polres';
    case Bagian = 'bagian';
    case Satuan = 'satuan';
    case Seksi = 'seksi';
    case Unit = 'unit';
    case Polsek = 'polsek';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
