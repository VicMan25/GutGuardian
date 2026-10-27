<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encuesta por jornadas: el instrumento se reparte en 3 bloques de carga
 * similar que el estudiante responde en días distintos. La sección sigue
 * siendo la agrupación temática (sociodemográfica, nutricional, clínica);
 * la jornada es solo el orden de presentación.
 *
 * El reparto mantiene juntas las preguntas que comparten ítems (P11/P12,
 * P16/P17, P14/P15, P18/P19) y deja en la misma jornada las cuatro que
 * definen la variable Y (P11, P12, P13, P20).
 *
 * Las bases ya sembradas no vuelven a pasar por InstrumentoSeeder (se omite
 * si el instrumento existe), así que la asignación se completa aquí. Es una
 * foto fija del reparto inicial: un reparto distinto va en una migración nueva.
 */
return new class extends Migration
{
    private const REPARTO = [
        1 => ['SD1', 'SD2', 'SD3', 'SD4', 'P01', 'P02', 'P03', 'P04', 'P05', 'P06', 'P07', 'P08', 'P10', 'P14', 'P15'],
        2 => ['P09', 'P11', 'P12', 'P13', 'P20'],
        3 => ['P16', 'P17', 'P18', 'P19'],
    ];

    public function up(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->unsignedTinyInteger('jornada')->default(1)->after('seccion_id');
            $table->index('jornada');
        });

        foreach (self::REPARTO as $jornada => $codigos) {
            DB::table('preguntas')->whereIn('codigo', $codigos)->update(['jornada' => $jornada]);
        }
    }

    public function down(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->dropIndex(['jornada']);
            $table->dropColumn('jornada');
        });
    }
};
