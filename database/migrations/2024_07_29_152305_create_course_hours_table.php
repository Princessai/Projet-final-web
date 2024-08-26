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
        Schema::create('course_hours', function (Blueprint $table) {
            $table->id();
         
            // $table->integer('nbre_heure_total');
            $table->integer('nbre_heure_effectue')->default(0);
            // $table->boolean('statut')->default(false); // terminé ou non 
            $table->unsignedBigInteger('classe_module_id');
            $table->foreign('classe_module_id')->references('id')->on('classe_module');
            $table->foreignId('type_seance_id')->constrained();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_hours');
    }
};
