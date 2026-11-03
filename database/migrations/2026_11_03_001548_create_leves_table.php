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
        Schema::create('leves', function (Blueprint $table) {
            $table->id();
            $table->date('date_leve');
            $table->string('statut', 20)->default('brouillon');
            $table->double('volume')->nullable();
            $table->double('surface_sol')->nullable();
            $table->double('hauteur_max')->nullable();
            $table->text('commentaire')->nullable();
            $table->foreignId('stock_id')->constrained()->restrictOnDelete();
            $table->foreignId('campagne_id')->constrained()->restrictOnDelete();
            $table->foreignId('technicien_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['stock_id', 'date_leve']);
        });

         DB::statement("ALTER TABLE leves ADD CONSTRAINT leves_statut_check
            CHECK (statut IN ('brouillon', 'calcule', 'valide'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leves');
    }
};
