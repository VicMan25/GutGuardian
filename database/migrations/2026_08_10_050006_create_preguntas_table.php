<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')->constrained('secciones')->cascadeOnDelete();
            $table->string('codigo', 10);
            $table->text('enunciado');
            $table->enum('tipo', ['single', 'multiple', 'matriz', 'escala']);
            $table->unsignedSmallInteger('orden');
            $table->timestamps();

            $table->index(['seccion_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas');
    }
};
