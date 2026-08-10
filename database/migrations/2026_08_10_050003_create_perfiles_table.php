<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('genero')->nullable();
            $table->unsignedTinyInteger('edad')->nullable();
            $table->foreignId('programa_id')->nullable()->constrained('programas')->nullOnDelete();
            $table->unsignedTinyInteger('semestre')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles');
    }
};
