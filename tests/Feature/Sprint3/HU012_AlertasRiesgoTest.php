<?php

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Panel\Models\Alerta;
use App\Modules\Panel\Services\AlertaService;
use Carbon\Carbon;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function alertaVersionMinima(): VersionModelo
{
    return VersionModelo::create([
        'nombre' => 'Prueba', 'version' => 'test-'.uniqid(), 'activo' => false,
        'coeficientes' => [], 'metricas' => [], 'mapa_variables' => [],
    ]);
}

function alertaEvaluacion(User $user, Instrumento $instrumento, Carbon $fecha, int $categoria): EvaluacionRiesgo
{
    $d = Diligenciamiento::create([
        'user_id' => $user->id, 'instrumento_id' => $instrumento->id,
        'estado' => 'completado', 'completado_at' => $fecha,
    ]);

    return EvaluacionRiesgo::create([
        'diligenciamiento_id' => $d->id, 'version_modelo_id' => alertaVersionMinima()->id,
        'categoria' => $categoria, 'prob_0' => 0.5, 'prob_1' => 0.3, 'prob_2' => 0.2,
        'contribuciones' => [], 'evaluado_at' => $fecha,
    ]);
}

describe('HU-012 — Alertas internas de riesgo', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);

        $this->estudiante = User::factory()->create();
        $this->estudiante->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $this->estudiante->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->instrumento = Instrumento::where('activo', true)->firstOrFail();
        $this->servicio = app(AlertaService::class);
    });

    it('no genera alerta en la primera evaluación del estudiante', function () {
        $evaluacion = alertaEvaluacion($this->estudiante, $this->instrumento, now(), categoria: 2);

        $alerta = $this->servicio->generarSiCambioDeRiesgo($evaluacion);

        expect($alerta)->toBeNull()
            ->and(Alerta::count())->toBe(0);
    });

    it('no genera alerta cuando la categoría no cambia', function () {
        alertaEvaluacion($this->estudiante, $this->instrumento, now()->subDays(10), categoria: 1);
        $actual = alertaEvaluacion($this->estudiante, $this->instrumento, now(), categoria: 1);

        $alerta = $this->servicio->generarSiCambioDeRiesgo($actual);

        expect($alerta)->toBeNull()
            ->and(Alerta::count())->toBe(0);
    });

    it('genera una alerta cuando la categoría cambia respecto a la evaluación anterior', function () {
        alertaEvaluacion($this->estudiante, $this->instrumento, now()->subDays(10), categoria: 0);
        $actual = alertaEvaluacion($this->estudiante, $this->instrumento, now(), categoria: 2);

        $alerta = $this->servicio->generarSiCambioDeRiesgo($actual);

        expect($alerta)->not->toBeNull()
            ->and($alerta->user_id)->toBe($this->estudiante->id)
            ->and($alerta->evaluacion_id)->toBe($actual->id)
            ->and($alerta->leida_at)->toBeNull()
            ->and($alerta->mensaje)->toContain('riesgo bajo')->toContain('riesgo alto');
    });

    it('el listado de alertas muestra el indicador de no leída y permite marcarla como leída', function () {
        alertaEvaluacion($this->estudiante, $this->instrumento, now()->subDays(10), categoria: 0);
        $actual = alertaEvaluacion($this->estudiante, $this->instrumento, now(), categoria: 1);
        $alerta = $this->servicio->generarSiCambioDeRiesgo($actual);

        $this->actingAs($this->estudiante)
            ->get(route('alertas.index'))
            ->assertOk()
            ->assertSee('Marcar como leída');

        $this->actingAs($this->estudiante)
            ->patch(route('alertas.marcarLeida', $alerta))
            ->assertRedirect();

        expect($alerta->fresh()->leida_at)->not->toBeNull();

        $this->actingAs($this->estudiante)
            ->get(route('alertas.index'))
            ->assertOk()
            ->assertDontSee('Marcar como leída');
    });

    it('un estudiante no puede marcar como leída la alerta de otro — 403', function () {
        alertaEvaluacion($this->estudiante, $this->instrumento, now()->subDays(10), categoria: 0);
        $actual = alertaEvaluacion($this->estudiante, $this->instrumento, now(), categoria: 1);
        $alerta = $this->servicio->generarSiCambioDeRiesgo($actual);

        $otro = User::factory()->create();
        $otro->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $otro->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->actingAs($otro)
            ->patch(route('alertas.marcarLeida', $alerta))
            ->assertForbidden();
    });

    it('un estudiante solo ve sus propias alertas en el listado', function () {
        $otro = User::factory()->create();
        $otro->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $otro->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);
        alertaEvaluacion($otro, $this->instrumento, now()->subDays(10), categoria: 0);
        $ajena = alertaEvaluacion($otro, $this->instrumento, now(), categoria: 2);
        $this->servicio->generarSiCambioDeRiesgo($ajena);

        $this->actingAs($this->estudiante)
            ->get(route('alertas.index'))
            ->assertOk()
            ->assertSee('No tienes alertas');
    });

});
