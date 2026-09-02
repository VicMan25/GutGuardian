<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Usuarios\Models\Perfil;
use App\Modules\Usuarios\Models\Programa;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\ProgramasSeeder;
use Database\Seeders\RolesPermisosSeeder;

function hu015Consentimiento(User $user): void
{
    Consentimiento::create([
        'user_id' => $user->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);
}

function hu015Profesional(): User
{
    $p = User::factory()->create();
    $p->assignRole('profesional_salud');
    hu015Consentimiento($p);

    return $p;
}

function hu015Estudiante(string $name, Programa $programa): User
{
    $e = User::factory()->create(['name' => $name]);
    $e->assignRole('estudiante');
    hu015Consentimiento($e);
    Perfil::create(['user_id' => $e->id, 'genero' => 'otro', 'edad' => 20, 'programa_id' => $programa->id, 'semestre' => 3]);

    return $e;
}

describe('HU-015 — Listado general de estudiantes', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, ProgramasSeeder::class, InstrumentoSeeder::class]);
        $this->programa = Programa::where('nombre', 'Enfermería')->firstOrFail();
    });

    it('el profesional de salud ve el listado general de estudiantes', function () {
        $profesional = hu015Profesional();
        hu015Estudiante('Ana Torres', $this->programa);
        hu015Estudiante('Luis Pérez', $this->programa);

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.index'))
            ->assertOk()
            ->assertSee('Ana Torres')
            ->assertSee('Luis Pérez');
    });

    it('permite buscar por nombre', function () {
        $profesional = hu015Profesional();
        hu015Estudiante('Ana Torres', $this->programa);
        hu015Estudiante('Luis Pérez', $this->programa);

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.index', ['q' => 'Torres']))
            ->assertOk()
            ->assertSee('Ana Torres')
            ->assertDontSee('Luis Pérez');
    });

    it('muestra un mensaje informativo cuando no hay estudiantes registrados', function () {
        $profesional = hu015Profesional();

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.index'))
            ->assertOk()
            ->assertSee('Aún no hay estudiantes registrados');
    });

    it('un estudiante sin el permiso consultar_estudiantes recibe 403', function () {
        $estudiante = hu015Estudiante('Ana Torres', $this->programa);

        $this->actingAs($estudiante)
            ->get(route('panel.estudiantes.index'))
            ->assertForbidden();
    });

});
