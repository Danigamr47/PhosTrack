<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PasseProfilSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('passes')->delete();
        DB::table('profils')->delete();

        $this->charger('passes.csv', function (array $l) {
            DB::insert(
                "INSERT INTO passes (numero, bloc, etiquette_plan, geom)
                 VALUES (?, ?, ?, ST_GeomFromWKB(decode(?, 'hex'), 32628))",
                [(int) $l[0], $l[1], (bool) $l[2], $l[3]]
            );
        });

        $this->charger('profils.csv', function (array $l) {
            DB::insert(
                "INSERT INTO profils (numero, geom)
                 VALUES (?, ST_GeomFromWKB(decode(?, 'hex'), 32628))",
                [(int) $l[0], $l[1]]
            );
        });
    }

    private function charger(string $fichier, callable $inserer): void
    {
        $f = fopen(database_path('data/'.$fichier), 'r');
        fgetcsv($f); // en-tête

        while (($ligne = fgetcsv($f)) !== false) {
            $inserer($ligne);
        }

        fclose($f);
    }
}
