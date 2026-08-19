<?php

use App\Models\User;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Analitica\Services\PredictorService;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\ItemPregunta;
use App\Modules\Encuestas\Models\Opcion;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Respuesta;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function hu009ResponderUnica(Diligenciamiento $diligenciamiento, string $codigo, int $valor): void
{
    $pregunta = Pregunta::where('codigo', $codigo)->firstOrFail();
    $opcion = Opcion::where('pregunta_id', $pregunta->id)->where('valor_numerico', $valor)->firstOrFail();

    Respuesta::create([
        'diligenciamiento_id' => $diligenciamiento->id,
        'pregunta_id' => $pregunta->id,
        'opcion_id' => $opcion->id,
        'valor_numerico' => $valor,
    ]);
}

function hu009ResponderItem(Diligenciamiento $diligenciamiento, string $codigo, string $etiquetaItem, int $valor): void
{
    $pregunta = Pregunta::where('codigo', $codigo)->firstOrFail();
    $item = ItemPregunta::where('pregunta_id', $pregunta->id)->where('etiqueta', $etiquetaItem)->firstOrFail();
    $opcion = Opcion::where('pregunta_id', $pregunta->id)->where('valor_numerico', $valor)->firstOrFail();

    Respuesta::create([
        'diligenciamiento_id' => $diligenciamiento->id,
        'pregunta_id' => $pregunta->id,
        'item_pregunta_id' => $item->id,
        'opcion_id' => $opcion->id,
        'valor_numerico' => $valor,
    ]);
}

function hu009ResponderOpcionMultiple(Diligenciamiento $diligenciamiento, string $codigo, string $etiquetaOpcion): void
{
    $pregunta = Pregunta::where('codigo', $codigo)->firstOrFail();
    $opcion = Opcion::where('pregunta_id', $pregunta->id)->where('etiqueta', $etiquetaOpcion)->firstOrFail();

    Respuesta::create([
        'diligenciamiento_id' => $diligenciamiento->id,
        'pregunta_id' => $pregunta->id,
        'opcion_id' => $opcion->id,
        'valor_numerico' => $opcion->valor_numerico,
    ]);
}

describe('HU-009 — PredictorService: construcción del vector desde respuestas', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);

        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $this->diligenciamiento = Diligenciamiento::create([
            'user_id' => $estudiante->id,
            'instrumento_id' => Instrumento::first()->id,
            'estado' => 'completado',
        ]);
    });

    it('arma correctamente los predictores sociodemográficos, nutricionales y clínicos resumidos', function () {
        $d = $this->diligenciamiento;

        // SD1 género = Femenino (valor_numerico 2), SD2 edad = 3, SD4 semestre = 6
        hu009ResponderUnica($d, 'SD1', 2);
        hu009ResponderUnica($d, 'SD2', 3);
        hu009ResponderUnica($d, 'SD4', 6);

        // P01-P07: Siempre=3
        foreach (['P01', 'P02', 'P03', 'P04', 'P05', 'P06', 'P07'] as $codigo) {
            hu009ResponderUnica($d, $codigo, 3);
        }
        hu009ResponderUnica($d, 'P08', 3);
        hu009ResponderItem($d, 'P09', 'Alcohol', 2);
        hu009ResponderItem($d, 'P09', 'Tabaco', 0);
        hu009ResponderUnica($d, 'P10', 3);

        // P14: dos antecedentes reales (valor 1 c/u) -> conteo = 2
        hu009ResponderOpcionMultiple($d, 'P14', 'Estrés');
        hu009ResponderOpcionMultiple($d, 'P14', 'Sobrepeso');

        // P15: ninguno
        hu009ResponderOpcionMultiple($d, 'P15', 'Ninguna');

        // P16: dos medicamentos recientes (>=4) de siete
        hu009ResponderItem($d, 'P16', 'Laxantes', 5);   // Última semana
        hu009ResponderItem($d, 'P16', 'Antibióticos', 4); // Último mes
        foreach (['Antidepresivos', 'Antiinflamatorios', 'Antiespasmódicos', 'Antidiarreicos', 'Corticosteroides'] as $item) {
            hu009ResponderItem($d, 'P16', $item, 0);
        }

        // P17: un medicamento frecuente (>=2) de siete
        hu009ResponderItem($d, 'P17', 'Laxantes', 2);
        foreach (['Antidepresivos', 'Antiinflamatorios', 'Antibióticos', 'Antiespasmódicos', 'Antidiarreicos', 'Corticosteroides'] as $item) {
            hu009ResponderItem($d, 'P17', $item, 1);
        }

        // P18: practica deporte + un factor de riesgo (sedentarismo)
        hu009ResponderOpcionMultiple($d, 'P18', 'Practica deporte');
        hu009ResponderOpcionMultiple($d, 'P18', 'Sedentarismo');

        hu009ResponderUnica($d, 'P19', 2);

        $version = new VersionModelo([
            'mapa_variables' => [
                'orden_variables' => [
                    'edad', 'semestre', 'genero_femenino', 'genero_otro',
                    'p01_frutas', 'p09_alcohol', 'p09_tabaco',
                    'p14_antecedentes_personales_count', 'p15_antecedentes_familiares_count',
                    'p16_medicamentos_recientes_count', 'p17_medicamentos_frecuentes_count',
                    'p18_practica_deporte', 'p18_factores_riesgo_count', 'p19_frecuencia_deporte',
                ],
            ],
        ]);

        $vector = (new PredictorService)->armarVector($d, $version);

        expect($vector)->toBe([
            'edad' => 3.0,
            'semestre' => 6.0,
            'genero_femenino' => 1.0,
            'genero_otro' => 0.0,
            'p01_frutas' => 3.0,
            'p09_alcohol' => 2.0,
            'p09_tabaco' => 0.0,
            'p14_antecedentes_personales_count' => 2.0,
            'p15_antecedentes_familiares_count' => 0.0,
            'p16_medicamentos_recientes_count' => 2.0,
            'p17_medicamentos_frecuentes_count' => 1.0,
            'p18_practica_deporte' => 1.0,
            'p18_factores_riesgo_count' => 1.0,
            'p19_frecuencia_deporte' => 2.0,
        ]);
    });

    it('lanza un error si el modelo referencia una variable que no sabe calcular', function () {
        $version = new VersionModelo([
            'mapa_variables' => ['orden_variables' => ['variable_inexistente']],
        ]);

        (new PredictorService)->armarVector($this->diligenciamiento, $version);
    })->throws(RuntimeException::class);

});
