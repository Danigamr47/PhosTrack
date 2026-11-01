<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;


    protected $table = 'users';

    protected $fillable = [
        'matricule', 'nom', 'prenom', 'role', 'actif', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'actif' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function connexions(): HasMany
    {
        return $this->hasMany(Connexion::class);
    }

     public function estActif(): bool
    {
        return $this->actif;
    }

    public function aLeRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function getNomCompletAttribute(): string
    {
        return $this->prenom.' '.$this->nom;
    }
}