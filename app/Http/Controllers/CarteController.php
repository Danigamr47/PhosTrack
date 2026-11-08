<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CarteController extends Controller
{
    public function index()
    {
        return view('carte.index');
    }

    /** Zones et stocks au format GeoJSON (WGS84), pour Leaflet. */
    public function donnees(Request $request): JsonResponse
    {
        $campagneId = (int) $request->query('campagne');
        $zones = DB::select(<<<'SQL'
            SELECT id, nom, statut, periode_debut, periode_fin, surface_ha,
                   ST_AsGeoJSON(ST_Transform(geom, 4326), 6) AS geom
            FROM zones_exploitation
            ORDER BY id
        SQL);

        // Un stock apparaît sur la carte avec l'emprise de son dernier levé VALIDÉ
        $sqlStocks = <<<'SQL'
            SELECT s.id, s.nom, t.libelle AS type,
                   l.id AS leve_id, l.date_leve, l.volume,
                   (SELECT COUNT(*) FROM leves x
                     WHERE x.stock_id = s.id AND x.statut = 'valide') AS nb_leves,
                   (SELECT z.nom FROM zones_exploitation z
                     WHERE ST_Intersects(z.geom, h.geom)
                     ORDER BY ST_Area(ST_Intersection(z.geom, h.geom)) DESC
                     LIMIT 1) AS zone,
                   ST_X(ST_Centroid(ST_Transform(h.geom, 4326))) AS lon,
                   ST_Y(ST_Centroid(ST_Transform(h.geom, 4326))) AS lat,
                   ST_AsGeoJSON(ST_Transform(h.geom, 4326), 6) AS geom
            FROM stocks s
            JOIN types_materiau t ON t.id = s.type_materiau_id
            JOIN LATERAL (
                SELECT id, date_leve, volume
                FROM leves
                WHERE stock_id = s.id AND statut = 'valide' {FILTRE_CAMPAGNE}
                ORDER BY date_leve DESC, id DESC
                LIMIT 1
            ) l ON true
            JOIN LATERAL (
                SELECT ST_SetSRID(ST_ConvexHull(ST_Collect(ST_MakePoint(x, y))), 32628) AS geom
                FROM point_leves
                WHERE leve_id = l.id AND type = 'pied'
            ) h ON GeometryType(h.geom) = 'POLYGON'
            ORDER BY s.nom
        SQL;

        $stocks = DB::select(
            str_replace('{FILTRE_CAMPAGNE}', $campagneId ? 'AND campagne_id = ?' : '', $sqlStocks),
            $campagneId ? [$campagneId] : []
        );

        // Draglines : dernière position de chacune + trajectoire (toutes ses positions) quand il y en a plusieurs
        $draglines = DB::select(<<<'SQL'
            SELECT d.id, d.nom, p.date_mesure, p.x, p.y,
                   (SELECT ST_AsGeoJSON(ST_Transform(ST_SetSRID(ST_MakeLine(ST_MakePoint(q.x, q.y) ORDER BY q.date_mesure, q.id), 32628), 4326), 6)
                      FROM position_draglines q
                     WHERE q.dragline_id = d.id
                    HAVING COUNT(*) >= 2) AS trajectoire,
                   ST_AsGeoJSON(ST_Transform(ST_SetSRID(ST_MakePoint(p.x, p.y), 32628), 4326), 6) AS geom
            FROM draglines d
            JOIN LATERAL (
                SELECT date_mesure, x, y
                FROM position_draglines
                WHERE dragline_id = d.id
                ORDER BY date_mesure DESC, id DESC
                LIMIT 1
            ) p ON true
            ORDER BY d.id
        SQL);

        // Campagnes proposées dans le filtre : celles qui ont au moins un levé validé
        $campagnes = DB::table('campagnes')
            ->whereIn('id', DB::table('leves')->where('statut', 'valide')->select('campagne_id'))
            ->orderByDesc('annee')->orderByDesc('mois')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'libelle' => ucfirst(Carbon::create($c->annee, $c->mois, 1)->locale('fr')->translatedFormat('F Y')),
            ])->values();

        // Évolution du volume du plus ancien au plus récent
        $series = DB::table('leves')
            ->join('campagnes', 'campagnes.id', '=', 'leves.campagne_id')
            ->where('leves.statut', 'valide')
            ->orderBy('leves.date_leve')
            ->orderBy('leves.id')
            ->get(['leves.id', 'leves.stock_id', 'leves.volume', 'campagnes.mois', 'campagnes.annee'])
            ->groupBy('stock_id');

        return response()->json([
            'campagnes' => $campagnes,
            'campagne_active' => $campagneId ?: null,
            'zones' => $this->collection($zones, fn ($z) => [
                'id' => $z->id,
                'nom' => $z->nom,
                'statut' => $z->statut,
                'periode' => $this->periode($z->periode_debut, $z->periode_fin),
                'surface_ha' => $z->surface_ha !== null ? (float) $z->surface_ha : null,
            ]),
            'draglines' => $this->collection($draglines, fn ($d) => [
                'id' => $d->id,
                'nom' => $d->nom,
                'date' => Carbon::parse($d->date_mesure)->format('d/m/Y'),
                'x' => (float) $d->x,
                'y' => (float) $d->y,
                'trajectoire' => $d->trajectoire ? json_decode($d->trajectoire, true) : null,
            ]),
            'stocks' => $this->collection($stocks, fn ($s) => [
                'id' => $s->id,
                'nom' => $s->nom,
                'type' => $s->type,
                'zone' => $s->zone,
                'leve_id' => $s->leve_id,
                'date_leve' => Carbon::parse($s->date_leve)->format('d/m/Y'),
                'volume' => (float) $s->volume,
                'nb_leves' => (int) $s->nb_leves,
                'lon' => (float) $s->lon,
                'lat' => (float) $s->lat,
                'evolution' => $this->evolution($series->get($s->id, collect()), (int) $s->leve_id),
            ]),
        ]);
    }

    /**
     * Évolution du volume jusqu'au levé affiché (six levés au plus)
     * et variation par rapport au levé validé qui le précède.
     */
    private function evolution($levesDuStock, int $leveAffiche): array
    {
        $position = $levesDuStock->search(fn ($l) => (int) $l->id === $leveAffiche);

        if ($position === false) {
            return ['points' => [], 'variation' => null];
        }

        $jusquIci = $levesDuStock->take($position + 1);
        $fenetre = $jusquIci->take(-6)->values();
        $plusieursAnnees = $fenetre->pluck('annee')->unique()->count() > 1;

        $points = $fenetre->map(fn ($l) => [
            'label' => Carbon::create($l->annee, $l->mois, 1)->locale('fr')
                ->translatedFormat($plusieursAnnees ? 'M y' : 'M'),
            'volume' => (float) $l->volume,
        ])->all();

        $variation = null;
        if ($position >= 1) {
            $precedent = (float) $levesDuStock[$position - 1]->volume;
            $actuel = (float) $levesDuStock[$position]->volume;
            $variation = $precedent > 0 ? ($actuel - $precedent) / $precedent * 100 : null;
        }

        return ['points' => $points, 'variation' => $variation];
    }

    private function collection(array $lignes, \Closure $proprietes): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => array_map(fn ($l) => [
                'type' => 'Feature',
                'properties' => $proprietes($l),
                'geometry' => json_decode($l->geom, true),
            ], $lignes),
        ];
    }

    private function periode($debut, $fin): ?string
    {
        return match (true) {
            (bool) $debut && (bool) $fin => "{$debut} – {$fin}",
            (bool) $debut => "depuis {$debut}",
            default => null,
        };
    }
}