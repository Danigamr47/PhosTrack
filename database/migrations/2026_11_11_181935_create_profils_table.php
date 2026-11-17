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
        Schema::create('profils', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero')->unique();
        });

        
        DB::statement('ALTER TABLE profils ADD COLUMN geom geometry(MultiLineString, 32628) NOT NULL');
        DB::statement('CREATE INDEX profils_geom_idx ON profils USING GIST (geom)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profils');
    }
};
