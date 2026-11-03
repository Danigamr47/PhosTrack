<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campagnes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('mois');
            $table->unsignedSmallInteger('annee');
            $table->timestamps();

            $table->unique(['mois', 'annee']);

        });

         DB::statement('ALTER TABLE campagnes ADD CONSTRAINT campagnes_mois_check CHECK (mois BETWEEN 1 AND 12)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campagnes');
    }
};
