<?php

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function hu016Consentimiento(User $user): void
{
    Consentimiento::create([
        'user_id' => $user->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);
}

function hu016Profesional(): User
{
    $p = User::factory()->create();
    $p->assignRole('profesional_salud');
    hu016Consentimiento($p);

    return $p;
}

function hu016Version(): VersionModelo
{
    return VersionModelo::create([
        'nombre' => 'Prueba', 'version' => 'test-'.uniqid(), 'activo' => false,
        'coeficientes' => [], 'metricas' => [], 'mapa_variables' => [],
    ]);
}

function hu016EstudianteConEvaluaciones(Instrumento $instrumento, array $categorias): User
{
    $e = User::factory()->create();
    $e->assignRole('estudiante');
    hu016Consentimiento($e);

    $version = hu016Version();

    foreach ($categorias as $i => $categoria) {
        $d = Diligenciamiento::create([
            'user_id' => $e->id, 'instrumento_id' => $instrumento->id,
            'estado' => 'completado', 'completado_at' => now()->subDays(count($categorias) - $i),
        ]);

        EvaluacionRiesgo::create([
            'diligenciamiento_id' => $d->id, 'version_modelo_id' => $version->id,
            'categoria' => $categoria, 'prob_0' => 0.2, 'prob_1' => 0.5, 'prob_2' => 0.3,
            'contribuciones' => [], 'evaluado_at' => now()->subDays(count($categorias) - $i),
        ]);
    }

    return $e;
}

describe('HU-016 — Visualización del nivel de riesgo por estudiante', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
        $this->instrumento = Instrumento::where('activo', true)->firstOrFail();
    });

    it('el listado muestra "Sin evaluar" para un estudiante sin ninguna evaluación', function () {
        $profesional = hu016Profesional();
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        hu016Consentimiento($estudiante);

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.index'))
            ->assertOk()
            ->assertSee($estudiante->name)
            ->assertSee('Sin evaluar');
    });

    it('el listado muestra el nivel de riesgo más reciente, no uno antiguo', function () {
        $profesional = hu016Profesional();
        // Primera evaluación bajo, la más reciente alto.
        $estudiante = hu016EstudianteConEvaluaciones($this->instrumento, [0, 2]);

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.index'))
            ->assertOk()
            ->assertSee('Riesgo alto');
    });

    it('la ficha individual grafica la evolución del riesgo con dos o más evaluaciones', function () {
        $profesional = hu016Profesional();
        $estudiante = hu016EstudianteConEvaluaciones($this->instrumento, [1, 2]);

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.show', $estudiante))
            ->assertOk()
            ->assertSee('Evolución del nivel de riesgo');
    });

    it('la ficha individual no grafica evolución con una sola evaluación', function () {
        $profesional = hu016Profesional();
        $estudiante = hu016EstudianteConEvaluaciones($this->instrumento, [1]);

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.show', $estudiante))
            ->assertOk()
            ->assertDontSee('Evolución del nivel de riesgo');
    });

    it('la ficha muestra el aviso de no diagnóstico junto al nivel de riesgo (Resolución 3100)', function () {
        $profesional = hu016Profesional();
        $estudiante = hu016EstudianteConEvaluaciones($this->instrumento, [1]);

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.show', $estudiante))
            ->assertOk()
            ->assertSee('no realiza diagnóstico clínico');
    });

    it('la ficha de un estudiante sin evaluaciones no muestra el aviso de no diagnóstico', function () {
        $profesional = hu016Profesional();
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        hu016Consentimiento($estudiante);

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.show', $estudiante))
            ->assertOk()
            ->assertDontSee('no realiza diagnóstico clínico');
    });

});
