<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StatutLeve;
use App\Models\JournalActivite;
use App\Models\Leve;
use App\Models\Stock;
use App\Services\Localisation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const COULEURS = ['Phosphate' => '#C9A24B', 'Stérile' => '#8A7F72', 'Charbon' => '#2B2B2E'];

    public function index()
    {
        $utilisateur = auth()->user();
        $estConsultant = $utilisateur->aLeRole(Role::Consultant);
        $estAdmin = $utilisateur->aLeRole(Role::Administrateur);

        // Volume actuel par matériau : dernier levé validé de chaque stock
        $materiaux = collect(DB::select(<<<'SQL'
            SELECT t.libelle AS materiau, COUNT(*) AS nb_stocks, SUM(l.volume) AS volume
            FROM stocks s
            JOIN types_materiau t ON t.id = s.type_materiau_id
            JOIN LATERAL (
                SELECT volume FROM leves
                WHERE stock_id = s.id AND statut = 'valide'
                ORDER BY date_leve DESC, id DESC
                LIMIT 1
            ) l ON true
            GROUP BY t.libelle
            ORDER BY t.libelle
        SQL))->map(fn ($m) => (object) [
            'materiau' => $m->materiau,
            'nb_stocks' => (int) $m->nb_stocks,
            'volume' => (float) $m->volume,
            'couleur' => self::COULEURS[$m->materiau] ?? '#2F7DB5',
        ]);

        // Volume cumulé des levés validés, par campagne (six dernières)
        $campagnes = collect(DB::select(<<<'SQL'
            SELECT c.annee, c.mois, SUM(l.volume) AS volume
            FROM (
                SELECT DISTINCT ON (stock_id, campagne_id) stock_id, campagne_id, volume
                FROM leves
                WHERE statut = 'valide'
                ORDER BY stock_id, campagne_id, date_leve DESC, id DESC
            ) l
            JOIN campagnes c ON c.id = l.campagne_id
            GROUP BY c.annee, c.mois
            ORDER BY c.annee DESC, c.mois DESC
            LIMIT 6
        SQL))->reverse()->values();

        $plusieursAnnees = $campagnes->pluck('annee')->unique()->count() > 1;
        $graphCampagnes = [
            'labels' => $campagnes->map(fn ($c) => Carbon::create($c->annee, $c->mois, 1)->locale('fr')
                ->translatedFormat($plusieursAnnees ? 'M y' : 'M'))->all(),
            'volumes' => $campagnes->map(fn ($c) => round((float) $c->volume, 1))->all(),
        ];

        // Draglines : dernière position, passe et distance à la limite
        $localisations = Localisation::dernieresPositions();
        $draglines = collect(DB::select(<<<'SQL'
            SELECT d.id, d.nom, p.id AS position_id, p.date_mesure
            FROM draglines d
            LEFT JOIN LATERAL (
                SELECT id, date_mesure FROM position_draglines
                WHERE dragline_id = d.id
                ORDER BY date_mesure DESC, id DESC
                LIMIT 1
            ) p ON true
            ORDER BY d.id
        SQL))->map(fn ($d) => (object) [
            'id' => $d->id,
            'nom' => $d->nom,
            'date' => $d->date_mesure ? Carbon::parse($d->date_mesure) : null,
            'loc' => $d->position_id ? ($localisations[$d->position_id] ?? null) : null,
        ]);

        $debutMois = now()->startOfMonth()->toDateString();

        $stats = [
            'volume_total' => $materiaux->sum('volume'),
            'nb_stocks' => Stock::count(),
            'leves_mois' => Leve::where('statut', StatutLeve::Valide->value)->whereDate('date_leve', '>=', $debutMois)->count(),
            'draglines_proches' => $draglines->filter(fn ($d) => $d->loc && $d->loc->statut === 'proche')->count(),
        ];

        // Levés en cours : invisibles pour le Consultant
        $enCours = $estConsultant ? collect() : Leve::with(['stock', 'technicien'])
            ->whereIn('statut', [StatutLeve::Brouillon->value, StatutLeve::Calcule->value])
            ->orderByDesc('date_leve')->orderByDesc('id')
            ->take(5)->get();

        // Activité récente : réservée à l'Administrateur
        $activite = $estAdmin
            ? JournalActivite::with('user')->orderByDesc('date_heure')->take(6)->get()
            : collect();

        return view('dashboard', [
            'stats' => $stats,
            'materiaux' => $materiaux,
            'draglines' => $draglines,
            'enCours' => $enCours,
            'activite' => $activite,
            'estConsultant' => $estConsultant,
            'estAdmin' => $estAdmin,
            'graphiques' => [
                'materiaux' => [
                    'labels' => $materiaux->pluck('materiau')->all(),
                    'volumes' => $materiaux->map(fn ($m) => round($m->volume, 1))->all(),
                    'couleurs' => $materiaux->pluck('couleur')->all(),
                ],
                'campagnes' => $graphCampagnes,
            ],
        ]);
    }
}