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

/**
 * Construye un valor de envío VÁLIDO para cualquier pregunta, en el mismo
 * formato que enviaría el formulario (ver EncuestaController::reglasSeccion).
 */
function hu004ValorValido(Pregunta $pregunta): int|array
{
    return match ($pregunta->tipo) {
        'escala' => Opcion::where('pregunta_id', $pregunta->id)->first()->valor_numerico,
        'single' => Opcion::where('pregunta_id', $pregunta->id)->first()->id,
        'multiple' => [
            (Opcion::where('pregunta_id', $pregunta->id)->where('etiqueta', 'Ninguna')->first()
                ?? Opcion::where('pregunta_id', $pregunta->id)->first())->id,
        ],
        'matriz' => $pregunta->items->mapWithKeys(fn ($item) => [
            $item->id => Opcion::where('pregunta_id', $pregunta->id)->first()->valor_numerico,
        ])->all(),
    };
}

function hu004PayloadSeccion(Seccion $seccion): array
{
    $respuestas = [];
    foreach ($seccion->preguntas as $pregunta) {
        $respuestas[$pregunta->id] = hu004ValorValido($pregunta);
    }

    return ['respuestas' => $respuestas];
}

describe('HU-004 — Captura de la encuesta por secciones', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);

        $this->estudiante = User::factory()->create();
        $this->estudiante->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $this->estudiante->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->instrumento = Instrumento::where('activo', true)->firstOrFail();
    });

    it('GET /encuesta crea un diligenciamiento y redirige a la primera sección', function () {
        $primeraSeccion = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 1)->first();

        $this->actingAs($this->estudiante)
            ->get(route('encuesta.iniciar'))
            ->assertRedirect();

        $diligenciamiento = Diligenciamiento::where('user_id', $this->estudiante->id)->first();

        expect($diligenciamiento)->not->toBeNull()
            ->and($diligenciamiento->estado)->toBe('pendiente')
            ->and($diligenciamiento->instrumento_id)->toBe($this->instrumento->id);
    });

    it('muestra las preguntas de la sección solicitada', function () {
        $diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'pendiente',
        ]);
        $seccion = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 1)->first();

        $respuesta = $this->actingAs($this->estudiante)
            ->get(route('encuesta.seccion', [$diligenciamiento, 1]));

        $respuesta->assertOk();
        foreach ($seccion->preguntas as $pregunta) {
            $respuesta->assertSee($pregunta->enunciado);
        }
    });

    it('guardar la sección persiste una respuesta por pregunta de tipo escala/single y avanza a la siguiente sección', function () {
        $diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'pendiente',
        ]);
        $seccion1 = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 1)->first();

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$diligenciamiento, 1]), hu004PayloadSeccion($seccion1))
            ->assertRedirect(route('encuesta.seccion', [$diligenciamiento, 2]));

        expect(Respuesta::where('diligenciamiento_id', $diligenciamiento->id)->count())->toBe($seccion1->preguntas->count())
            ->and($diligenciamiento->fresh()->estado)->toBe('en_progreso');
    });

    it('guarda correctamente una pregunta de tipo matriz (una respuesta por ítem)', function () {
        $diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'pendiente',
        ]);
        $seccion2 = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 2)->first();

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$diligenciamiento, 2]), hu004PayloadSeccion($seccion2))
            ->assertRedirect();

        $p09 = Pregunta::where('codigo', 'P09')->first();
        expect(Respuesta::where('diligenciamiento_id', $diligenciamiento->id)->where('pregunta_id', $p09->id)->count())
            ->toBe($p09->items->count());
    });

    it('guarda correctamente una pregunta de selección múltiple', function () {
        $diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'pendiente',
        ]);
        $seccion3 = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 3)->first();

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$diligenciamiento, 3]), hu004PayloadSeccion($seccion3))
            ->assertRedirect(route('encuesta.confirmar', $diligenciamiento));

        $p14 = Pregunta::where('codigo', 'P14')->first();
        $respuestaP14 = Respuesta::where('diligenciamiento_id', $diligenciamiento->id)->where('pregunta_id', $p14->id)->first();

        expect($respuestaP14)->not->toBeNull();
    });

    it('rechaza el envío si falta una pregunta requerida', function () {
        $diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'pendiente',
        ]);
        $seccion1 = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 1)->first();

        $payload = hu004PayloadSeccion($seccion1);
        $primeraPregunta = $seccion1->preguntas->first();
        unset($payload['respuestas'][$primeraPregunta->id]);

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$diligenciamiento, 1]), $payload)
            ->assertSessionHasErrors("respuestas.{$primeraPregunta->id}");

        expect(Respuesta::where('diligenciamiento_id', $diligenciamiento->id)->count())->toBe(0);
    });

    it('si falta un ítem de una pregunta matriz, el error queda visible al recargar la sección y no avanza', function () {
        $diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'pendiente',
        ]);
        $seccionClinica = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 3)->first();
        $p11 = Pregunta::where('codigo', 'P11')->first();

        $payload = hu004PayloadSeccion($seccionClinica);
        $itemFaltante = $p11->items->first();
        unset($payload['respuestas'][$p11->id][$itemFaltante->id]);

        $this->actingAs($this->estudiante)
            ->from(route('encuesta.seccion', [$diligenciamiento, 3]))
            ->post(route('encuesta.guardar', [$diligenciamiento, 3]), $payload)
            ->assertSessionHasErrors("respuestas.{$p11->id}.{$itemFaltante->id}")
            ->assertRedirect(route('encuesta.seccion', [$diligenciamiento, 3]));

        expect(Respuesta::where('diligenciamiento_id', $diligenciamiento->id)->count())->toBe(0);

        $this->actingAs($this->estudiante)
            ->from(route('encuesta.guardar', [$diligenciamiento, 3]))
            ->followingRedirects()
            ->post(route('encuesta.guardar', [$diligenciamiento, 3]), $payload)
            ->assertSee('Falta responder uno o más ítems de esta pregunta.');
    });

    it('un estudiante no puede diligenciar el instrumento de otro estudiante — 403', function () {
        $otro = User::factory()->create();
        $otro->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $otro->id, 'version_politica' => '1.0', 'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'pendiente',
        ]);

        $this->actingAs($otro)
            ->get(route('encuesta.seccion', [$diligenciamiento, 1]))
            ->assertForbidden();
    });

});
