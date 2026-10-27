<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Respuesta;
use App\Modules\Encuestas\Services\JornadaService;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

describe('HU-004 — Captura de la encuesta por jornadas', function () {

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

    it('GET /encuesta crea un diligenciamiento y redirige al día 1', function () {
        $this->diligenciamiento->forceDelete();

        $this->actingAs($this->estudiante)
            ->get(route('encuesta.iniciar'))
            ->assertRedirect();

        $diligenciamiento = Diligenciamiento::where('user_id', $this->estudiante->id)->first();

        expect($diligenciamiento)->not->toBeNull()
            ->and($diligenciamiento->estado)->toBe('pendiente')
            ->and($diligenciamiento->instrumento_id)->toBe($this->instrumento->id);

        $this->actingAs($this->estudiante)
            ->get(route('encuesta.iniciar'))
            ->assertRedirect(route('encuesta.jornada', [$diligenciamiento, 1]));
    });

    it('muestra las preguntas del día solicitado', function () {
        $respuesta = $this->actingAs($this->estudiante)
            ->get(route('encuesta.jornada', [$this->diligenciamiento, 1]));

        $respuesta->assertOk()->assertSee('Día 1 de 3');
        foreach (app(JornadaService::class)->preguntas($this->instrumento->id, 1) as $pregunta) {
            $respuesta->assertSee($pregunta->enunciado);
        }
    });

    it('guardar el día persiste una respuesta por pregunta y lleva a «listo por hoy»', function () {
        $preguntas = app(JornadaService::class)->preguntas($this->instrumento->id, 1);

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), encuestaPayloadJornada($this->instrumento->id, 1))
            ->assertRedirect(route('encuesta.pausa', $this->diligenciamiento));

        // Las múltiples del día 1 (P14, P15) marcan solo «Ninguna»: una fila cada una.
        expect(Respuesta::where('diligenciamiento_id', $this->diligenciamiento->id)->count())->toBe($preguntas->count())
            ->and($this->diligenciamiento->fresh()->estado)->toBe('en_progreso');
    });

    it('guarda correctamente una pregunta de tipo matriz (una respuesta por ítem)', function () {
        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 2]), encuestaPayloadJornada($this->instrumento->id, 2))
            ->assertRedirect();

        $p09 = Pregunta::where('codigo', 'P09')->first();
        expect(Respuesta::where('diligenciamiento_id', $this->diligenciamiento->id)->where('pregunta_id', $p09->id)->count())
            ->toBe($p09->items->count());
    });

    it('guarda correctamente una pregunta de selección múltiple', function () {
        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 3]), encuestaPayloadJornada($this->instrumento->id, 3))
            ->assertRedirect(route('encuesta.pausa', $this->diligenciamiento));

        $p18 = Pregunta::where('codigo', 'P18')->first();

        expect(Respuesta::where('diligenciamiento_id', $this->diligenciamiento->id)->where('pregunta_id', $p18->id)->exists())->toBeTrue();
    });

    it('rechaza el envío si falta una pregunta requerida', function () {
        $payload = encuestaPayloadJornada($this->instrumento->id, 1);
        $primeraPregunta = app(JornadaService::class)->preguntas($this->instrumento->id, 1)->first();
        unset($payload['respuestas'][$primeraPregunta->id]);

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), $payload)
            ->assertSessionHasErrors("respuestas.{$primeraPregunta->id}");

        expect(Respuesta::where('diligenciamiento_id', $this->diligenciamiento->id)->count())->toBe(0);
    });

    it('si falta un ítem de una pregunta matriz, el error queda visible al recargar el día y no avanza', function () {
        $p11 = Pregunta::where('codigo', 'P11')->first();

        $payload = encuestaPayloadJornada($this->instrumento->id, $p11->jornada);
        $itemFaltante = $p11->items->first();
        unset($payload['respuestas'][$p11->id][$itemFaltante->id]);

        $this->actingAs($this->estudiante)
            ->from(route('encuesta.jornada', [$this->diligenciamiento, $p11->jornada]))
            ->post(route('encuesta.guardar', [$this->diligenciamiento, $p11->jornada]), $payload)
            ->assertSessionHasErrors("respuestas.{$p11->id}.{$itemFaltante->id}")
            ->assertRedirect(route('encuesta.jornada', [$this->diligenciamiento, $p11->jornada]));

        expect(Respuesta::where('diligenciamiento_id', $this->diligenciamiento->id)->count())->toBe(0);

        $this->actingAs($this->estudiante)
            ->from(route('encuesta.jornada', [$this->diligenciamiento, $p11->jornada]))
            ->followingRedirects()
            ->post(route('encuesta.guardar', [$this->diligenciamiento, $p11->jornada]), $payload)
            ->assertSee('Falta responder uno o más ítems de esta pregunta.');
    });

    it('un día que no existe en el instrumento responde 404', function () {
        $this->actingAs($this->estudiante)
            ->get(route('encuesta.jornada', [$this->diligenciamiento, 9]))
            ->assertNotFound();
    });

    it('un estudiante no puede diligenciar el instrumento de otro estudiante — 403', function () {
        $otro = User::factory()->create();
        $otro->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $otro->id, 'version_politica' => '1.0', 'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->actingAs($otro)
            ->get(route('encuesta.jornada', [$this->diligenciamiento, 1]))
            ->assertForbidden();
    });

});
