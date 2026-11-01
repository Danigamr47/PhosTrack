<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $table = 'zones_exploitation';

    protected $fillable = ['nom', 'statut', 'periode_debut', 'periode_fin', 'surface_ha'];
}
