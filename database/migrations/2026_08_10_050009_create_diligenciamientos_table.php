<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diligenciamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('instrumento_id')->constrained('instrumentos')->cascadeOnDelete();
            $table->enum('estado', ['pendiente', 'en_progreso', 'completado'])->default('pendiente');
            $table->timestamp('completado_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'instrumento_id']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diligenciamientos');
    }
};
