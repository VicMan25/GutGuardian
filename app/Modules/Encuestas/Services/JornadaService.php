<?php

namespace App\Modules\Encuestas\Services;

use App\Models\User;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\JornadaDiligenciamiento;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Respuesta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Encuesta por jornadas: el instrumento completo se reparte en bloques
 * (`preguntas.jornada`) que el estudiante responde en días distintos.
 *
 * El desbloqueo es flexible: la jornada siguiente se *sugiere* desde el día
 * calendario posterior a terminar la anterior (hora de Colombia), pero el
 * estudiante puede seguir de inmediato. El ciclo no vence y se pueden iniciar
 * ciclos nuevos sin límite. El riesgo solo se calcula con el ciclo completo
 * (el modelo necesita las 21 variables; ver hallazgo D6).
 */
class JornadaService
{
    /** Zona horaria con la que se decide «el día siguiente». */
    public const ZONA = 'America/Bogota';

    /**
     * Una jornada abierta hace más de este tiempo sin completarse se considera
     * abandonada: al volver se reinicia su hora de inicio para que el tiempo de
     * diligenciamiento mida la sesión en que de verdad se respondió.
     */
    public const SESION_ABANDONADA_MIN = 60;

    public function total(int $instrumentoId): int
    {
        return (int) Pregunta::whereHas('seccion', fn ($q) => $q->where('instrumento_id', $instrumentoId))->max('jornada');
    }

    /**
     * Preguntas de una jornada en el orden del instrumento (sección, luego
     * pregunta), con lo necesario para pintarlas y validarlas.
     *
     * @return Collection<int, Pregunta>
     */
    public function preguntas(int $instrumentoId, int $jornada): Collection
    {
        return Pregunta::select('preguntas.*')
            ->join('secciones', 'secciones.id', '=', 'preguntas.seccion_id')
            ->where('secciones.instrumento_id', $instrumentoId)
            ->where('preguntas.jornada', $jornada)
            ->orderBy('secciones.orden')
            ->orderBy('preguntas.orden')
            ->with(['opciones', 'items', 'seccion'])
            ->get();
    }

    /**
     * Primera jornada con alguna pregunta sin responder, o null si el ciclo
     * está completo. Cada jornada se guarda de forma atómica, así que basta
     * con que cada pregunta tenga al menos una respuesta.
     */
    public function primeraPendiente(Diligenciamiento $diligenciamiento): ?int
    {
        $respondidas = Respuesta::where('diligenciamiento_id', $diligenciamiento->id)
            ->distinct()
            ->pluck('pregunta_id');

        $porJornada = Pregunta::whereHas('seccion', fn ($q) => $q->where('instrumento_id', $diligenciamiento->instrumento_id))
            ->get(['id', 'jornada'])
            ->groupBy('jornada')
            ->sortKeys();

        foreach ($porJornada as $jornada => $preguntas) {
            if ($preguntas->pluck('id')->diff($respondidas)->isNotEmpty()) {
                return (int) $jornada;
            }
        }

        return null;
    }

    public function registrarInicio(Diligenciamiento $diligenciamiento, int $jornada): void
    {
        $registro = JornadaDiligenciamiento::firstOrCreate(
            ['diligenciamiento_id' => $diligenciamiento->id, 'jornada' => $jornada],
        );

        if ($registro->completada_at !== null) {
            return;
        }

        $abandonada = $registro->iniciada_at?->lt(now()->subMinutes(self::SESION_ABANDONADA_MIN));

        if ($registro->iniciada_at === null || $abandonada) {
            $registro->update(['iniciada_at' => now()]);
        }
    }

    /**
     * Marca la jornada como completada la primera vez que se guarda entera.
     * Volver a guardarla (el estudiante corrige una respuesta) no mueve la
     * fecha: la sugerencia del día siguiente y el tiempo medido no cambian.
     */
    public function registrarCompletada(Diligenciamiento $diligenciamiento, int $jornada): void
    {
        $registro = JornadaDiligenciamiento::firstOrCreate(
            ['diligenciamiento_id' => $diligenciamiento->id, 'jornada' => $jornada],
        );

        if ($registro->completada_at === null) {
            $registro->update([
                'iniciada_at' => $registro->iniciada_at ?? now(),
                'completada_at' => now(),
            ]);
        }
    }

    /**
     * Desde cuándo se sugiere responder la jornada pendiente: el inicio del
     * día calendario siguiente a completar la anterior. Null para la primera
     * jornada (o si la anterior no tiene registro): está disponible ya.
     */
    public function sugeridaDesde(Diligenciamiento $diligenciamiento, int $jornada): ?Carbon
    {
        if ($jornada <= 1) {
            return null;
        }

        $anterior = $diligenciamiento->jornadas->firstWhere('jornada', $jornada - 1)?->completada_at;

        return $anterior?->copy()->setTimezone(self::ZONA)->addDay()->startOfDay();
    }

    /**
     * Estado del ciclo abierto del estudiante para la tarjeta de /inicio, o
     * null si no tiene uno con respuestas.
     *
     * @return array{diligenciamiento: Diligenciamiento, jornada: int, total: int, completadas: int, sugerida_desde: ?Carbon, es_hoy: bool}|null
     */
    public function cicloAbierto(User $user): ?array
    {
        $diligenciamiento = Diligenciamiento::where('user_id', $user->id)
            ->where('estado', 'en_progreso')
            ->with('jornadas')
            ->latest()
            ->first();

        if (! $diligenciamiento) {
            return null;
        }

        $jornada = $this->primeraPendiente($diligenciamiento);
        $total = $this->total($diligenciamiento->instrumento_id);
        $sugerida = $jornada === null ? null : $this->sugeridaDesde($diligenciamiento, $jornada);

        return [
            'diligenciamiento' => $diligenciamiento,
            'jornada' => $jornada ?? $total,
            'total' => $total,
            'completadas' => $jornada === null ? $total : $jornada - 1,
            'sugerida_desde' => $sugerida,
            'es_hoy' => $sugerida === null || $sugerida->lte(now()),
        ];
    }

    /**
     * Ciclos a los que hoy les corresponde recordatorio: en progreso, con la
     * jornada siguiente ya sugerida (se terminó la anterior antes de hoy) y
     * sin recordatorio previo para esa jornada. Solo estudiantes activos que
     * no hayan desactivado los recordatorios.
     *
     * @return Collection<int, array{diligenciamiento: Diligenciamiento, jornada: int, total: int}>
     */
    public function pendientesDeRecordatorio(): Collection
    {
        $hoy = now()->setTimezone(self::ZONA)->startOfDay();

        return Diligenciamiento::where('estado', 'en_progreso')
            ->whereHas('usuario', fn ($q) => $q->where('activo', true)
                ->where('recibir_recordatorios', true)
                ->role('estudiante'))
            ->with(['usuario', 'jornadas'])
            ->get()
            ->map(function (Diligenciamiento $d) {
                $jornada = $this->primeraPendiente($d);

                return $jornada === null ? null : [
                    'diligenciamiento' => $d,
                    'jornada' => $jornada,
                    'total' => $this->total($d->instrumento_id),
                    'sugerida' => $this->sugeridaDesde($d, $jornada),
                ];
            })
            ->filter(fn ($c) => $c !== null
                && $c['sugerida'] !== null
                && $c['sugerida']->lte($hoy)
                && $c['diligenciamiento']->jornadas->firstWhere('jornada', $c['jornada'])?->recordatorio_enviado_at === null)
            ->map(fn ($c) => ['diligenciamiento' => $c['diligenciamiento'], 'jornada' => $c['jornada'], 'total' => $c['total']])
            ->values();
    }

    public function marcarRecordatorioEnviado(Diligenciamiento $diligenciamiento, int $jornada): void
    {
        JornadaDiligenciamiento::updateOrCreate(
            ['diligenciamiento_id' => $diligenciamiento->id, 'jornada' => $jornada],
            ['recordatorio_enviado_at' => now()],
        );
    }
}
