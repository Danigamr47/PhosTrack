<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Calcul du volume d'un stock par PostGIS.
 *
 * Méthode : volume entre deux modèles numériques (TIN de Delaunay)
 *  - MNT « base »    : triangulation des points de PIED (sol naturel) ;
 *  - MNT « surface » : triangulation de tous les points (PIED + SEMIS).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Altitude d'un point (px, py) dans un triangle 3D, par coordonnées barycentriques
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION phos_z_triangle(tri geometry, px double precision, py double precision)
RETURNS double precision
LANGUAGE plpgsql IMMUTABLE AS $$
DECLARE
    r  geometry := ST_ExteriorRing(tri);
    x1 double precision := ST_X(ST_PointN(r, 1));
    y1 double precision := ST_Y(ST_PointN(r, 1));
    z1 double precision := ST_Z(ST_PointN(r, 1));
    x2 double precision := ST_X(ST_PointN(r, 2));
    y2 double precision := ST_Y(ST_PointN(r, 2));
    z2 double precision := ST_Z(ST_PointN(r, 2));
    x3 double precision := ST_X(ST_PointN(r, 3));
    y3 double precision := ST_Y(ST_PointN(r, 3));
    z3 double precision := ST_Z(ST_PointN(r, 3));
    det double precision;
    w1  double precision;
    w2  double precision;
BEGIN
    det := (y2 - y3) * (x1 - x3) + (x3 - x2) * (y1 - y3);
    IF det = 0 THEN
        RETURN NULL;
    END IF;
    w1 := ((y2 - y3) * (px - x3) + (x3 - x2) * (py - y3)) / det;
    w2 := ((y3 - y1) * (px - x3) + (x1 - x3) * (py - y3)) / det;
    RETURN w1 * z1 + w2 * z2 + (1 - w1 - w2) * z3;
END;
$$;
SQL);

        DB::unprepared('DROP FUNCTION IF EXISTS calculer_volume_leve(bigint)');

        // Volume, surface au sol et hauteur maximale d'un levé
        DB::unprepared(<<<'SQL'
CREATE FUNCTION calculer_volume_leve(p_leve_id bigint)
RETURNS TABLE (
    volume double precision,
    surface_sol double precision,
    hauteur_max double precision,
    nb_triangles_surface integer,
    nb_triangles_base integer,
    nb_semis_hors_emprise integer
)
LANGUAGE plpgsql AS $$
BEGIN
    RETURN QUERY
    WITH
    base AS (
        SELECT (ST_Dump(ST_DelaunayTriangles(ST_Collect(ST_SetSRID(ST_MakePoint(x, y, z), 32628))))).geom AS tri
        FROM point_leves
        WHERE leve_id = p_leve_id AND type = 'pied'
    ),
    surf AS (
        SELECT (ST_Dump(ST_DelaunayTriangles(ST_Collect(ST_SetSRID(ST_MakePoint(x, y, z), 32628))))).geom AS tri
        FROM point_leves
        WHERE leve_id = p_leve_id
    ),
    emprise AS (
        SELECT ST_ConvexHull(ST_Collect(ST_MakePoint(x, y))) AS g
        FROM point_leves
        WHERE leve_id = p_leve_id AND type = 'pied'
    ),
    zones AS (
        SELECT s.tri AS ts,
               b.tri AS tb,
               ST_CollectionExtract(ST_Intersection(ST_Force2D(s.tri), ST_Force2D(b.tri)), 3) AS poly
        FROM surf s
        JOIN base b ON ST_Intersects(ST_Force2D(s.tri), ST_Force2D(b.tri))
    ),
    valides AS (
        SELECT ts, tb, poly, ST_Area(poly) AS aire, ST_Centroid(poly) AS c
        FROM zones
        WHERE NOT ST_IsEmpty(poly) AND ST_Area(poly) > 0.000000001
    ),
    prismes AS (
        SELECT aire,
               aire * (phos_z_triangle(ts, ST_X(c), ST_Y(c)) - phos_z_triangle(tb, ST_X(c), ST_Y(c))) AS v
        FROM valides
    ),
    sommets AS (
        SELECT phos_z_triangle(v.ts, ST_X(d.geom), ST_Y(d.geom))
             - phos_z_triangle(v.tb, ST_X(d.geom), ST_Y(d.geom)) AS h
        FROM valides v, LATERAL ST_DumpPoints(v.poly) d
    )
    SELECT (SELECT SUM(prismes.v) FROM prismes),
           (SELECT SUM(prismes.aire) FROM prismes),
           (SELECT MAX(sommets.h) FROM sommets),
           (SELECT COUNT(*)::integer FROM surf),
           (SELECT COUNT(*)::integer FROM base),
           (SELECT COUNT(*)::integer
              FROM point_leves sp, emprise e
             WHERE sp.leve_id = p_leve_id AND sp.type = 'semis'
               AND NOT ST_Covers(e.g, ST_MakePoint(sp.x, sp.y)));
END;
$$;
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS calculer_volume_leve(bigint)');
        DB::unprepared('DROP FUNCTION IF EXISTS phos_z_triangle(geometry, double precision, double precision)');
    }
};