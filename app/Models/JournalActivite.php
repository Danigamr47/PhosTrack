<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalActivite extends Model
{
    public $timestamps = false;
    protected $table = 'journal_activites';
    protected $fillable = ['user_id', 'date_heure', 'action', 'entite', 'id_entite'];

    protected function casts(): array
    {
        return ['date_heure' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function enregistrerAction(User $user, string $action, string $entite, ?int $idEntite = null): void
    {
        static::create([
            'user_id' => $user->id,
            'date_heure' => now(),
            'action' => $action,
            'entite' => $entite,
            'id_entite' => $idEntite,
        ]);
    }
}