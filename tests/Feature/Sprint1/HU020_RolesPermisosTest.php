<?php

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Spatie\Permission\Models\Role;

describe('HU-020 — Roles y permisos', function () {

    beforeEach(function () {
        $this->seed(RolesPermisosSeeder::class);
    });

    it('existen los tres roles requeridos', function () {
        expect(Role::where('name', 'estudiante')->exists())->toBeTrue()
            ->and(Role::where('name', 'profesional_salud')->exists())->toBeTrue()
            ->and(Role::where('name', 'admin')->exists())->toBeTrue();
    });

    it('un estudiante no puede acceder al panel de profesionales', function () {
        $user = User::factory()->create();
        $user->assignRole('estudiante');

        // El consentimiento permite que el middleware role llegue a evaluar
        \App\Modules\Auth\Models\Consentimiento::create([
            'user_id'          => $user->id,
            'version_politica' => '1.0',
            'aceptado_at'      => now(),
            'ip'               => '127.0.0.1',
        ]);

        $this->actingAs($user)
            ->get(route('panel.inicio'))
            ->assertForbidden();
    });

    it('un estudiante no puede acceder al área de administración', function () {
        $user = User::factory()->create();
        $user->assignRole('estudiante');

        \App\Modules\Auth\Models\Consentimiento::create([
            'user_id'          => $user->id,
            'version_politica' => '1.0',
            'aceptado_at'      => now(),
            'ip'               => '127.0.0.1',
        ]);

        $this->actingAs($user)
            ->get(route('admin.inicio'))
            ->assertForbidden();
    });

    it('un profesional de salud puede acceder al panel', function () {
        $user = User::factory()->create();
        $user->assignRole('profesional_salud');

        // El middleware consentimiento debe dejarlo pasar — simulamos que ya aceptó
        \App\Modules\Auth\Models\Consentimiento::create([
            'user_id'          => $user->id,
            'version_politica' => '1.0',
            'aceptado_at'      => now(),
            'ip'               => '127.0.0.1',
        ]);

        $this->actingAs($user)
            ->get(route('panel.inicio'))
            ->assertOk();
    });

    it('un admin puede acceder a su área', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        \App\Modules\Auth\Models\Consentimiento::create([
            'user_id'          => $user->id,
            'version_politica' => '1.0',
            'aceptado_at'      => now(),
            'ip'               => '127.0.0.1',
        ]);

        $this->actingAs($user)
            ->get(route('admin.inicio'))
            ->assertOk();
    });

});
