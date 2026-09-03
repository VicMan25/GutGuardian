<?php

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function hu014Consentimiento(User $user): void
{
    Consentimiento::create([
        'user_id' => $user->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);
}

function hu014Profesional(): User
{
    $p = User::factory()->create();
    $p->assignRole('profesional_salud');
    hu014Consentimiento($p);

    return $p;
}

function hu014Estudiante(Instrumento $instrumento, int $categoria = 1): User
{
    $e = User::factory()->create();
    $e->assignRole('estudiante');
    hu014Consentimiento($e);

    $d = Diligenciamiento::create([
        'user_id' => $e->id, 'instrumento_id' => $instrumento->id,
        'estado' => 'completado', 'completado_at' => now(),
    ]);

    $v = VersionModelo::create([
        'nombre' => 'Prueba', 'version' => 'test-'.uniqid(), 'activo' => false,
        'coeficientes' => [], 'metricas' => [], 'mapa_variables' => [],
    ]);

    EvaluacionRiesgo::create([
        'diligenciamiento_id' => $d->id, 'version_modelo_id' => $v->id,
        'categoria' => $categoria, 'prob_0' => 0.2, 'prob_1' => 0.5, 'prob_2' => 0.3,
        'contribuciones' => [], 'evaluado_at' => now(),
    ]);

    return $e;
}

describe('HU-014 — Consulta individual de estudiante', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
        $this->instrumento = Instrumento::where('activo', true)->firstOrFail();
    });

    it('el profesional de salud consulta el historial de un estudiante puntual', function () {
        $profesional = hu014Profesional();
        $estudiante = hu014Estudiante($this->instrumento, categoria: 2);

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.show', $estudiante))
            ->assertOk()
            ->assertSee($estudiante->name)
            ->assertSee('Riesgo alto');
    });

    it('un estudiante sin el permiso consultar_estudiantes recibe 403', function () {
        $estudiante = hu014Estudiante($this->instrumento);

        $this->actingAs($estudiante)
            ->get(route('panel.estudiantes.show', $estudiante))
            ->assertForbidden();
    });

    it('no permite consultar la ficha de un usuario que no es estudiante — 404', function () {
        $profesional = hu014Profesional();
        $otroProfesional = hu014Profesional();

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.show', $otroProfesional))
            ->assertNotFound();
    });

});
