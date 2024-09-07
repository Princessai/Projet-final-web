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
        Schema::create('retards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('module_id')->nullable();
            $table->timestamp('seance_heure_debut')->nullable();
            $table->timestamp('seance_heure_fin')->nullable();
            $table->integer('duree')->nullable();
            $table->float('duree_raw')->nullable(); 
            $table->foreignId('seance_id')->constrained()->onDelete('cascade');
            $table->foreignId('annee_id')->constrained();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retards');
    }
};
