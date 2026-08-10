<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versiones_modelo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('version')->unique();
            $table->timestamp('entrenado_at')->nullable();
            $table->boolean('activo')->default(false);
            $table->json('coeficientes');
            $table->json('metricas');
            $table->json('mapa_variables');
            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versiones_modelo');
    }
};
