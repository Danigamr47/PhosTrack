<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeMateriau extends Model
{
     protected $table = 'types_materiau';

    protected $fillable = ['libelle'];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'type_materiau_id');
    }
}
