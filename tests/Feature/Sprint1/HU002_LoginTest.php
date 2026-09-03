<?php

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Support\Facades\Hash;

describe('HU-002 — Inicio de sesión', function () {

    beforeEach(function () {
        $this->seed(RolesPermisosSeeder::class);
    });

    it('un estudiante con credenciales válidas accede a su área', function () {
        $user = User::factory()->create([
            'email' => 'estudiante@umariana.edu.co',
            'password' => Hash::make('Password123!'),
        ]);
        $user->assignRole('estudiante');

        $this->post(route('login'), [
            'email' => 'estudiante@umariana.edu.co',
            'password' => 'Password123!',
        ])->assertRedirect(route('estudiante.inicio'));
    });

    it('un profesional de salud con credenciales válidas accede al panel', function () {
        $user = User::factory()->create([
            'email' => 'prof@umariana.edu.co',
            'password' => Hash::make('Password123!'),
        ]);
        $user->assignRole('profesional_salud');

        $this->post(route('login'), [
            'email' => 'prof@umariana.edu.co',
            'password' => 'Password123!',
        ])->assertRedirect(route('panel.inicio'));
    });

    it('credenciales incorrectas devuelven error de validación', function () {
        User::factory()->create([
            'email' => 'real@umariana.edu.co',
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('login'), [
            'email' => 'real@umariana.edu.co',
            'password' => 'ContraseñaWrong',
        ])->assertSessionHasErrors('email');
    });

    it('la ruta /login devuelve 200', function () {
        $this->get(route('login'))->assertOk();
    });

});
