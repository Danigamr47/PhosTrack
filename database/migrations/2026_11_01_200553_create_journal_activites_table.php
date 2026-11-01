<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('journal_activites', function (Blueprint $table) {
            $table->id();
                $table->foreignId('user_id')->constrained();
            $table->timestamp('date_heure')->useCurrent();
            $table->string('action');
            $table->string('entite');
            $table->unsignedBigInteger('id_entite')->nullable();
            $table->index(['entite', 'id_entite']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_activites');
    }
};
