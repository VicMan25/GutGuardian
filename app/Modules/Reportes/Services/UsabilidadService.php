<?php

namespace App\Modules\Reportes\Services;

use App\Models\User;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\EvaluacionUsabilidad;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Indicadores de validación del aplicativo para la prueba piloto (Sprint 6,
 * objetivo 1.3.2.4 y §1.5.5.5 de la tesis):
 *
 *  - System Usability Scale (SUS, Brooke, 1996): 10 ítems Likert 1–5,
 *    puntaje 0–100. Ítems impares aportan (respuesta − 1); pares, (5 − respuesta);
 *    la suma se multiplica por 2,5.
 *  - Tiempo de diligenciamiento de la encuesta (creación → completado).
 *  - Tasa de errores al ingresar datos: proporción de envíos de sección
 *    rechazados por las reglas de validación.
 *
 * Los rangos de aceptabilidad siguen a Bangor, Kortum y Miller (2008):
 * < 50 no aceptable, 50–69,9 marginal, ≥ 70 aceptable.
 */
class UsabilidadService
{
    /** Log de spatie/laravel-activitylog para eventos de interacción. */
    public const LOG = 'usabilidad';

    /** Enunciados del SUS en español, en el orden original de Brooke (1996). */
    public const ITEMS = [
        1 => 'Creo que me gustaría usar GutGuardián con frecuencia.',
        2 => 'Encontré GutGuardián innecesariamente complejo.',
        3 => 'Pensé que GutGuardián era fácil de usar.',
        4 => 'Creo que necesitaría el apoyo de una persona con conocimientos técnicos para poder usar GutGuardián.',
        5 => 'Encontré que las diversas funciones de GutGuardián estaban bien integradas.',
        6 => 'Pensé que había demasiada inconsistencia en GutGuardián.',
        7 => 'Imagino que la mayoría de las personas aprendería a usar GutGuardián muy rápidamente.',
        8 => 'Encontré GutGuardián muy engorroso de usar.',
        9 => 'Me sentí con mucha confianza al usar GutGuardián.',
        10 => 'Necesité aprender muchas cosas antes de poder empezar a usar GutGuardián.',
    ];

    public const ESCALA = [
        1 => 'Totalmente en desacuerdo',
        2 => 'En desacuerdo',
        3 => 'Ni de acuerdo ni en desacuerdo',
        4 => 'De acuerdo',
        5 => 'Totalmente de acuerdo',
    ];

    /**
     * @param  array<int, int>  $respuestas  ítem (1–10) => valor (1–5)
     */
    public function puntaje(array $respuestas): float
    {
        $suma = 0;
        foreach (self::ITEMS as $item => $_) {
            $valor = (int) $respuestas[$item];
            $suma += $item % 2 === 1 ? $valor - 1 : 5 - $valor;
        }

        return $suma * 2.5;
    }

    public function aceptabilidad(float $puntaje): string
    {
        return match (true) {
            $puntaje >= 70 => 'Aceptable',
            $puntaje >= 50 => 'Marginal',
            default => 'No aceptable',
        };
    }

    /**
     * @param  array<int, int>  $respuestas
     */
    public function registrar(User $user, array $respuestas, ?string $comentario = null): EvaluacionUsabilidad
    {
        return EvaluacionUsabilidad::create([
            'user_id' => $user->id,
            'respuestas' => $respuestas,
            'puntaje' => $this->puntaje($respuestas),
            'comentario' => $comentario,
        ]);
    }

    /**
     * Deja constancia de un envío de sección de la encuesta y de si pasó la
     * validación. No guarda respuestas: solo el resultado del envío.
     */
    public function registrarEnvioSeccion(User $user, Diligenciamiento $diligenciamiento, int $orden, int $camposConError): void
    {
        activity(self::LOG)
            ->causedBy($user)
            ->performedOn($diligenciamiento)
            ->event('envio_seccion')
            ->withProperties([
                'seccion' => $orden,
                'valido' => $camposConError === 0,
                'campos_con_error' => $camposConError,
            ])
            ->log($camposConError === 0 ? 'Envió una sección válida' : 'Envió una sección con errores de validación');
    }

    /**
     * @return array{n: int, media: ?float, mediana: ?float, desviacion: ?float, minimo: ?float, maximo: ?float,
     *               aceptabilidad: array<string, int>, por_item: array<int, ?float>}
     */
    public function resumenSus(): array
    {
        $evaluaciones = EvaluacionUsabilidad::all();
        $puntajes = $evaluaciones->pluck('puntaje')->map(fn ($p) => (float) $p);

        $aceptabilidad = ['Aceptable' => 0, 'Marginal' => 0, 'No aceptable' => 0];
        foreach ($puntajes as $p) {
            $aceptabilidad[$this->aceptabilidad($p)]++;
        }

        $porItem = [];
        foreach (self::ITEMS as $item => $_) {
            $valores = $evaluaciones->map(fn ($e) => (int) ($e->respuestas[$item] ?? $e->respuestas[(string) $item] ?? 0))->filter();
            $porItem[$item] = $valores->isEmpty() ? null : round($valores->avg(), 2);
        }

        return [
            'n' => $puntajes->count(),
            'media' => $puntajes->isEmpty() ? null : round($puntajes->avg(), 1),
            'mediana' => $this->percentil($puntajes, 50),
            'desviacion' => $this->desviacion($puntajes),
            'minimo' => $puntajes->min(),
            'maximo' => $puntajes->max(),
            'aceptabilidad' => $aceptabilidad,
            'por_item' => $porItem,
        ];
    }

    /**
     * @return array{tiempo: array{n: int, mediana: ?float, p25: ?float, p75: ?float},
     *               errores: array{envios: int, con_error: int, tasa: ?float}}
     */
    public function metricasInteraccion(): array
    {
        $minutos = Diligenciamiento::where('estado', 'completado')
            ->whereNotNull('completado_at')
            ->get(['created_at', 'completado_at'])
            ->map(fn ($d) => $d->created_at->diffInSeconds($d->completado_at) / 60);

        $envios = Activity::inLog(self::LOG)->where('event', 'envio_seccion')->get(['properties']);
        $conError = $envios->filter(fn ($a) => ! $a->properties->get('valido'))->count();

        return [
            'tiempo' => [
                'n' => $minutos->count(),
                'mediana' => $this->percentil($minutos, 50),
                'p25' => $this->percentil($minutos, 25),
                'p75' => $this->percentil($minutos, 75),
            ],
            'errores' => [
                'envios' => $envios->count(),
                'con_error' => $conError,
                'tasa' => $envios->isEmpty() ? null : round($conError / $envios->count() * 100, 1),
            ],
        ];
    }

    /**
     * Percentil por interpolación lineal (método 7 de Hyndman y Fan, el de R y Excel).
     *
     * @param  Collection<int, float>  $valores
     */
    private function percentil(Collection $valores, float $p): ?float
    {
        if ($valores->isEmpty()) {
            return null;
        }

        $ordenados = $valores->sort()->values();
        $posicion = ($ordenados->count() - 1) * $p / 100;
        $inferior = (int) floor($posicion);
        $superior = (int) ceil($posicion);
        $valor = $ordenados[$inferior] + ($ordenados[$superior] - $ordenados[$inferior]) * ($posicion - $inferior);

        return round($valor, 1);
    }

    /**
     * Desviación estándar muestral (n − 1).
     *
     * @param  Collection<int, float>  $valores
     */
    private function desviacion(Collection $valores): ?float
    {
        if ($valores->count() < 2) {
            return null;
        }

        $media = $valores->avg();
        $suma = $valores->sum(fn ($v) => ($v - $media) ** 2);

        return round(sqrt($suma / ($valores->count() - 1)), 1);
    }
}
