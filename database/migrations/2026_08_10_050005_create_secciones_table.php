<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos')->cascadeOnDelete();
            $table->string('nombre');
            $table->unsignedSmallInteger('orden');
            $table->timestamps();

            $table->index(['instrumento_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secciones');
    }
};
