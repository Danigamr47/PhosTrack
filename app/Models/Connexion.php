<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class Connexion extends Model
{
    public $timestamps = false;

    protected $fillable = [
    'user_id', 'matricule', 'date_connexion', 'date_deconnexion',
    'session_id', 'succes', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'date_connexion'   => 'datetime',
            'date_deconnexion' => 'datetime',
            'succes'           => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function ouvrirSession(User $user, Request $request): void
    {
        static::create([
            'user_id'        => $user->id,
            'matricule'      => $user->matricule,
            'date_connexion' => now(),
            'session_id'     => $request->session()->getId(),
            'succes'         => true,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);
    }

    public static function enregistrerEchec(string $matricule, Request $request): void
    {
        static::create([
            'user_id'        => null,
            'matricule'      => $matricule,
            'date_connexion' => now(),
            'succes'         => false,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);
    }

    public static function fermerSession(Request $request): void
    {
        static::where('user_id', $request->user()->id)
            ->where('session_id', $request->session()->getId())
            ->whereNull('date_deconnexion')
            ->update(['date_deconnexion' => now()]);
    }
}