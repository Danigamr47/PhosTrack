<?php

namespace App\Enums;

enum StatutLeve: string
{
    case Brouillon = 'brouillon';
    case Calcule = 'calcule';
    case Valide = 'valide';

    public function libelle(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Calcule => 'Calculé',
            self::Valide => 'Validé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::Brouillon => 'secondary',
            self::Calcule => 'warning',
            self::Valide => 'success',
        };
    }
}