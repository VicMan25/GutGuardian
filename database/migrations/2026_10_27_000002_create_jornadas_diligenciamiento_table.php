<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avance por jornada de cada diligenciamiento. Sirve para tres cosas:
 *  - saber qué jornada sigue y desde cuándo se sugiere (día siguiente),
 *  - medir el tiempo activo de diligenciamiento (iniciada → completada),
 *    que ya no puede ser creación → cierre porque el ciclo dura varios días,
 *  - no enviar más de un recordatorio por jornada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jornadas_diligenciamiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diligenciamiento_id')->constrained('diligenciamientos')->cascadeOnDelete();
            $table->unsignedTinyInteger('jornada');
            $table->timestamp('iniciada_at')->nullable();
            $table->timestamp('completada_at')->nullable();
            $table->timestamp('recordatorio_enviado_at')->nullable();
            $table->timestamps();

            $table->unique(['diligenciamiento_id', 'jornada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jornadas_diligenciamiento');
    }
};
