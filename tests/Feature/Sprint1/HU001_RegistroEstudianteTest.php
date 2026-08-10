<?php

use App\Models\User;
use App\Modules\Usuarios\Models\Programa;
use Database\Seeders\ProgramasSeeder;
use Database\Seeders\RolesPermisosSeeder;

describe('HU-001 — Registro de estudiante', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, ProgramasSeeder::class]);
    });

    it('un estudiante puede registrarse con todos los campos requeridos', function () {
        $programa = Programa::first();

        $this->post(route('register'), [
            'name'                  => 'Ana Torres',
            'email'                 => 'ana@umariana.edu.co',
            'codigo_participante'   => 'EST-001',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'genero'                => 'femenino',
            'edad'                  => 21,
            'programa_id'           => $programa->id,
            'semestre'              => 4,
        ])
        ->assertRedirect(route('estudiante.inicio'));

        $user = User::where('email', 'ana@umariana.edu.co')->first();

        expect($user)->not->toBeNull()
            ->and($user->codigo_participante)->toBe('EST-001')
            ->and($user->hasRole('estudiante'))->toBeTrue();
    });

    it('crea el perfil sociodemográfico al registrarse', function () {
        $programa = Programa::first();

        $this->post(route('register'), [
            'name'                  => 'Luis Pérez',
            'email'                 => 'luis@umariana.edu.co',
            'codigo_participante'   => 'EST-002',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'genero'                => 'masculino',
            'edad'                  => 23,
            'programa_id'           => $programa->id,
            'semestre'              => 6,
        ]);

        $user = User::where('email', 'luis@umariana.edu.co')->first();

        expect($user->perfil)->not->toBeNull()
            ->and($user->perfil->genero)->toBe('masculino')
            ->and($user->perfil->edad)->toBe(23)
            ->and($user->perfil->semestre)->toBe(6);
    });

    it('el código de participante debe ser único', function () {
        $programa = Programa::first();

        // Crear el primer usuario directamente para evitar estado de sesión
        User::factory()->create(['codigo_participante' => 'EST-DUP']);

        $this->post(route('register'), [
            'name'                  => 'Estudiante Dos',
            'email'                 => 'dos@umariana.edu.co',
            'codigo_participante'   => 'EST-DUP',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'genero'                => 'femenino',
            'edad'                  => 20,
            'programa_id'           => $programa->id,
            'semestre'              => 2,
        ])->assertSessionHasErrors('codigo_participante');
    });

    it('el registro falla sin código de participante', function () {
        $programa = Programa::first();

        $this->post(route('register'), [
            'name'                  => 'Sin Código',
            'email'                 => 'sn@umariana.edu.co',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'genero'                => 'otro',
            'edad'                  => 19,
            'programa_id'           => $programa->id,
            'semestre'              => 1,
        ])->assertSessionHasErrors('codigo_participante');
    });

});
