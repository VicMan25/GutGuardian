<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\Opcion;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Respuesta;
use App\Modules\Encuestas\Models\Seccion;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function hu005ValorValido(Pregunta $pregunta, int $offset = 0): int|array
{
    return match ($pregunta->tipo) {
        'escala' => Opcion::where('pregunta_id', $pregunta->id)->orderBy('orden')->get()[$offset]->valor_numerico,
        'single' => Opcion::where('pregunta_id', $pregunta->id)->orderBy('orden')->get()[$offset]->id,
        'multiple' => [
            (Opcion::where('pregunta_id', $pregunta->id)->where('etiqueta', 'Ninguna')->first()
                ?? Opcion::where('pregunta_id', $pregunta->id)->first())->id,
        ],
        'matriz' => $pregunta->items->mapWithKeys(fn ($item) => [
            $item->id => Opcion::where('pregunta_id', $pregunta->id)->orderBy('orden')->get()[$offset]->valor_numerico,
        ])->all(),
    };
}

function hu005PayloadSeccion(Seccion $seccion, int $offset = 0): array
{
    $respuestas = [];
    foreach ($seccion->preguntas as $pregunta) {
        $respuestas[$pregunta->id] = hu005ValorValido($pregunta, $offset);
    }

    return ['respuestas' => $respuestas];
}

describe('HU-005 — Persistencia incremental y reanudación', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);

        $this->estudiante = User::factory()->create();
        $this->estudiante->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $this->estudiante->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->instrumento = Instrumento::where('activo', true)->firstOrFail();
        $this->diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'pendiente',
        ]);
    });

    it('GET /encuesta reanuda en la sección siguiente a la última guardada, sin repetir la ya completa', function () {
        $seccion1 = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 1)->first();

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), hu005PayloadSeccion($seccion1));

        $this->actingAs($this->estudiante)
            ->get(route('encuesta.iniciar'))
            ->assertRedirect(route('encuesta.seccion', [$this->diligenciamiento, 2]));
    });

    it('las respuestas ya guardadas se prellenan al volver a abrir la misma sección', function () {
        $seccion1 = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 1)->first();
        $sd2 = Pregunta::where('codigo', 'SD2')->first();
        $opcionElegida = Opcion::where('pregunta_id', $sd2->id)->orderBy('orden')->first();

        $payload = hu005PayloadSeccion($seccion1);
        $payload['respuestas'][$sd2->id] = $opcionElegida->id;

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), $payload);

        // selectorUnico(<id>) debe llegar con el opcion_id ya guardado como valor inicial.
        $this->actingAs($this->estudiante)
            ->get(route('encuesta.seccion', [$this->diligenciamiento, 1]))
            ->assertOk()
            ->assertSee("selectorUnico({$opcionElegida->id})", false);
    });

    it('las respuestas de una matriz ya guardadas se prellenan (bug corregido en matriz-sintomas)', function () {
        $seccion2 = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 2)->first();
        $p09 = Pregunta::where('codigo', 'P09')->first();
        $itemAlcohol = $p09->items->firstWhere('etiqueta', 'Alcohol');
        $opcionValor2 = Opcion::where('pregunta_id', $p09->id)->where('valor_numerico', 2)->first();

        $payload = hu005PayloadSeccion($seccion2);
        $payload['respuestas'][$p09->id][$itemAlcohol->id] = 2;

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 2]), $payload);

        // Índices dentro de items(): Alcohol=0, Tabaco=1 (orden). Alcohol se
        // sobrescribió a 2; Tabaco quedó en el valor base del payload (3).
        // json_encode(['0'=>2,'1'=>3]) produce el arreglo denso [2,3] — Alpine
        // lee respuestas[0]/respuestas[1] igual con arreglo u objeto JS.
        $this->actingAs($this->estudiante)
            ->get(route('encuesta.seccion', [$this->diligenciamiento, 2]))
            ->assertOk()
            ->assertSee('matrizSintomas(2, [2,3])', false);
    });

    it('guardar la misma sección dos veces actualiza las respuestas en vez de duplicarlas', function () {
        $seccion1 = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 1)->first();

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), hu005PayloadSeccion($seccion1, 0));

        $conteoTrasPrimerGuardado = Respuesta::where('diligenciamiento_id', $this->diligenciamiento->id)->count();

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), hu005PayloadSeccion($seccion1, 1));

        expect(Respuesta::where('diligenciamiento_id', $this->diligenciamiento->id)->count())->toBe($conteoTrasPrimerGuardado);

        $sd2 = Pregunta::where('codigo', 'SD2')->first();
        $valorEsperado = Opcion::where('pregunta_id', $sd2->id)->orderBy('orden')->get()[1]->valor_numerico;
        $respuestaGuardada = Respuesta::where('diligenciamiento_id', $this->diligenciamiento->id)
            ->where('pregunta_id', $sd2->id)->first();

        expect($respuestaGuardada->valor_numerico)->toBe($valorEsperado);
    });

    it('cuando todas las secciones están completas, GET /encuesta redirige a confirmar', function () {
        foreach (Seccion::where('instrumento_id', $this->instrumento->id)->orderBy('orden')->get() as $seccion) {
            $this->actingAs($this->estudiante)
                ->post(route('encuesta.guardar', [$this->diligenciamiento, $seccion->orden]), hu005PayloadSeccion($seccion));
        }

        $this->actingAs($this->estudiante)
            ->get(route('encuesta.iniciar'))
            ->assertRedirect(route('encuesta.confirmar', $this->diligenciamiento));
    });

});
