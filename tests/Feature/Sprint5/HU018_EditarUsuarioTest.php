<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Usuarios\Models\Perfil;
use App\Modules\Usuarios\Models\Programa;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\ProgramasSeeder;
use Database\Seeders\RolesPermisosSeeder;

function hu018Consentimiento(User $user): void
{
    Consentimiento::create([
        'user_id' => $user->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);
}

function hu018Profesional(): User
{
    $p = User::factory()->create();
    $p->assignRole('profesional_salud');
    hu018Consentimiento($p);

    return $p;
}

function hu018Estudiante(Programa $programa): User
{
    $e = User::factory()->create(['codigo_participante' => 'EST-ORIGINAL']);
    $e->assignRole('estudiante');
    hu018Consentimiento($e);
    Perfil::create(['user_id' => $e->id, 'genero' => 'masculino', 'edad' => 20, 'programa_id' => $programa->id, 'semestre' => 2]);

    return $e;
}

describe('HU-018 — Edición de información de usuario', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, ProgramasSeeder::class, InstrumentoSeeder::class]);
        $this->programa = Programa::where('nombre', 'Enfermería')->firstOrFail();
        $this->programaNuevo = Programa::where('nombre', 'Nutrición y Dietética')->firstOrFail();
    });

    it('el profesional de salud corrige los datos de un estudiante', function () {
        $profesional = hu018Profesional();
        $estudiante = hu018Estudiante($this->programa);

        $this->actingAs($profesional)->put(route('panel.usuarios.update', $estudiante), [
            'name' => 'Nombre Corregido',
            'email' => $estudiante->email,
            'codigo_participante' => 'EST-CORREGIDO',
            'genero' => 'masculino', 'edad' => 21,
            'programa_id' => $this->programaNuevo->id, 'semestre' => 3,
        ])->assertRedirect(route('panel.estudiantes.show', $estudiante));

        $estudiante->refresh();
        expect($estudiante->name)->toBe('Nombre Corregido')
            ->and($estudiante->codigo_participante)->toBe('EST-CORREGIDO')
            ->and($estudiante->perfil->programa_id)->toBe($this->programaNuevo->id);
    });

    it('no permite editar la ficha de un usuario que no es estudiante — 404', function () {
        $profesional = hu018Profesional();
        $otroProfesional = hu018Profesional();

        $this->actingAs($profesional)
            ->get(route('panel.usuarios.edit', $otroProfesional))
            ->assertNotFound();
    });

    it('un estudiante sin el permiso editar_usuarios recibe 403', function () {
        $estudiante = hu018Estudiante($this->programa);

        $this->actingAs($estudiante)
            ->get(route('panel.usuarios.edit', $estudiante))
            ->assertForbidden();
    });

});
