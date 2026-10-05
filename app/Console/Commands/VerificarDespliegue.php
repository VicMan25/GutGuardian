<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Analitica\Services\ImportadorModeloService;
use App\Modules\Encuestas\Models\Instrumento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Lista de chequeo previa al despliegue piloto (Sprint 6). Revisa la
 * configuración de seguridad exigida por la Ley 1273 de 2009 y la Ley 1581
 * de 2012 (HTTPS, modo depuración apagado, sesión cifrada) y que el sistema
 * tenga lo mínimo para operar: instrumento, roles, administrador y una
 * versión válida del modelo. Termina con código 1 si hay errores.
 */
class VerificarDespliegue extends Command
{
    protected $signature = 'gutguardian:verificar-despliegue';

    protected $description = 'Verifica la configuración y los datos mínimos antes del despliegue piloto';

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $resultados = [];

    public function handle(ImportadorModeloService $importador): int
    {
        $produccion = app()->environment('production');

        $this->revisar('Entorno de producción', $produccion, $produccion ? 'production' : 'APP_ENV='.app()->environment(), grave: false);
        $this->revisar('Clave de aplicación', filled(config('app.key')), 'APP_KEY');
        $this->revisar('Modo depuración apagado', ! config('app.debug'), 'APP_DEBUG debe ser false', grave: $produccion);
        $this->revisar('URL con HTTPS', Str::startsWith((string) config('app.url'), 'https://'), (string) config('app.url'), grave: $produccion);
        $this->revisar('Cookie de sesión solo por HTTPS', (bool) config('session.secure'), 'SESSION_SECURE_COOKIE=true', grave: false);
        $this->revisar('Sesión cifrada', (bool) config('session.encrypt'), 'SESSION_ENCRYPT=true', grave: false);

        try {
            DB::connection()->getPdo();
            $this->revisar('Conexión a la base de datos', true, DB::connection()->getDriverName());
        } catch (Throwable $e) {
            $this->revisar('Conexión a la base de datos', false, $e->getMessage());

            return $this->reportar();
        }

        $this->revisar('Instrumento de encuesta activo', Instrumento::where('activo', true)->exists(), 'php artisan db:seed --class=InstrumentoSeeder');

        $roles = Role::whereIn('name', ['estudiante', 'profesional_salud', 'admin'])->count();
        $this->revisar('Roles del sistema', $roles === 3, "{$roles} de 3 roles sembrados");
        // User::role() lanza excepción si el rol no existe: solo se consulta con los roles sembrados.
        $hayAdmin = $roles === 3 && User::role('admin')->exists();
        $this->revisar('Al menos un administrador', $hayAdmin, 'crear una cuenta con rol admin', grave: false);

        $version = VersionModelo::where('activo', true)->first();
        if (! $version) {
            $this->revisar('Versión activa del modelo', false, 'registrar y activar una versión en /admin/modelos');
        } else {
            $errores = $importador->errores($version->only(['nombre', 'version', 'entrenado_at', 'coeficientes', 'metricas', 'mapa_variables']));
            $this->revisar('Versión activa del modelo', $errores === [], $errores === [] ? 'v'.$version->version : implode(' ', $errores));

            $limitaciones = Str::lower((string) ($version->mapa_variables['limitaciones'] ?? ''));
            $provisional = Str::contains($limitaciones, ['sintético', 'sintetico', 'placeholder', 'provisional']);
            $this->revisar('Modelo entrenado con datos reales', ! $provisional, $provisional ? 'la versión activa declara datos sintéticos o regla provisional' : 'sin limitaciones de prueba', grave: false);
        }

        $this->revisar('Almacenamiento con permisos de escritura', is_writable(storage_path('app')) && is_writable(storage_path('logs')), 'storage/app y storage/logs');

        return $this->reportar();
    }

    private function revisar(string $chequeo, bool $ok, string $detalle, bool $grave = true): void
    {
        $this->resultados[] = [$chequeo, $ok ? 'OK' : ($grave ? 'ERROR' : 'AVISO'), $detalle];
    }

    private function reportar(): int
    {
        $this->table(['Chequeo', 'Estado', 'Detalle'], $this->resultados);

        $errores = collect($this->resultados)->where(1, 'ERROR')->count();
        $avisos = collect($this->resultados)->where(1, 'AVISO')->count();

        if ($errores > 0) {
            $this->error("{$errores} error(es) y {$avisos} aviso(s): corrige los errores antes de desplegar.");

            return self::FAILURE;
        }

        $this->info("Sin errores. {$avisos} aviso(s) por revisar.");

        return self::SUCCESS;
    }
}
