<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferencia del estudiante sobre los recordatorios por correo de la
 * encuesta por jornadas. Activos por defecto; se desactivan desde /perfil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('recibir_recordatorios')->default(true)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('recibir_recordatorios');
        });
    }
};
