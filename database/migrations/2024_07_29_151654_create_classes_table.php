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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->unsignedBigInteger('coordinateur_id')->nullable();
            $table->foreign('coordinateur_id')->references('id')->on('users')->onDelete('set null');
            $table->foreignId('niveau_id')->constrained();
            $table->foreignId('filiere_id')->constrained();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
