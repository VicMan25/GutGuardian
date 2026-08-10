<?php

use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

describe('Sprint 0 — entorno base', function () {

    // -------------------------------------------------------
    // Migraciones
    // -------------------------------------------------------
    describe('migraciones', function () {

        it('crea todas las tablas del modelo de datos', function () {
            $tablas = [
                // Core Laravel
                'users', 'sessions', 'cache', 'jobs',
                // GutGuardián
                'programas', 'perfiles',
                'instrumentos', 'secciones', 'preguntas',
                'items_pregunta', 'opciones',
                'diligenciamientos', 'respuestas',
                'versiones_modelo', 'evaluaciones_riesgo',
                'alertas', 'consentimientos',
                // Spatie
                'roles', 'permissions',
                'model_has_roles', 'model_has_permissions', 'role_has_permissions',
                'activity_log',
            ];

            foreach ($tablas as $tabla) {
                expect(Schema::hasTable($tabla))
                    ->toBeTrue("La tabla '{$tabla}' no existe en la base de datos");
            }
        });

        it('la tabla users tiene los campos propios del proyecto', function () {
            expect(Schema::hasColumns('users', ['codigo_participante', 'activo', 'deleted_at']))
                ->toBeTrue();
        });

        it('la tabla versiones_modelo tiene los campos JSON requeridos', function () {
            expect(Schema::hasColumns('versiones_modelo', ['coeficientes', 'metricas', 'mapa_variables']))
                ->toBeTrue();
        });
    });

    // -------------------------------------------------------
    // Roles y permisos
    // -------------------------------------------------------
    describe('roles y permisos', function () {

        beforeEach(function () {
            $this->seed(RolesPermisosSeeder::class);
        });

        it('existen exactamente 3 roles', function () {
            expect(Role::count())->toBe(3);
        });

        it('el rol estudiante existe', function () {
            expect(Role::where('name', 'estudiante')->exists())->toBeTrue();
        });

        it('el rol profesional_salud existe', function () {
            expect(Role::where('name', 'profesional_salud')->exists())->toBeTrue();
        });

        it('el rol admin existe', function () {
            expect(Role::where('name', 'admin')->exists())->toBeTrue();
        });

        it('el rol estudiante tiene exactamente 4 permisos', function () {
            expect(Role::findByName('estudiante')->permissions)->toHaveCount(4);
        });

        it('el rol profesional_salud tiene exactamente 8 permisos', function () {
            expect(Role::findByName('profesional_salud')->permissions)->toHaveCount(8);
        });

        it('el rol admin tiene todos los permisos del sistema', function () {
            $admin = Role::findByName('admin');
            expect($admin->permissions->count())->toBe(Permission::count());
        });

        it('el seeder es idempotente al ejecutarse dos veces', function () {
            $this->seed(RolesPermisosSeeder::class);

            expect(Role::count())->toBe(3);
            expect(Permission::count())->toBe(12);
        });
    });
});
