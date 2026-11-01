<?php

namespace App\Enums;

enum Role: string
{
    case Administrateur = 'administrateur';
    case Technicien = 'technicien';
    case Consultant = 'consultant';

    public function libelle(): string
    {
        return match ($this) {
            self::Administrateur => 'Administrateur',
            self::Technicien => 'Technicien',
            self::Consultant => 'Consultant',
        };
    }
}