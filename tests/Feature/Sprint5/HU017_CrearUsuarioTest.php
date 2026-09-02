<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Usuarios\Models\Programa;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\ProgramasSeeder;
use Database\Seeders\RolesPermisosSeeder;

function hu017Profesional(): User
{
    $p = User::factory()->create();
    $p->assignRole('profesional_salud');
    Consentimiento::create([
        'user_id' => $p->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);

    return $p;
}

describe('HU-017 — Creación de usuarios estudiantes', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, ProgramasSeeder::class, InstrumentoSeeder::class]);
        $this->programa = Programa::where('nombre', 'Enfermería')->firstOrFail();
    });

    it('el profesional de salud crea un nuevo estudiante con su perfil', function () {
        $profesional = hu017Profesional();

        $respuesta = $this->actingAs($profesional)->post(route('panel.usuarios.store'), [
            'name' => 'Nuevo Estudiante',
            'email' => 'nuevo.estudiante@umariana.edu.co',
            'codigo_participante' => 'EST-2026-001',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'genero' => 'femenino',
            'edad' => 19,
            'programa_id' => $this->programa->id,
            'semestre' => 1,
        ]);

        $creado = User::where('email', 'nuevo.estudiante@umariana.edu.co')->first();

        expect($creado)->not->toBeNull()
            ->and($creado->hasRole('estudiante'))->toBeTrue()
            ->and($creado->perfil->programa_id)->toBe($this->programa->id);

        $respuesta->assertRedirect(route('panel.estudiantes.show', $creado));
    });

    it('rechaza el correo o código duplicado', function () {
        $profesional = hu017Profesional();
        User::factory()->create(['email' => 'ya.existe@umariana.edu.co', 'codigo_participante' => 'EST-DUP']);

        $this->actingAs($profesional)->post(route('panel.usuarios.store'), [
            'name' => 'X', 'email' => 'ya.existe@umariana.edu.co', 'codigo_participante' => 'EST-DUP',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'genero' => 'otro', 'edad' => 20, 'programa_id' => $this->programa->id, 'semestre' => 2,
        ])->assertSessionHasErrors(['email', 'codigo_participante']);
    });

    it('un estudiante sin el permiso crear_usuarios recibe 403', function () {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $estudiante->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->actingAs($estudiante)
            ->get(route('panel.usuarios.create'))
            ->assertForbidden();
    });

});
