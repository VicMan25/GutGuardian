<?php

use Database\Seeders\InstrumentoSeeder;
use Illuminate\Support\Facades\DB;

describe('InstrumentoSeeder — instrumento de recolección v1.0', function () {

    beforeEach(function () {
        $this->seed(InstrumentoSeeder::class);
    });

    // ----------------------------------------------------------------
    // Instrumento
    // ----------------------------------------------------------------
    it('crea exactamente 1 instrumento', function () {
        expect(DB::table('instrumentos')->count())->toBe(1);
    });

    it('el instrumento tiene los atributos correctos', function () {
        $inst = DB::table('instrumentos')->first();

        expect($inst->version)->toBe('1.0')
            ->and((bool) $inst->activo)->toBeTrue()
            ->and($inst->tipo)->toBe('mixto');
    });

    // ----------------------------------------------------------------
    // Secciones
    // ----------------------------------------------------------------
    it('crea exactamente 3 secciones', function () {
        expect(DB::table('secciones')->count())->toBe(3);
    });

    it('las secciones tienen los nombres y el orden esperados', function () {
        $nombres = DB::table('secciones')->orderBy('orden')->pluck('nombre')->toArray();

        expect($nombres)->toBe([
            'Datos Sociodemográficos',
            'Hábitos Nutricionales',
            'Información Clínica',
        ]);
    });

    // ----------------------------------------------------------------
    // Preguntas  (24 en total: 4 SD + 10 nutricionales + 10 clínicas)
    // ----------------------------------------------------------------
    it('crea exactamente 24 preguntas', function () {
        expect(DB::table('preguntas')->count())->toBe(24);
    });

    it('la sección sociodemográfica tiene 4 preguntas (SD1-SD4)', function () {
        $seccionId = DB::table('secciones')->where('nombre', 'Datos Sociodemográficos')->value('id');

        expect(DB::table('preguntas')->where('seccion_id', $seccionId)->count())->toBe(4);
    });

    it('la sección nutricional tiene 10 preguntas (P01-P10)', function () {
        $seccionId = DB::table('secciones')->where('nombre', 'Hábitos Nutricionales')->value('id');

        expect(DB::table('preguntas')->where('seccion_id', $seccionId)->count())->toBe(10);
    });

    it('la sección clínica tiene 10 preguntas (P11-P20)', function () {
        $seccionId = DB::table('secciones')->where('nombre', 'Información Clínica')->value('id');

        expect(DB::table('preguntas')->where('seccion_id', $seccionId)->count())->toBe(10);
    });

    it('las preguntas de selección múltiple son P14, P15, P18 y P20', function () {
        $codigos = DB::table('preguntas')
            ->where('tipo', 'multiple')
            ->orderBy('codigo')
            ->pluck('codigo')
            ->toArray();

        expect($codigos)->toBe(['P14', 'P15', 'P18', 'P20']);
    });

    it('las preguntas de tipo matriz son P09, P11, P12, P16 y P17', function () {
        $codigos = DB::table('preguntas')
            ->where('tipo', 'matriz')
            ->orderBy('codigo')
            ->pluck('codigo')
            ->toArray();

        expect($codigos)->toBe(['P09', 'P11', 'P12', 'P16', 'P17']);
    });

    // ----------------------------------------------------------------
    // Ítems de pregunta  (28: P09×2 + P11×6 + P12×6 + P16×7 + P17×7)
    // ----------------------------------------------------------------
    it('crea exactamente 28 ítems de pregunta', function () {
        expect(DB::table('items_pregunta')->count())->toBe(28);
    });

    it('P09 tiene 2 ítems (Alcohol y Tabaco)', function () {
        $preguntaId = DB::table('preguntas')->where('codigo', 'P09')->value('id');
        $etiquetas = DB::table('items_pregunta')
            ->where('pregunta_id', $preguntaId)
            ->orderBy('orden')
            ->pluck('etiqueta')
            ->toArray();

        expect($etiquetas)->toBe(['Alcohol', 'Tabaco']);
    });

    it('P11 y P12 tienen 6 ítems de síntomas cada una', function () {
        $sintomasEsperados = ['Diarrea', 'Dolor abdominal', 'Vómito', 'Náuseas', 'Estreñimiento', 'Fiebre'];

        foreach (['P11', 'P12'] as $codigo) {
            $preguntaId = DB::table('preguntas')->where('codigo', $codigo)->value('id');
            $etiquetas = DB::table('items_pregunta')
                ->where('pregunta_id', $preguntaId)
                ->orderBy('orden')
                ->pluck('etiqueta')
                ->toArray();

            expect($etiquetas)->toBe($sintomasEsperados, "Ítems incorrectos en $codigo");
        }
    });

    it('P16 y P17 tienen 7 ítems de medicamentos cada una', function () {
        $medicamentosEsperados = [
            'Antidepresivos', 'Antiinflamatorios', 'Antibióticos',
            'Antiespasmódicos', 'Antidiarreicos', 'Laxantes', 'Corticosteroides',
        ];

        foreach (['P16', 'P17'] as $codigo) {
            $preguntaId = DB::table('preguntas')->where('codigo', $codigo)->value('id');
            $etiquetas = DB::table('items_pregunta')
                ->where('pregunta_id', $preguntaId)
                ->orderBy('orden')
                ->pluck('etiqueta')
                ->toArray();

            expect($etiquetas)->toBe($medicamentosEsperados, "Ítems incorrectos en $codigo");
        }
    });

    // ----------------------------------------------------------------
    // Opciones  (130 en total)
    // ----------------------------------------------------------------
    it('crea exactamente 130 opciones', function () {
        expect(DB::table('opciones')->count())->toBe(130);
    });

    it('la escala de frecuencia estándar codifica Siempre=3 … Nunca=0', function () {
        $preguntaId = DB::table('preguntas')->where('codigo', 'P01')->value('id');
        $opciones = DB::table('opciones')
            ->where('pregunta_id', $preguntaId)
            ->orderBy('orden')
            ->get(['etiqueta', 'valor_numerico']);

        expect($opciones)->toHaveCount(4);
        expect($opciones[0]->valor_numerico)->toBe(3); // Siempre
        expect($opciones[3]->valor_numerico)->toBe(0); // Nunca
    });

    it('la escala de temporalidad codifica Última semana=5 … Nunca=0', function () {
        $preguntaId = DB::table('preguntas')->where('codigo', 'P11')->value('id');
        $opciones = DB::table('opciones')
            ->where('pregunta_id', $preguntaId)
            ->orderBy('orden')
            ->get(['etiqueta', 'valor_numerico']);

        expect($opciones)->toHaveCount(6);
        expect($opciones[0]->valor_numerico)->toBe(5); // Última semana
        expect($opciones[5]->valor_numerico)->toBe(0); // Nunca
    });

    it('P08 tiene 7 opciones de tiempos de comida (Ninguna=0 a Más de 5=6)', function () {
        $preguntaId = DB::table('preguntas')->where('codigo', 'P08')->value('id');
        $opciones = DB::table('opciones')
            ->where('pregunta_id', $preguntaId)
            ->orderBy('orden')
            ->get(['etiqueta', 'valor_numerico']);

        expect($opciones)->toHaveCount(7);
        expect($opciones[0]->valor_numerico)->toBe(0); // Ninguna
        expect($opciones[6]->valor_numerico)->toBe(6); // Más de 5
    });

    it('P13 tiene escala de dolor 1-5', function () {
        $preguntaId = DB::table('preguntas')->where('codigo', 'P13')->value('id');
        $valores = DB::table('opciones')
            ->where('pregunta_id', $preguntaId)
            ->orderBy('orden')
            ->pluck('valor_numerico')
            ->toArray();

        expect($valores)->toBe([1, 2, 3, 4, 5]);
    });

    it('P14 tiene 9 opciones con "Ninguna" codificada como 0', function () {
        $preguntaId = DB::table('preguntas')->where('codigo', 'P14')->value('id');

        expect(DB::table('opciones')->where('pregunta_id', $preguntaId)->count())->toBe(9);

        $ninguna = DB::table('opciones')
            ->where('pregunta_id', $preguntaId)
            ->where('etiqueta', 'Ninguna')
            ->first();

        expect($ninguna)->not->toBeNull()
            ->and($ninguna->valor_numerico)->toBe(0);
    });

    it('SD4 tiene 10 opciones de semestre con valores 1 a 10', function () {
        $preguntaId = DB::table('preguntas')->where('codigo', 'SD4')->value('id');
        $valores = DB::table('opciones')
            ->where('pregunta_id', $preguntaId)
            ->orderBy('orden')
            ->pluck('valor_numerico')
            ->toArray();

        expect($valores)->toBe(range(1, 10));
    });

    // ----------------------------------------------------------------
    // Idempotencia
    // ----------------------------------------------------------------
    it('ejecutar el seeder dos veces no duplica registros', function () {
        $this->seed(InstrumentoSeeder::class);

        expect(DB::table('instrumentos')->count())->toBe(1)
            ->and(DB::table('secciones')->count())->toBe(3)
            ->and(DB::table('preguntas')->count())->toBe(24)
            ->and(DB::table('items_pregunta')->count())->toBe(28)
            ->and(DB::table('opciones')->count())->toBe(130);
    });
});
