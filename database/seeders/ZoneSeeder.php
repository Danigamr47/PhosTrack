<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('zones_exploitation')->delete();

        $fichier = fopen(database_path('data/zones_exploitation.csv'), 'r');
        fgetcsv($fichier); // ligne d'en-tête

        while (($l = fgetcsv($fichier)) !== false) {
            DB::insert(
                "INSERT INTO zones_exploitation
                    (nom, statut, periode_debut, periode_fin, surface_ha, geom, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ST_GeomFromWKB(decode(?, 'hex'), 32628), now(), now())",
                [
                    $l[0],
                    $l[1],
                    $l[2] === '' ? null : (int) $l[2],
                    $l[3] === '' ? null : (int) $l[3],
                    $l[4],
                    $l[5],
                ]
            );
        }

        fclose($fichier);
    }
}