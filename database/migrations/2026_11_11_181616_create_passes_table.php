<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('passes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero')->unique();
            $table->string('bloc', 20);
            $table->boolean('etiquette_plan')->default(false);
        });

        // Axe de la passe (une ou plusieurs lignes : partie « aller » et partie « retour »), UTM 28N
        DB::statement('ALTER TABLE passes ADD COLUMN geom geometry(MultiLineString, 32628) NOT NULL');
        DB::statement('CREATE INDEX passes_geom_idx ON passes USING GIST (geom)');
        DB::statement("ALTER TABLE passes ADD CONSTRAINT passes_bloc_check CHECK (bloc IN ('ouest', 'eventail', 'est'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passes');
    }
};
