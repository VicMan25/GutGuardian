<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_pregunta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained('preguntas')->cascadeOnDelete();
            $table->string('etiqueta');
            $table->unsignedSmallInteger('orden');
            $table->timestamps();

            $table->index(['pregunta_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_pregunta');
    }
};
