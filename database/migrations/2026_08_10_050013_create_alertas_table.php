<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones_riesgo')->cascadeOnDelete();
            $table->string('tipo');
            $table->text('mensaje');
            $table->timestamp('leida_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'leida_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
