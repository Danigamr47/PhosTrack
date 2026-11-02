<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeMateriauSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         foreach (['Phosphate', 'Stérile', 'Charbon'] as $libelle) {
        \App\Models\TypeMateriau::firstOrCreate(['libelle' => $libelle]);
    }
    }
}
