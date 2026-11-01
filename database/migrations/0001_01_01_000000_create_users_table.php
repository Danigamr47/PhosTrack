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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('matricule', 20)->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('role', 20)->default('consultant');
            $table->boolean('actif')->default(true);
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Contrainte CHECK : seuls les trois rôles du projet sont acceptés
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check
            CHECK (role IN ('administrateur', 'technicien', 'consultant'))");


        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
