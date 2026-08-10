<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Usuarios\Models\Perfil;
use App\Modules\Usuarios\Models\Programa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosTestSeeder extends Seeder
{
    public function run(): void
    {
        $ingenieria = Programa::where('nombre', 'Ingeniería de Sistemas')->first();
        $enfermeria = Programa::where('nombre', 'Enfermería')->first();
        $nutricion = Programa::where('nombre', 'Nutrición y Dietética')->first();

        $usuarios = [
            [
                'user' => [
                    'name' => 'Estudiante Demo',
                    'email' => 'estudiante@umariana.edu.co',
                    'codigo_participante' => 'EST-DEMO',
                    'password' => Hash::make('Password123!'),
                ],
                'perfil' => [
                    'genero' => 'femenino',
                    'edad' => 21,
                    'programa_id' => $ingenieria?->id,
                    'semestre' => 5,
                ],
                'rol' => 'estudiante',
            ],
            [
                'user' => [
                    'name' => 'Profesional Demo',
                    'email' => 'profesional@umariana.edu.co',
                    'codigo_participante' => 'PRO-DEMO',
                    'password' => Hash::make('Password123!'),
                ],
                'perfil' => [
                    'genero' => 'masculino',
                    'edad' => 35,
                    'programa_id' => $enfermeria?->id,
                    'semestre' => null,
                ],
                'rol' => 'profesional_salud',
            ],
            [
                'user' => [
                    'name' => 'Admin Demo',
                    'email' => 'admin@umariana.edu.co',
                    'codigo_participante' => 'ADM-DEMO',
                    'password' => Hash::make('Password123!'),
                ],
                'perfil' => [
                    'genero' => 'otro',
                    'edad' => 40,
                    'programa_id' => $nutricion?->id,
                    'semestre' => null,
                ],
                'rol' => 'admin',
            ],
        ];

        foreach ($usuarios as $datos) {
            $user = User::firstOrCreate(
                ['email' => $datos['user']['email']],
                $datos['user']
            );

            if (! $user->hasRole($datos['rol'])) {
                $user->assignRole($datos['rol']);
            }

            if (! $user->perfil) {
                Perfil::create(array_merge(['user_id' => $user->id], $datos['perfil']));
            }

            if (! Consentimiento::where('user_id', $user->id)->exists()) {
                Consentimiento::create([
                    'user_id' => $user->id,
                    'version_politica' => '1.0',
                    'aceptado_at' => now(),
                    'ip' => '127.0.0.1',
                ]);
            }
        }
    }
}
