<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_riesgo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diligenciamiento_id')->constrained('diligenciamientos')->cascadeOnDelete();
            $table->foreignId('version_modelo_id')->constrained('versiones_modelo')->cascadeOnDelete();
            $table->unsignedTinyInteger('categoria');
            $table->decimal('prob_0', 6, 4);
            $table->decimal('prob_1', 6, 4);
            $table->decimal('prob_2', 6, 4);
            $table->json('contribuciones');
            $table->timestamp('evaluado_at');
            $table->timestamps();

            $table->index('diligenciamiento_id');
            $table->index(['version_modelo_id', 'categoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_riesgo');
    }
};
