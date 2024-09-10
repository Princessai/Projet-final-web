<?php

use App\Enums\seanceStateEnum;
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
        Schema::create('seances', function (Blueprint $table) {
            $table->id();
            $table->integer('etat')->default(seanceStateEnum::ComingSoon->value);
            $table->timestamp('date')->nullable();
            $table->boolean('attendance')->default(false);
            $table->timestamp('heure_debut')->nullable();
            $table->timestamp('heure_fin')->nullable();
            $table->integer('duree')->nullable();       
            $table->float('duree_raw')->nullable();
            $table->foreignId('salle_id')->constrained();
            $table->foreignId('module_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('timetable_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_seance_id')->constrained();
            $table->foreignId('classe_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('annee_id')->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seances');
    }
};
