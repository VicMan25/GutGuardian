<?php

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\ItemPregunta;
use App\Modules\Encuestas\Models\Opcion;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Respuesta;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Database\Seeders\VersionModeloSeeder;

/**
 * Responde el subconjunto de preguntas que PredictorService necesita, para
 * poder ejercitar el flujo completo (predicción -> explicabilidad ->
 * persistencia -> vista) sobre el modelo_v1.json real generado por ml/.
 */
function hu009CompletarDiligenciamiento(Diligenciamiento $d): void
{
    $unica = function (string $codigo, int $valor) use ($d) {
        $pregunta = Pregunta::where('codigo', $codigo)->firstOrFail();
        $opcion = Opcion::where('pregunta_id', $pregunta->id)->where('valor_numerico', $valor)->firstOrFail();
        Respuesta::create([
            'diligenciamiento_id' => $d->id, 'pregunta_id' => $pregunta->id,
            'opcion_id' => $opcion->id, 'valor_numerico' => $valor,
        ]);
    };

    $item = function (string $codigo, string $etiqueta, int $valor) use ($d) {
        $pregunta = Pregunta::where('codigo', $codigo)->firstOrFail();
        $itemPregunta = ItemPregunta::where('pregunta_id', $pregunta->id)->where('etiqueta', $etiqueta)->firstOrFail();
        $opcion = Opcion::where('pregunta_id', $pregunta->id)->where('valor_numerico', $valor)->firstOrFail();
        Respuesta::create([
            'diligenciamiento_id' => $d->id, 'pregunta_id' => $pregunta->id,
            'item_pregunta_id' => $itemPregunta->id, 'opcion_id' => $opcion->id, 'valor_numerico' => $valor,
        ]);
    };

    $opcionMultiple = function (string $codigo, string $etiqueta) use ($d) {
        $pregunta = Pregunta::where('codigo', $codigo)->firstOrFail();
        $opcion = Opcion::where('pregunta_id', $pregunta->id)->where('etiqueta', $etiqueta)->firstOrFail();
        Respuesta::create([
            'diligenciamiento_id' => $d->id, 'pregunta_id' => $pregunta->id,
            'opcion_id' => $opcion->id, 'valor_numerico' => $opcion->valor_numerico,
        ]);
    };

    $unica('SD1', 2);
    $unica('SD2', 2);
    $unica('SD4', 3);

    foreach (['P01', 'P02', 'P03', 'P04', 'P05', 'P06', 'P07', 'P08', 'P10'] as $codigo) {
        $unica($codigo, 1);
    }
    $item('P09', 'Alcohol', 0);
    $item('P09', 'Tabaco', 0);

    $opcionMultiple('P14', 'Ninguna');
    $opcionMultiple('P15', 'Ninguna');

    foreach (['Antidepresivos', 'Antiinflamatorios', 'Antibióticos', 'Antiespasmódicos', 'Antidiarreicos', 'Laxantes', 'Corticosteroides'] as $medicamento) {
        $item('P16', $medicamento, 0);
        $item('P17', $medicamento, 0);
    }

    $opcionMultiple('P18', 'Practica deporte');
    $unica('P19', 2);
}

describe('HU-009 — Flujo completo: predicción, explicabilidad y persistencia', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class, VersionModeloSeeder::class]);

        $this->estudiante = User::factory()->create();
        $this->estudiante->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $this->estudiante->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id,
            'instrumento_id' => Instrumento::first()->id,
            'estado' => 'completado',
        ]);
        hu009CompletarDiligenciamiento($this->diligenciamiento);
    });

    it('el estudiante propietario ve su resultado y queda una evaluación persistida con version_modelo_id', function () {
        $respuesta = $this->actingAs($this->estudiante)
            ->get(route('resultado.show', $this->diligenciamiento));

        $respuesta->assertOk();

        $evaluacion = EvaluacionRiesgo::where('diligenciamiento_id', $this->diligenciamiento->id)->first();

        expect($evaluacion)->not->toBeNull()
            ->and($evaluacion->version_modelo_id)->not->toBeNull()
            ->and($evaluacion->categoria)->toBeIn([0, 1, 2])
            ->and((float) $evaluacion->prob_0 + (float) $evaluacion->prob_1 + (float) $evaluacion->prob_2)
            ->toEqualWithDelta(1.0, 0.01)
            ->and($evaluacion->contribuciones)->toHaveCount(5);
    });

    it('reutiliza la evaluación ya persistida en vez de recalcular en cada visita', function () {
        $this->actingAs($this->estudiante)->get(route('resultado.show', $this->diligenciamiento));
        $primeraEvaluacion = EvaluacionRiesgo::where('diligenciamiento_id', $this->diligenciamiento->id)->first();

        $this->actingAs($this->estudiante)->get(route('resultado.show', $this->diligenciamiento))->assertOk();

        expect(EvaluacionRiesgo::where('diligenciamiento_id', $this->diligenciamiento->id)->count())->toBe(1)
            ->and(EvaluacionRiesgo::first()->id)->toBe($primeraEvaluacion->id);
    });

    it('otro estudiante no puede ver el resultado ajeno — 403', function () {
        $otro = User::factory()->create();
        $otro->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $otro->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->actingAs($otro)
            ->get(route('resultado.show', $this->diligenciamiento))
            ->assertForbidden();
    });

    it('un diligenciamiento sin completar no genera evaluación', function () {
        $this->diligenciamiento->update(['estado' => 'en_progreso']);

        $this->actingAs($this->estudiante)
            ->get(route('resultado.show', $this->diligenciamiento))
            ->assertRedirect();

        expect(EvaluacionRiesgo::where('diligenciamiento_id', $this->diligenciamiento->id)->exists())->toBeFalse();
    });

});
