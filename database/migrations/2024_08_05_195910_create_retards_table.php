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
            $table->foreignId('user_id')->constrained();
               $table->unsignedBigInteger('module_id')->nullable();
            $table->integer('duree')->nullable();
            $table->integer('duree_raw')->nullable();
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
