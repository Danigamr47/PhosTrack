<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Profil topographique (PF55 à PF155), un tous les 250 m (UTM 28N). */
class Profil extends Model
{
    public $timestamps = false;

    protected $fillable = ['numero'];

    protected function casts(): array
    {
        return ['numero' => 'integer'];
    }
}
