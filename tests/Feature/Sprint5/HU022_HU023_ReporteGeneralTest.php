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

function hu022Consentimiento(User $user): void
{
    Consentimiento::create([
        'user_id' => $user->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);
}

function hu022Profesional(): User
{
    $p = User::factory()->create();
    $p->assignRole('profesional_salud');
    hu022Consentimiento($p);

    return $p;
}

function hu022Evaluacion(Instrumento $instrumento, Carbon $fecha, int $categoria): EvaluacionRiesgo
{
    $e = User::factory()->create();
    $e->assignRole('estudiante');
    hu022Consentimiento($e);

    $d = Diligenciamiento::create([
        'user_id' => $e->id, 'instrumento_id' => $instrumento->id,
        'estado' => 'completado', 'completado_at' => $fecha,
    ]);

    $v = VersionModelo::create([
        'nombre' => 'Prueba', 'version' => 'test-'.uniqid(), 'activo' => false,
        'coeficientes' => [], 'metricas' => [], 'mapa_variables' => [],
    ]);

    return EvaluacionRiesgo::create([
        'diligenciamiento_id' => $d->id, 'version_modelo_id' => $v->id,
        'categoria' => $categoria, 'prob_0' => 0.3, 'prob_1' => 0.4, 'prob_2' => 0.3,
        'contribuciones' => [], 'evaluado_at' => $fecha,
    ]);
}

describe('HU-022/HU-023 — Reporte general de niveles de riesgo y filtrado', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
        $this->instrumento = Instrumento::where('activo', true)->firstOrFail();
    });

    it('muestra la distribución de niveles de riesgo de todas las evaluaciones', function () {
        $profesional = hu022Profesional();
        hu022Evaluacion($this->instrumento, now()->subDays(10), categoria: 0);
        hu022Evaluacion($this->instrumento, now()->subDays(5), categoria: 2);
        hu022Evaluacion($this->instrumento, now()->subDays(1), categoria: 2);

        $this->actingAs($profesional)
            ->get(route('panel.reportes.index'))
            ->assertOk()
            ->assertSee('3 evaluaciones')
            ->assertSeeText('66.7%');
    });

    it('filtra el reporte por rango de fechas', function () {
        $profesional = hu022Profesional();
        hu022Evaluacion($this->instrumento, now()->subMonths(2), categoria: 0);
        hu022Evaluacion($this->instrumento, now()->subDays(1), categoria: 2);

        $this->actingAs($profesional)
            ->get(route('panel.reportes.index', ['desde' => now()->subWeek()->toDateString()]))
            ->assertOk()
            ->assertSee('1 evaluaciones');
    });

    it('filtra el reporte por categoría de riesgo', function () {
        $profesional = hu022Profesional();
        hu022Evaluacion($this->instrumento, now(), categoria: 0);
        hu022Evaluacion($this->instrumento, now(), categoria: 2);

        $this->actingAs($profesional)
            ->get(route('panel.reportes.index', ['categoria' => 2]))
            ->assertOk()
            ->assertSee('1 evaluaciones');
    });

    it('muestra un mensaje informativo cuando no hay evaluaciones en el rango filtrado', function () {
        $profesional = hu022Profesional();
        hu022Evaluacion($this->instrumento, now()->subMonths(3), categoria: 0);

        $this->actingAs($profesional)
            ->get(route('panel.reportes.index', ['desde' => now()->subDay()->toDateString()]))
            ->assertOk()
            ->assertSee('No hay evaluaciones que coincidan con los filtros seleccionados');
    });

    it('un estudiante sin el permiso generar_reportes recibe 403', function () {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        hu022Consentimiento($estudiante);

        $this->actingAs($estudiante)
            ->get(route('panel.reportes.index'))
            ->assertForbidden();
    });

});
