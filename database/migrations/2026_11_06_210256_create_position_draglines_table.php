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
        Schema::create('position_draglines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dragline_id')->constrained('draglines')->restrictOnDelete();
            $table->foreignId('technicien_id')->constrained('users')->restrictOnDelete();
            $table->double('x');
            $table->double('y');
            $table->timestamp('date_mesure');

            $table->index(['dragline_id', 'date_mesure']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_draglines');
    }
};
