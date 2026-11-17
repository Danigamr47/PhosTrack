<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Passe extends Model
{
    public $timestamps = false;

    protected $fillable = ['numero', 'bloc', 'etiquette_plan'];

    protected function casts(): array
    {
        return ['numero' => 'integer', 'etiquette_plan' => 'boolean'];
    }
}
