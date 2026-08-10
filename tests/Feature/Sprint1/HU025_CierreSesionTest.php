<?php

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;

describe('HU-025 — Cierre de sesión', function () {

    beforeEach(function () {
        $this->seed(RolesPermisosSeeder::class);
    });

    it('POST /logout invalida la sesión y redirige al login', function () {
        $user = User::factory()->create();
        $user->assignRole('estudiante');

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    });

    it('después de cerrar sesión el área protegida redirige al login', function () {
        $user = User::factory()->create();
        $user->assignRole('estudiante');

        $this->actingAs($user)->post(route('logout'));

        $this->get(route('estudiante.inicio'))
            ->assertRedirect(route('login'));
    });

});
