<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Respuestas al System Usability Scale (SUS) de la prueba piloto (Sprint 6,
 * objetivo 1.3.2.4). Una evaluación por usuario: el SUS mide la percepción
 * global del sistema, no de cada encuesta diligenciada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_usabilidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('respuestas');
            $table->decimal('puntaje', 5, 2);
            $table->text('comentario')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_usabilidad');
    }
};
