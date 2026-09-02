<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function hu019Consentimiento(User $user): void
{
    Consentimiento::create([
        'user_id' => $user->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);
}

function hu019Profesional(): User
{
    $p = User::factory()->create();
    $p->assignRole('profesional_salud');
    hu019Consentimiento($p);

    return $p;
}

describe('HU-019 — Desactivación de cuenta de usuario', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
    });

    it('desactiva la cuenta del estudiante sin eliminar su historial', function () {
        $profesional = hu019Profesional();
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        hu019Consentimiento($estudiante);

        $this->actingAs($profesional)
            ->post(route('panel.usuarios.alternarActivo', $estudiante))
            ->assertRedirect();

        expect($estudiante->fresh()->activo)->toBeFalse()
            ->and(User::find($estudiante->id))->not->toBeNull();
    });

    it('una cuenta desactivada no puede iniciar sesión', function () {
        $estudiante = User::factory()->create(['activo' => false]);
        $estudiante->assignRole('estudiante');

        $this->post(route('login'), [
            'email' => $estudiante->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('reactivar la cuenta permite iniciar sesión de nuevo', function () {
        $profesional = hu019Profesional();
        $estudiante = User::factory()->create(['activo' => false]);
        $estudiante->assignRole('estudiante');

        $this->actingAs($profesional)->post(route('panel.usuarios.alternarActivo', $estudiante));

        expect($estudiante->fresh()->activo)->toBeTrue();

        // Cierra la sesión del profesional para probar el login del estudiante limpio.
        $this->post(route('logout'));

        $this->post(route('login'), [
            'email' => $estudiante->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($estudiante);
    });

    it('un estudiante sin el permiso desactivar_usuarios recibe 403', function () {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        hu019Consentimiento($estudiante);

        $this->actingAs($estudiante)
            ->post(route('panel.usuarios.alternarActivo', $estudiante))
            ->assertForbidden();
    });

});
