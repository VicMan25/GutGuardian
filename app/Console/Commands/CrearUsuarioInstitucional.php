<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Crea cuentas de profesional de salud o de administrador en el despliegue
 * piloto. El registro público solo crea estudiantes (HU-001) y el panel solo
 * gestiona estudiantes (HU-017), así que sin este comando el primer
 * administrador tendría que insertarse a mano en la base de datos.
 * La contraseña se pide sin eco y nunca se recibe como argumento, para que
 * no quede en el historial de la terminal.
 */
class CrearUsuarioInstitucional extends Command
{
    protected $signature = 'gutguardian:crear-usuario {rol : profesional_salud o admin}';

    protected $description = 'Crea una cuenta de profesional de salud o de administrador';

    public function handle(): int
    {
        $rol = $this->argument('rol');

        if (! in_array($rol, ['profesional_salud', 'admin'], true)) {
            $this->error('El rol debe ser profesional_salud o admin. Los estudiantes se registran desde la aplicación.');

            return self::INVALID;
        }

        $datos = [
            'name' => $this->ask('Nombre completo'),
            'email' => mb_strtolower((string) $this->ask('Correo institucional')),
            'codigo_participante' => $this->ask('Código interno (único)'),
            'password' => $this->secret('Contraseña'),
        ];
        $datos['password_confirmation'] = $this->secret('Confirma la contraseña');

        $validador = Validator::make($datos, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'codigo_participante' => ['required', 'string', 'max:50', 'unique:users,codigo_participante'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $mensaje) {
                $this->error($mensaje);
            }

            return self::FAILURE;
        }

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'codigo_participante' => $datos['codigo_participante'],
            'password' => Hash::make($datos['password']),
        ]);
        $usuario->assignRole($rol);

        $this->info("Cuenta creada con rol {$rol}: {$usuario->email}. Deberá aceptar el consentimiento en su primer ingreso.");

        return self::SUCCESS;
    }
}
