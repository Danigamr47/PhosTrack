<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DraglineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['1250B', '650B', '1260W', 'MARION'] as $nom) {
            \App\Models\Dragline::firstOrCreate(['nom' => $nom]);
        }
    }
}
