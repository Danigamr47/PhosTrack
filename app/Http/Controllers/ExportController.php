<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\JournalActivite;
use App\Models\Leve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Téléchargement des coordonnées d'un levé (tous les rôles).
 * Points : UTM 28N (EPSG:32628, mètres, par défaut) ou WGS84 (EPSG:4326, degrés) au choix (?srs=wgs84).
 * Contour : toujours en WGS84, comme l'exige la norme GeoJSON.
 */
class ExportController extends Controller
{
    public function __invoke(Request $request, Leve $leve, string $type)
    {
        // Le Consultant n'accède qu'aux levés validés
        abort_if($request->user()->aLeRole(Role::Consultant) && ! $leve->estValide(), 404);

        $leve->load('stock.typeMateriau');

        $wgs84 = $request->query('srs') === 'wgs84';
        $systeme = $wgs84 ? 'WGS84' : 'UTM28N';

        $base = 'PhosTrack_'.Str::slug($leve->stock->nom).'_'.$leve->date_leve->format('Y-m-d');

        $reponse = match ($type) {
            'pied', 'semis' => $this->texte($leve, $type, $wgs84, $base.'_'.strtoupper($type).'_'.$systeme.'.txt'),
            'tous' => $this->csv($leve, $wgs84, $base.'_points_'.$systeme.'.csv'),
            'contour' => $this->contour($leve, $base.'_contour_WGS84.geojson'),
        };

        JournalActivite::enregistrerAction(
            $request->user(),
            'Coordonnées exportées ('.$type.($type === 'contour' ? '' : ', '.$systeme).')',
            'Leve',
            $leve->id
        );

        return $reponse;
    }

    /** Requête des points : en UTM telle quelle, ou convertie en degrés (X = longitude, Y = latitude). */
    private function requetePoints(Leve $leve, bool $wgs84)
    {
        $requete = DB::table('point_leves')->where('leve_id', $leve->id);

        if ($wgs84) {
            $point = 'ST_Transform(ST_SetSRID(ST_MakePoint(x, y), 32628), 4326)';
            $requete->selectRaw("id, id_point, type, z, ST_X($point) AS x, ST_Y($point) AS y");
        } else {
            $requete->select('id', 'id_point', 'type', 'x', 'y', 'z');
        }

        return $requete;
    }

    /** Même format que les fichiers importés : ID, X, Y, Z séparés par une tabulation, sans en-tête. */
    private function texte(Leve $leve, string $type, bool $wgs84, string $nomFichier)
    {
        return response()->streamDownload(function () use ($leve, $type, $wgs84) {
            $sortie = fopen('php://output', 'w');
            $decimales = $wgs84 ? 8 : 4; // 8 décimales de degré = moins d'un millimètre

            $points = $this->requetePoints($leve, $wgs84)->where('type', $type)->orderBy('id')->cursor();

            foreach ($points as $p) {
                fwrite($sortie, implode("\t", [
                    $p->id_point,
                    number_format($p->x, $decimales, '.', ''),
                    number_format($p->y, $decimales, '.', ''),
                    number_format($p->z, 4, '.', ''),
                ])."\n");
            }

            fclose($sortie);
        }, $nomFichier, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** CSV « à la française » s'ouvre directement dans Excel. */
    private function csv(Leve $leve, bool $wgs84, string $nomFichier)
    {
        return response()->streamDownload(function () use ($leve, $wgs84) {
            $sortie = fopen('php://output', 'w');
            $decimales = $wgs84 ? 8 : 4;

            fwrite($sortie, "\xEF\xBB\xBF");
            fwrite($sortie, ($wgs84 ? "ID;Longitude;Latitude;Z;Type" : "ID;X;Y;Z;Type")."\n");

            $points = $this->requetePoints($leve, $wgs84)->orderBy('type')->orderBy('id')->cursor();

            foreach ($points as $p) {
                fwrite($sortie, implode(';', [
                    $p->id_point,
                    number_format($p->x, $decimales, ',', ''),
                    number_format($p->y, $decimales, ',', ''),
                    number_format($p->z, 4, ',', ''),
                    strtoupper($p->type),
                ])."\n");
            }

            fclose($sortie);
        }, $nomFichier, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Contour du stock en GeoJSON, pour QGIS ou tout SIG. */
    private function contour(Leve $leve, string $nomFichier)
    {
        $ligne = DB::selectOne(<<<'SQL'
            SELECT ST_AsGeoJSON(ST_Transform(h.geom, 4326), 7) AS geom
            FROM (
                SELECT ST_SetSRID(ST_ConvexHull(ST_Collect(ST_MakePoint(x, y))), 32628) AS geom
                FROM point_leves
                WHERE leve_id = ? AND type = 'pied'
            ) h
            WHERE GeometryType(h.geom) = 'POLYGON'
        SQL, [$leve->id]);

        abort_if(! $ligne, 404, 'Contour indisponible : points de pied insuffisants.');

        $json = json_encode([
            'type' => 'FeatureCollection',
            'name' => 'Contour '.$leve->stock->nom,
            'features' => [[
                'type' => 'Feature',
                'properties' => [
                    'stock' => $leve->stock->nom,
                    'materiau' => $leve->stock->typeMateriau->libelle,
                    'date_leve' => $leve->date_leve->format('Y-m-d'),
                    'volume_m3' => $leve->volume,
                    'surface_sol_m2' => $leve->surface_sol,
                    'hauteur_max_m' => $leve->hauteur_max,
                    'systeme' => 'WGS84 (EPSG:4326)',
                ],
                'geometry' => json_decode($ligne->geom, true),
            ]],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return response($json, 200, [
            'Content-Type' => 'application/geo+json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$nomFichier.'"',
        ]);
    }
}