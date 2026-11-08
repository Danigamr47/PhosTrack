<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Zone;
use App\Models\TypeMateriau;
Use App\Models\Dragline;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'matricule' => 'MAT0001',
            'nom' => 'Admin',
            'prenom' => 'Super',
            'role' => \App\Enums\Role::Administrateur,
            'actif' => true,
            'password' => Hash::make('admin123'),
        ]);

        $this->call(ZoneSeeder::class);
        $this->call(TypeMateriauSeeder::class);
        $this->call(DraglineSeeder::class);
    }
}
