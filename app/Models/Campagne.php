<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Campagne extends Model
{
    protected $fillable = ['mois', 'annee'];

    public function leves(): HasMany
    {
        return $this->hasMany(Leve::class);
    }

    public function getLibelleAttribute(): string
    {
        return ucfirst(Carbon::create($this->annee, $this->mois, 1)->locale('fr')->translatedFormat('F Y'));
    }

    public function getCleAttribute(): string
    {
        return sprintf('%04d-%02d', $this->annee, $this->mois);
    }
}