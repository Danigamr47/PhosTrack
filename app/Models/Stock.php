<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
     protected $fillable = ['nom', 'type_materiau_id'];

    public function typeMateriau(): BelongsTo
    {
        return $this->belongsTo(TypeMateriau::class, 'type_materiau_id');
    }
}
