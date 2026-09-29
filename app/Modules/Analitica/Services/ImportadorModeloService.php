<?php

namespace App\Modules\Analitica\Services;

use App\Models\User;
use App\Modules\Analitica\Models\VersionModelo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * HU-026: registro y activación de versiones del modelo predictivo.
 *
 * Recibe el artefacto JSON que produce ml/exportar_modelo.py, lo normaliza a
 * la forma de la tabla versiones_modelo y valida que sea utilizable por
 * PredictorService antes de guardarlo o activarlo. Una versión con
 * coeficientes incompletos, categorías mal definidas o variables que la
 * aplicación no sabe calcular nunca llega a quedar activa: así el sistema
 * no se queda sin una versión válida (criterio 6 de HU-026).
 *
 * Es la única vía de carga de versiones: VersionModeloSeeder también pasa por
 * aquí, de modo que la validación es la misma en el sembrado y en la pantalla
 * de administración.
 */
class ImportadorModeloService
{
    /** Log de spatie/laravel-activitylog para cambios del modelo predictivo. */
    public const LOG = 'modelo_predictivo';

    public function __construct(private PredictorService $predictor) {}

    /**
     * Normaliza el JSON exportado por ml/ a los atributos de VersionModelo.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     *
     * @throws ValidationException si falta alguna clave o el contenido no es utilizable.
     */
    public function normalizar(array $datos): array
    {
        $obligatorias = ['nombre', 'version', 'entrenado_at', 'categoria_base', 'categorias',
            'orden_variables', 'mapa_variables', 'coeficientes', 'metricas'];
        $faltantes = array_values(array_diff($obligatorias, array_keys($datos)));

        if ($faltantes !== []) {
            throw ValidationException::withMessages([
                'archivo' => 'Al archivo le faltan las claves: '.implode(', ', $faltantes).'.',
            ]);
        }

        $atributos = [
            'nombre' => $datos['nombre'],
            'version' => $datos['version'],
            'entrenado_at' => $datos['entrenado_at'],
            'coeficientes' => $datos['coeficientes'],
            'metricas' => $datos['metricas'],
            // mapa_variables agrupa lo que PredictorService/ExplicabilidadService
            // necesitan además de los coeficientes: orden fijo de variables,
            // categoría base del MNLogit, etiquetas en lenguaje llano y
            // limitaciones a mostrar.
            'mapa_variables' => [
                'orden_variables' => $datos['orden_variables'],
                'categoria_base' => $datos['categoria_base'],
                'categorias' => $datos['categorias'],
                'variables' => $datos['mapa_variables'],
                'limitaciones' => $datos['limitaciones'] ?? null,
            ],
        ];

        $errores = $this->errores($atributos);
        if ($errores !== []) {
            throw ValidationException::withMessages(['archivo' => $errores]);
        }

        return $atributos;
    }

    /**
     * Revisa que unos atributos de versión sean utilizables por PredictorService.
     *
     * @param  array<string, mixed>  $atributos  forma de VersionModelo (ver normalizar()).
     * @return list<string> mensajes de error; vacío si la versión es válida.
     */
    public function errores(array $atributos): array
    {
        $errores = [];

        if (! is_string($atributos['nombre'] ?? null) || trim($atributos['nombre']) === '') {
            $errores[] = 'El nombre del modelo es obligatorio.';
        }
        if (! is_string($atributos['version'] ?? null) || ! preg_match('/^[0-9A-Za-z._-]{1,20}$/', $atributos['version'])) {
            $errores[] = 'La versión debe ser un texto corto (letras, números, punto o guion).';
        }

        try {
            CarbonImmutable::parse($atributos['entrenado_at'] ?? '');
        } catch (Throwable) {
            $errores[] = 'La fecha de entrenamiento no es válida.';
        }

        $mapa = $atributos['mapa_variables'] ?? [];
        $categorias = $mapa['categorias'] ?? null;
        $base = $mapa['categoria_base'] ?? null;

        if (! is_array($categorias) || array_values($categorias) !== [0, 1, 2]) {
            $errores[] = 'El modelo debe declarar exactamente las categorías 0, 1 y 2.';

            return $errores;
        }
        if (! in_array($base, $categorias, true)) {
            $errores[] = 'La categoría base debe ser una de las categorías del modelo.';

            return $errores;
        }

        $orden = $mapa['orden_variables'] ?? null;
        if (! is_array($orden) || $orden === [] || count($orden) !== count(array_unique($orden))) {
            $errores[] = 'El orden de variables debe ser una lista no vacía y sin repetidos.';

            return $errores;
        }

        $desconocidas = array_diff($orden, $this->predictor->variablesSoportadas());
        if ($desconocidas !== []) {
            $errores[] = 'La aplicación no sabe calcular estas variables: '.implode(', ', $desconocidas).'.';
        }

        if (! is_array($mapa['variables'] ?? null)) {
            $errores[] = 'Falta el mapa de etiquetas de las variables.';
        }

        $coeficientes = $atributos['coeficientes'] ?? null;
        foreach ($categorias as $categoria) {
            if ($categoria === $base) {
                continue;
            }

            $coef = is_array($coeficientes) ? ($coeficientes[(string) $categoria] ?? null) : null;
            if (! is_array($coef) || ! $this->esFinito($coef['intercepto'] ?? null) || ! is_array($coef['beta'] ?? null)) {
                $errores[] = "Faltan el intercepto o los coeficientes de la categoría {$categoria}.";

                continue;
            }

            $sinCoeficiente = array_diff($orden, array_keys($coef['beta']));
            if ($sinCoeficiente !== []) {
                $errores[] = "La categoría {$categoria} no trae coeficiente para: ".implode(', ', $sinCoeficiente).'.';
            }

            foreach ($coef['beta'] as $variable => $beta) {
                if (! $this->esFinito($beta)) {
                    $errores[] = "El coeficiente de «{$variable}» en la categoría {$categoria} no es numérico.";
                }
            }
        }

        if (! is_array($atributos['metricas'] ?? null)) {
            $errores[] = 'Faltan las métricas de desempeño del modelo.';
        }

        return $errores;
    }

    /**
     * Registra una versión nueva. Queda inactiva salvo que se pida activarla,
     * para que cargar un modelo nunca cambie por sí solo las evaluaciones nuevas.
     *
     * @param  array<string, mixed>  $datos  JSON exportado por ml/exportar_modelo.py
     */
    public function importar(array $datos, ?User $actor = null, bool $activar = false): VersionModelo
    {
        $atributos = $this->normalizar($datos);

        if (VersionModelo::where('version', $atributos['version'])->exists()) {
            throw ValidationException::withMessages([
                'archivo' => "Ya existe una versión {$atributos['version']} registrada.",
            ]);
        }

        $version = VersionModelo::create($atributos + ['activo' => false]);

        activity(self::LOG)
            ->causedBy($actor)
            ->performedOn($version)
            ->event('registro_version')
            ->withProperties(['version' => $version->version])
            ->log('Registró una nueva versión del modelo predictivo');

        if ($activar) {
            $this->activar($version, $actor);
        }

        return $version->fresh();
    }

    /**
     * Deja esta versión como la única activa. Revalida lo almacenado antes de
     * tocar la versión vigente: si falla, la versión anterior sigue activa.
     */
    public function activar(VersionModelo $version, ?User $actor = null): void
    {
        $errores = $this->errores($version->only(['nombre', 'version', 'entrenado_at', 'coeficientes', 'metricas', 'mapa_variables']));
        if ($errores !== []) {
            throw ValidationException::withMessages(['version' => $errores]);
        }

        $anterior = VersionModelo::where('activo', true)->value('version');

        DB::transaction(function () use ($version) {
            VersionModelo::where('activo', true)->whereKeyNot($version->id)->update(['activo' => false]);
            $version->update(['activo' => true]);
        });

        activity(self::LOG)
            ->causedBy($actor)
            ->performedOn($version)
            ->event('activacion_version')
            ->withProperties(['version' => $version->version, 'version_anterior' => $anterior])
            ->log('Activó una versión del modelo predictivo');
    }

    private function esFinito(mixed $valor): bool
    {
        return (is_int($valor) || is_float($valor)) && is_finite((float) $valor);
    }
}
