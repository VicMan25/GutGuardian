<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\EvaluacionUsabilidad;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Reportes\Services\UsabilidadService;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function susUsuario(string $rol): User
{
    $u = User::factory()->create();
    $u->assignRole($rol);
    Consentimiento::create([
        'user_id' => $u->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);

    return $u;
}

function susEstudianteConEncuesta(): User
{
    $e = susUsuario('estudiante');
    Diligenciamiento::create([
        'user_id' => $e->id,
        'instrumento_id' => Instrumento::where('activo', true)->value('id'),
        'estado' => 'completado', 'completado_at' => now(),
    ]);

    return $e;
}

/** @return array<int, int> */
function susRespuestas(int $impares, int $pares): array
{
    $r = [];
    for ($i = 1; $i <= 10; $i++) {
        $r[$i] = $i % 2 === 1 ? $impares : $pares;
    }

    return $r;
}

describe('Sprint 6 — Cálculo del puntaje SUS (Brooke, 1996)', function () {

    it('el mejor patrón posible da 100', function () {
        expect(app(UsabilidadService::class)->puntaje(susRespuestas(5, 1)))->toBe(100.0);
    });

    it('el peor patrón posible da 0', function () {
        expect(app(UsabilidadService::class)->puntaje(susRespuestas(1, 5)))->toBe(0.0);
    });

    it('responder neutral en todo da 50', function () {
        expect(app(UsabilidadService::class)->puntaje(susRespuestas(3, 3)))->toBe(50.0);
    });

    it('clasifica la aceptabilidad según Bangor et al. (2008)', function () {
        $s = app(UsabilidadService::class);

        expect($s->aceptabilidad(85))->toBe('Aceptable')
            ->and($s->aceptabilidad(70))->toBe('Aceptable')
            ->and($s->aceptabilidad(62.5))->toBe('Marginal')
            ->and($s->aceptabilidad(40))->toBe('No aceptable');
    });

});

describe('Sprint 6 — Cuestionario SUS del estudiante', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
    });

    it('el estudiante con una encuesta completada responde el SUS y se guarda su puntaje', function () {
        $e = susEstudianteConEncuesta();

        $this->actingAs($e)
            ->post(route('usabilidad.store'), ['respuestas' => susRespuestas(4, 2)])
            ->assertRedirect(route('usabilidad.show'));

        expect(EvaluacionUsabilidad::where('user_id', $e->id)->value('puntaje'))->toBe(75.0);
    });

    it('rechaza el envío si falta alguna afirmación', function () {
        $e = susEstudianteConEncuesta();
        $respuestas = susRespuestas(4, 2);
        unset($respuestas[7]);

        $this->actingAs($e)
            ->post(route('usabilidad.store'), ['respuestas' => $respuestas])
            ->assertSessionHasErrors('respuestas.7');

        expect(EvaluacionUsabilidad::count())->toBe(0);
    });

    it('rechaza valores fuera de la escala 1 a 5', function () {
        $e = susEstudianteConEncuesta();
        $respuestas = susRespuestas(4, 2);
        $respuestas[3] = 6;

        $this->actingAs($e)
            ->post(route('usabilidad.store'), ['respuestas' => $respuestas])
            ->assertSessionHasErrors('respuestas.3');
    });

    it('solo se puede responder una vez', function () {
        $e = susEstudianteConEncuesta();

        $this->actingAs($e)->post(route('usabilidad.store'), ['respuestas' => susRespuestas(4, 2)]);
        $this->actingAs($e)->post(route('usabilidad.store'), ['respuestas' => susRespuestas(1, 5)]);

        expect(EvaluacionUsabilidad::where('user_id', $e->id)->count())->toBe(1)
            ->and(EvaluacionUsabilidad::where('user_id', $e->id)->value('puntaje'))->toBe(75.0);
    });

    it('sin encuestas completadas no se muestra el cuestionario', function () {
        $e = susUsuario('estudiante');

        $this->actingAs($e)->get(route('usabilidad.show'))->assertRedirect(route('estudiante.inicio'));
        $this->actingAs($e)->post(route('usabilidad.store'), ['respuestas' => susRespuestas(4, 2)]);

        expect(EvaluacionUsabilidad::count())->toBe(0);
    });

});

describe('Sprint 6 — Indicadores de validación (admin)', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
        $this->admin = susUsuario('admin');
    });

    it('resume media, mediana y aceptabilidad del SUS', function () {
        foreach ([susRespuestas(5, 1), susRespuestas(4, 2), susRespuestas(3, 3)] as $r) {
            app(UsabilidadService::class)->registrar(susEstudianteConEncuesta(), $r);
        }

        $resumen = app(UsabilidadService::class)->resumenSus();

        expect($resumen['n'])->toBe(3)
            ->and($resumen['media'])->toBe(75.0)
            ->and($resumen['mediana'])->toBe(75.0)
            ->and($resumen['aceptabilidad'])->toBe(['Aceptable' => 2, 'Marginal' => 1, 'No aceptable' => 0]);

        $this->actingAs($this->admin)
            ->get(route('admin.validacion.index'))
            ->assertOk()
            ->assertSee('75,0');
    });

    it('registra los envíos de sección con y sin errores y calcula la tasa de error', function () {
        $e = susUsuario('estudiante');
        $this->actingAs($e)->get(route('encuesta.iniciar'));
        $d = Diligenciamiento::where('user_id', $e->id)->firstOrFail();

        // Envío vacío de la sección 1: la validación lo rechaza.
        $this->actingAs($e)->post(route('encuesta.guardar', [$d, 1]), [])->assertSessionHasErrors();

        $metricas = app(UsabilidadService::class)->metricasInteraccion();

        expect($metricas['errores']['envios'])->toBe(1)
            ->and($metricas['errores']['con_error'])->toBe(1)
            ->and($metricas['errores']['tasa'])->toBe(100.0);
    });

    it('calcula el tiempo de diligenciamiento de las encuestas completadas', function () {
        $e = susUsuario('estudiante');
        $d = Diligenciamiento::create([
            'user_id' => $e->id, 'instrumento_id' => Instrumento::where('activo', true)->value('id'),
            'estado' => 'completado', 'completado_at' => now(),
        ]);
        $d->forceFill(['created_at' => now()->subMinutes(12)])->saveQuietly();

        expect(app(UsabilidadService::class)->metricasInteraccion()['tiempo']['mediana'])->toBe(12.0);
    });

    it('exporta el SUS anonimizado, sin nombre, correo ni código del estudiante', function () {
        $e = susEstudianteConEncuesta();
        $e->update(['codigo_participante' => 'GG-PILOTO-0042']);
        app(UsabilidadService::class)->registrar($e, susRespuestas(4, 2));

        $csv = $this->actingAs($this->admin)->get(route('admin.validacion.exportarSus'))->assertOk()->streamedContent();

        expect($csv)->toContain('puntaje_sus')->toContain('75')
            ->not->toContain($e->email)
            ->not->toContain($e->name)
            ->not->toContain('GG-PILOTO-0042');
    });

    it('un profesional de salud no accede a los indicadores de validación', function () {
        $this->actingAs(susUsuario('profesional_salud'))
            ->get(route('admin.validacion.index'))
            ->assertForbidden();
    });

});
