<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use Database\Seeders\RolesPermisosSeeder;

describe('Ley 1581 — Consentimiento informado', function () {

    beforeEach(function () {
        $this->seed(RolesPermisosSeeder::class);
    });

    it('un usuario sin consentimiento es redirigido a /consentimiento al acceder a su área', function () {
        $user = User::factory()->create();
        $user->assignRole('estudiante');

        $this->actingAs($user)
            ->get(route('estudiante.inicio'))
            ->assertRedirect(route('consentimiento.show'));
    });

    it('la pantalla de consentimiento devuelve 200 para usuario autenticado', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('consentimiento.show'))
            ->assertOk();
    });

    it('al aceptar el consentimiento se registra en la base de datos con versión, timestamp e IP', function () {
        $user = User::factory()->create();
        $user->assignRole('estudiante');

        $this->actingAs($user)
            ->post(route('consentimiento.store'), ['acepto' => '1']);

        $consentimiento = Consentimiento::where('user_id', $user->id)->first();

        expect($consentimiento)->not->toBeNull()
            ->and($consentimiento->version_politica)->toBe('1.0')
            ->and($consentimiento->aceptado_at)->not->toBeNull()
            ->and($consentimiento->ip)->not->toBeNull();
    });

    it('al aceptar el consentimiento el usuario accede a su área correctamente', function () {
        $user = User::factory()->create();
        $user->assignRole('estudiante');

        $this->actingAs($user)
            ->post(route('consentimiento.store'), ['acepto' => '1'])
            ->assertRedirect(route('estudiante.inicio'));
    });

    it('rechazar el consentimiento (sin marcar el checkbox) devuelve error de validación', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('consentimiento.store'), [])
            ->assertSessionHasErrors('acepto');
    });

    it('un usuario con consentimiento ya aceptado pasa el middleware sin redirección', function () {
        $user = User::factory()->create();
        $user->assignRole('estudiante');

        Consentimiento::create([
            'user_id' => $user->id,
            'version_politica' => '1.0',
            'aceptado_at' => now(),
            'ip' => '127.0.0.1',
        ]);

        $this->actingAs($user)
            ->get(route('estudiante.inicio'))
            ->assertOk();
    });

});
