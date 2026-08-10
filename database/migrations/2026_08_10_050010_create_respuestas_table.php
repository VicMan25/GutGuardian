<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diligenciamiento_id')->constrained('diligenciamientos')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('preguntas')->cascadeOnDelete();
            $table->foreignId('item_pregunta_id')->nullable()->constrained('items_pregunta')->nullOnDelete();
            $table->foreignId('opcion_id')->nullable()->constrained('opciones')->nullOnDelete();
            $table->integer('valor_numerico')->nullable();
            $table->text('valor_texto')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('diligenciamiento_id');
            $table->index(['diligenciamiento_id', 'pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas');
    }
};
