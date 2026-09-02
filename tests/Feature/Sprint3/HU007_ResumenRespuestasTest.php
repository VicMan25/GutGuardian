<?php

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use Carbon\Carbon;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function resumenVersionMinima(): VersionModelo
{
    return VersionModelo::create([
        'nombre' => 'Prueba', 'version' => 'test-'.uniqid(), 'activo' => false,
        'coeficientes' => [], 'metricas' => [], 'mapa_variables' => [],
    ]);
}

function resumenDiligenciamientoCompletado(User $user, Instrumento $instrumento, Carbon $fecha, int $categoria): Diligenciamiento
{
    $d = Diligenciamiento::create([
        'user_id' => $user->id, 'instrumento_id' => $instrumento->id,
        'estado' => 'completado', 'completado_at' => $fecha,
    ]);

    EvaluacionRiesgo::create([
        'diligenciamiento_id' => $d->id, 'version_modelo_id' => resumenVersionMinima()->id,
        'categoria' => $categoria, 'prob_0' => 0.5, 'prob_1' => 0.3, 'prob_2' => 0.2,
        'contribuciones' => [], 'evaluado_at' => $fecha,
    ]);

    return $d;
}

describe('HU-007 — Resumen de respuestas registradas', function () {

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

    it('muestra un mensaje informativo cuando el estudiante no tiene registros', function () {
        $this->actingAs($this->estudiante)
            ->get(route('estudiante.inicio'))
            ->assertOk()
            ->assertSee('Aún no tienes registros');
    });

    it('muestra el resultado del diligenciamiento más reciente, no uno anterior', function () {
        resumenDiligenciamientoCompletado($this->estudiante, $this->instrumento, now()->subDays(30), categoria: 0);
        $reciente = resumenDiligenciamientoCompletado($this->estudiante, $this->instrumento, now()->subDays(1), categoria: 2);

        $this->actingAs($this->estudiante)
            ->get(route('estudiante.inicio'))
            ->assertOk()
            ->assertSee('Riesgo alto')
            ->assertSee(route('resultado.show', $reciente), false);
    });

    it('un estudiante solo ve el resumen de sus propios registros', function () {
        $otro = User::factory()->create();
        $otro->assignRole('estudiante');
        resumenDiligenciamientoCompletado($otro, $this->instrumento, now(), categoria: 2);

        $this->actingAs($this->estudiante)
            ->get(route('estudiante.inicio'))
            ->assertOk()
            ->assertSee('Aún no tienes registros')
            ->assertDontSee('Riesgo alto');
    });

    it('ofrece acceso directo al historial y al seguimiento desde el resumen', function () {
        resumenDiligenciamientoCompletado($this->estudiante, $this->instrumento, now(), categoria: 0);

        $this->actingAs($this->estudiante)
            ->get(route('estudiante.inicio'))
            ->assertOk()
            ->assertSee(route('historial.show'), false)
            ->assertSee(route('seguimiento.show'), false);
    });

});
