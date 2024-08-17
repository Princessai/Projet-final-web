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
        Schema::create('classe_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classe_id')->constrained();
            $table->foreignId('module_id')->constrained();
            $table->foreignId('annee_id')->constrained();
            $table->integer('nbre_heure_total')->nullable()->default(0);
            $table->integer('nbre_heure_effectue')->nullable()->default(0);
            $table->boolean('statut_cours')->default(false);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classe_module');
    }
};
