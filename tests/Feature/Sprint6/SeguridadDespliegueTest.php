<?php

use App\Models\User;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Database\Seeders\VersionModeloSeeder;

describe('Sprint 6 — Cabeceras de seguridad HTTP', function () {

    it('toda respuesta web incluye las cabeceras de seguridad', function () {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'");
    });

    it('envía HSTS solo cuando la conexión es HTTPS', function () {
        $this->get(route('login'))->assertHeaderMissing('Strict-Transport-Security');

        $this->get(str_replace('http://', 'https://', route('login')))
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    });

});

describe('Sprint 6 — Creación de cuentas institucionales por consola', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class]);
    });

    it('crea un administrador con la contraseña cifrada', function () {
        $this->artisan('gutguardian:crear-usuario', ['rol' => 'admin'])
            ->expectsQuestion('Nombre completo', 'Coordinación GutGuardián')
            ->expectsQuestion('Correo institucional', 'Admin@Umariana.edu.co')
            ->expectsQuestion('Código interno (único)', 'ADM-001')
            ->expectsQuestion('Contraseña', 'ClaveSegura#2026')
            ->expectsQuestion('Confirma la contraseña', 'ClaveSegura#2026')
            ->assertSuccessful();

        $admin = User::where('email', 'admin@umariana.edu.co')->firstOrFail();

        expect($admin->hasRole('admin'))->toBeTrue()
            ->and($admin->password)->not->toBe('ClaveSegura#2026');
    });

    it('rechaza contraseñas que no coinciden', function () {
        $this->artisan('gutguardian:crear-usuario', ['rol' => 'profesional_salud'])
            ->expectsQuestion('Nombre completo', 'Profesional')
            ->expectsQuestion('Correo institucional', 'pro@umariana.edu.co')
            ->expectsQuestion('Código interno (único)', 'PRO-001')
            ->expectsQuestion('Contraseña', 'ClaveSegura#2026')
            ->expectsQuestion('Confirma la contraseña', 'OtraClave#2026')
            ->assertFailed();

        expect(User::where('email', 'pro@umariana.edu.co')->exists())->toBeFalse();
    });

    it('no crea estudiantes: esos se registran desde la aplicación', function () {
        $this->artisan('gutguardian:crear-usuario', ['rol' => 'estudiante'])->assertExitCode(2);
    });

});

describe('Sprint 6 — Verificación previa al despliegue', function () {

    it('falla cuando no hay instrumento, roles ni modelo activo', function () {
        $this->artisan('gutguardian:verificar-despliegue')
            ->expectsOutputToContain('Versión activa del modelo')
            ->assertFailed();
    });

    it('pasa sin errores con los datos mínimos sembrados en entorno de pruebas', function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class, VersionModeloSeeder::class]);
        User::factory()->create()->assignRole('admin');

        // El modelo sembrado es el provisional (datos sintéticos): se reporta como aviso, no como error.
        $this->artisan('gutguardian:verificar-despliegue')
            ->expectsOutputToContain('Modelo entrenado con datos reales')
            ->assertSuccessful();
    });

    it('en producción exige modo depuración apagado y URL con HTTPS', function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class, VersionModeloSeeder::class]);
        app()['env'] = 'production';
        config(['app.debug' => true, 'app.url' => 'http://gutguardian.test']);

        $this->artisan('gutguardian:verificar-despliegue')->assertFailed();
    });

});
