<?php

use App\Enums\absenceStateEnum;
use App\Models\Absence;
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
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->integer('etat')->default(absenceStateEnum::notJustified->value);
            $table->foreignId('user_id')->constrained(); // l'étudiant
            $table->foreignId('seance_id')->constrained();
            $table->foreignId('annee_id')->constrained();
            $table->unsignedBigInteger('coordinateur_id')->nullable();
            $table->foreign('coordinateur_id')->references('id')->on('users');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
