<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('codigo_participante')->unique()->nullable()->after('email');
            $table->boolean('activo')->default(true)->after('codigo_participante');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['codigo_participante']);
            $table->dropColumn(['codigo_participante', 'activo']);
            $table->dropSoftDeletes();
        });
    }
};
