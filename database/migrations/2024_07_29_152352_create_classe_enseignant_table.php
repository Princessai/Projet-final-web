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
        Schema::create('classe_enseignant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classe_id')->constrained();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // $table->foreignId('annee_id')->constrained();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classe_enseignant');
    }
};
