<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Localisation des positions de dragline par rapport au plan des passes et profils.
 *
 * Hypothèse : la ligne du plan est l'AXE de la passe ; la bande utile fait DEMI_LARGEUR_M de chaque côté.
 * Distance à la limite = min( (d2 - d1) / 2 , DEMI_LARGEUR_M - d1 ), où d1 et d2 sont les distances
 * aux axes des deux passes les plus proches. En régime régulier (axes espacés de 40 m) cela donne
 * 20 m - d1 ; dans l'éventail (lignes convergentes) la limite se rapproche automatiquement.
 */
class Localisation
{
    /** Une dragline « approche de la limite de sa passe » sous ce seuil (mètres). */
    public const SEUIL_ALERTE_M = 5.0;

    /** Demi-largeur de la bande d'une passe (mètres). */
    public const DEMI_LARGEUR_M = 20.0;

    /** Localisation de toutes les positions d'une dragline, indexée par l'identifiant de la position. */
    public static function deLaDragline(int $draglineId): array
    {
        return self::executer(
            'SELECT id, x, y FROM position_draglines WHERE dragline_id = ?',
            [$draglineId]
        );
    }

    /** Localisation de la dernière position de chaque dragline, indexée par l'identifiant de la position. */
    public static function dernieresPositions(): array
    {
        return self::executer(
            'SELECT DISTINCT ON (dragline_id) id, x, y
               FROM position_draglines
              ORDER BY dragline_id, date_mesure DESC, id DESC',
            []
        );
    }

    /** ok | proche | hors_plan | inconnu */
    public static function statut(?int $passe, ?float $limite, ?float $seuil = null): string
    {
        $seuil ??= self::SEUIL_ALERTE_M;

        if ($passe === null || $limite === null) {
            return 'inconnu';
        }

        if ($limite < 0) {
            return 'hors_plan';
        }

        return $limite <= $seuil ? 'proche' : 'normal';
    }

    private static function executer(string $sourcePositions, array $liaisons): array
    {
        $lignes = DB::select(
            "SELECT q.id,
                    n.numero AS passe,
                    LEAST((n.d2 - n.d1) / 2, CAST(? AS double precision) - n.d1) AS limite,
                    f.pf_de, f.pf_a
               FROM ({$sourcePositions}) q
              CROSS JOIN LATERAL (SELECT ST_SetSRID(ST_MakePoint(q.x, q.y), 32628) AS g) pt
              CROSS JOIN LATERAL (
                    SELECT (array_agg(t.numero ORDER BY t.d))[1] AS numero, MIN(t.d) AS d1, MAX(t.d) AS d2
                      FROM (SELECT p.numero, ST_Distance(p.geom, pt.g) AS d
                              FROM passes p
                             ORDER BY d
                             LIMIT 2) t
              ) n
              CROSS JOIN LATERAL (
                    SELECT MIN(t.numero) AS pf_de, MAX(t.numero) AS pf_a
                      FROM (SELECT f.numero
                              FROM profils f
                             ORDER BY ST_Distance(f.geom, pt.g)
                             LIMIT 2) t
              ) f",
            array_merge([self::DEMI_LARGEUR_M], $liaisons)
        );

        $resultat = [];

        foreach ($lignes as $l) {
            $passe = $l->passe !== null ? (int) $l->passe : null;
            $limite = $l->limite !== null ? round((float) $l->limite, 1) : null;

            $resultat[(int) $l->id] = (object) [
                'passe' => $passe,
                'limite' => $limite,
                'pf_de' => $l->pf_de !== null ? (int) $l->pf_de : null,
                'pf_a' => $l->pf_a !== null ? (int) $l->pf_a : null,
                'statut' => self::statut($passe, $limite),
            ];
        }

        return $resultat;
    }
}
