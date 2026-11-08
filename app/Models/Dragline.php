<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dragline extends Model
{
    public $timestamps = false;

    protected $fillable = ['nom'];

    public function positions(): HasMany
    {
        return $this->hasMany(PositionDragline::class);
    }
}