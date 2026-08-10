<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermisosSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de permisos antes de sembrar
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            // Encuestas
            'diligenciar_encuesta',

            // Resultados propios (estudiante)
            'ver_historial_propio',
            'ver_resultado_riesgo_propio',
            'ver_graficas_propias',

            // Gestión de estudiantes (profesional_salud)
            'consultar_estudiantes',
            'ver_niveles_riesgo',

            // Reportes (profesional_salud)
            'generar_reportes',
            'exportar_reportes',

            // Usuarios (profesional_salud)
            'crear_usuarios',
            'editar_usuarios',
            'desactivar_usuarios',

            // Modelo predictivo (admin)
            'gestionar_versiones_modelo',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        // --- Rol: estudiante ---
        $estudiante = Role::firstOrCreate(['name' => 'estudiante', 'guard_name' => 'web']);
        $estudiante->syncPermissions([
            'diligenciar_encuesta',
            'ver_historial_propio',
            'ver_resultado_riesgo_propio',
            'ver_graficas_propias',
        ]);

        // --- Rol: profesional_salud ---
        $profesional = Role::firstOrCreate(['name' => 'profesional_salud', 'guard_name' => 'web']);
        $profesional->syncPermissions([
            'diligenciar_encuesta',
            'consultar_estudiantes',
            'ver_niveles_riesgo',
            'generar_reportes',
            'exportar_reportes',
            'crear_usuarios',
            'editar_usuarios',
            'desactivar_usuarios',
        ]);

        // --- Rol: admin ---
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());
    }
}
