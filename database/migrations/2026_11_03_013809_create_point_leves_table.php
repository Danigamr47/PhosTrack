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
        Schema::create('point_leves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leve_id')->constrained('leves')->cascadeOnDelete();
            $table->string('id_point', 50);
            $table->double('x');
            $table->double('y');
            $table->double('z');
            $table->string('type', 10);
            $table->timestamps();

            $table->unique(['leve_id', 'type', 'id_point']);
        });

         DB::statement("ALTER TABLE point_leves ADD CONSTRAINT point_leves_type_check
            CHECK (type IN ('pied', 'semis'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_leves');
    }
};
