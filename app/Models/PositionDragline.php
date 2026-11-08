<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionDragline extends Model
{
    public $timestamps = false;

    protected $fillable = ['dragline_id', 'technicien_id', 'date_mesure', 'x', 'y'];

    protected function casts(): array
    {
        return ['date_mesure' => 'datetime', 'x' => 'float', 'y' => 'float'];
    }

    public function dragline(): BelongsTo
    {
        return $this->belongsTo(Dragline::class);
    }

    public function technicien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technicien_id');
    }
}