<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CarteController extends Controller
{
    public function index()
    {
        return view('carte.index');
    }

    /** Zones et stocks au format GeoJSON (WGS84), pour Leaflet. */
    public function donnees(): JsonResponse
    {
        $zones = DB::select(<<<'SQL'
            SELECT id, nom, statut, periode_debut, periode_fin, surface_ha,
                   ST_AsGeoJSON(ST_Transform(geom, 4326), 6) AS geom
            FROM zones_exploitation
            ORDER BY id
        SQL);

        // Un stock apparaît sur la carte avec l'emprise de son dernier levé VALIDÉ :
        // enveloppe convexe de ses points de pied (la même que pour la surface au sol).
        $stocks = DB::select(<<<'SQL'
            SELECT s.id, s.nom, t.libelle AS type,
                   l.id AS leve_id, l.date_leve, l.volume,
                   (SELECT COUNT(*) FROM leves x
                     WHERE x.stock_id = s.id AND x.statut = 'valide') AS nb_leves,
                   ST_X(ST_Centroid(ST_Transform(h.geom, 4326))) AS lon,
                   ST_Y(ST_Centroid(ST_Transform(h.geom, 4326))) AS lat,
                   ST_AsGeoJSON(ST_Transform(h.geom, 4326), 6) AS geom
            FROM stocks s
            JOIN types_materiau t ON t.id = s.type_materiau_id
            JOIN LATERAL (
                SELECT id, date_leve, volume
                FROM leves
                WHERE stock_id = s.id AND statut = 'valide'
                ORDER BY date_leve DESC, id DESC
                LIMIT 1
            ) l ON true
            JOIN LATERAL (
                SELECT ST_SetSRID(ST_ConvexHull(ST_Collect(ST_MakePoint(x, y))), 32628) AS geom
                FROM point_leves
                WHERE leve_id = l.id AND type = 'pied'
            ) h ON GeometryType(h.geom) = 'POLYGON'
            ORDER BY s.nom
        SQL);

        return response()->json([
            'zones' => $this->collection($zones, fn ($z) => [
                'id' => $z->id,
                'nom' => $z->nom,
                'statut' => $z->statut,
                'periode' => $this->periode($z->periode_debut, $z->periode_fin),
                'surface_ha' => $z->surface_ha !== null ? (float) $z->surface_ha : null,
            ]),
            'stocks' => $this->collection($stocks, fn ($s) => [
                'id' => $s->id,
                'nom' => $s->nom,
                'type' => $s->type,
                'leve_id' => $s->leve_id,
                'date_leve' => Carbon::parse($s->date_leve)->format('d/m/Y'),
                'volume' => (float) $s->volume,
                'nb_leves' => (int) $s->nb_leves,
                'lon' => (float) $s->lon,
                'lat' => (float) $s->lat,
            ]),
        ]);
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