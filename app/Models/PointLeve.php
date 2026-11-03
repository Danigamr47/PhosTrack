<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointLeve extends Model
{
    public $timestamps = false;

    protected $fillable = ['leve_id', 'id_point', 'x', 'y', 'z', 'type'];

    protected function casts(): array
    {
        return ['x' => 'float', 'y' => 'float', 'z' => 'float'];
    }
}