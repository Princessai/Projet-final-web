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
        Schema::create('year_segments', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->integer('number');
            $table->timestamp('start')->nullable();
            $table->timestamp('end')->nullable();
            $table->foreignId('annee_id')->constrained();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('year_segments');
    }
};
