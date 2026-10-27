<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

describe('HU-006 — Confirmar y finalizar la encuesta', function () {

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

    it('finalizar sin haber completado todos los días no cambia el estado y muestra un error', function () {
        $this->actingAs($this->estudiante)
            ->post(route('encuesta.finalizar', $this->diligenciamiento))
            ->assertRedirect(route('encuesta.confirmar', $this->diligenciamiento))
            ->assertSessionHas('error');

        expect($this->diligenciamiento->fresh()->estado)->toBe('pendiente')
            ->and($this->diligenciamiento->fresh()->completado_at)->toBeNull();
    });

    it('finalizar con todos los días completos marca el diligenciamiento como completado', function () {
        $actor = $this->actingAs($this->estudiante);
        encuestaCompletarCiclo($actor, $this->diligenciamiento);

        $actor->post(route('encuesta.finalizar', $this->diligenciamiento))
            ->assertRedirect(route('resultado.show', $this->diligenciamiento));

        $fresco = $this->diligenciamiento->fresh();
        expect($fresco->estado)->toBe('completado')
            ->and($fresco->completado_at)->not->toBeNull();
    });

    it('la pantalla de confirmación ofrece el botón de envío solo cuando todo está completo', function () {
        $actor = $this->actingAs($this->estudiante);

        $actor->get(route('encuesta.confirmar', $this->diligenciamiento))
            ->assertOk()
            ->assertDontSee('Enviar encuesta');

        encuestaCompletarCiclo($actor, $this->diligenciamiento);

        $actor->get(route('encuesta.confirmar', $this->diligenciamiento))
            ->assertOk()
            ->assertSee('Enviar encuesta');
    });

    it('un diligenciamiento completado ya no se puede editar (policy bloquea update)', function () {
        $actor = $this->actingAs($this->estudiante);
        encuestaCompletarCiclo($actor, $this->diligenciamiento);
        $actor->post(route('encuesta.finalizar', $this->diligenciamiento));

        $actor->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), [])
            ->assertForbidden();
    });

    it('revisitar la URL de un día de un diligenciamiento completado redirige al resultado, sin dar 403', function () {
        $actor = $this->actingAs($this->estudiante);
        encuestaCompletarCiclo($actor, $this->diligenciamiento);
        $actor->post(route('encuesta.finalizar', $this->diligenciamiento));

        $actor->get(route('encuesta.jornada', [$this->diligenciamiento, 1]))
            ->assertRedirect(route('resultado.show', $this->diligenciamiento));
    });

    it('finalizar dos veces es idempotente: la segunda vez redirige al resultado sin error', function () {
        $actor = $this->actingAs($this->estudiante);
        encuestaCompletarCiclo($actor, $this->diligenciamiento);
        $actor->post(route('encuesta.finalizar', $this->diligenciamiento));

        $actor->post(route('encuesta.finalizar', $this->diligenciamiento))
            ->assertRedirect(route('resultado.show', $this->diligenciamiento));
    });

    it('otro estudiante no puede finalizar un diligenciamiento ajeno — 403', function () {
        $otro = User::factory()->create();
        $otro->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $otro->id, 'version_politica' => '1.0', 'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->actingAs($otro)
            ->post(route('encuesta.finalizar', $this->diligenciamiento))
            ->assertForbidden();
    });

});
