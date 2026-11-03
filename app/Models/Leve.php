<?php

namespace App\Models;

use App\Enums\StatutLeve;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leve extends Model
{
    protected $fillable = [
        'date_leve', 'statut', 'volume', 'surface_sol', 'hauteur_max',
        'commentaire', 'stock_id', 'campagne_id', 'technicien_id',
    ];

    protected function casts(): array
    {
        return [
            'date_leve' => 'date',
            'statut' => StatutLeve::class,
            'volume' => 'float',
            'surface_sol' => 'float',
            'hauteur_max' => 'float',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(Campagne::class);
    }

    public function technicien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technicien_id');
    }

    public function estValide(): bool
    {
        return $this->statut === StatutLeve::Valide;
    }

    public function points(): HasMany
    {
    return $this->hasMany(PointLeve::class);
    }
}