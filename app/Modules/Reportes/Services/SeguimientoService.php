<?php

namespace App\Modules\Reportes\Services;

use App\Models\User;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Respuesta;
use Illuminate\Support\Collection;

/**
 * Arma los datasets de seguimiento individual del estudiante: evolución del
 * riesgo (HU-008, real) y, como funcionalidad complementaria sin HU numerada
 * en el documento fuente (ver docs/HISTORIAS_USUARIO.md, nota 3), la
 * evolución del dolor abdominal y de los 6 síntomas rastreados por el
 * instrumento (temporalidad y frecuencia).
 */
class SeguimientoService
{
    private const SINTOMAS = ['Diarrea', 'Dolor abdominal', 'Vómito', 'Náuseas', 'Estreñimiento', 'Fiebre'];

    /**
     * @return Collection<int, Diligenciamiento>
     */
    public function diligenciamientosCompletados(User $user): Collection
    {
        return Diligenciamiento::where('user_id', $user->id)
            ->where('estado', 'completado')
            ->with(['evaluaciones' => fn ($q) => $q->latest('evaluado_at')])
            ->orderBy('completado_at')
            ->get();
    }

    /**
     * HU-008: evolución de las probabilidades por categoría de riesgo.
     *
     * @return array{etiquetas: array<int, string>, bajo: array<int, float>, medio: array<int, float>, alto: array<int, float>}
     */
    public function evolucionRiesgo(Collection $diligenciamientos): array
    {
        $etiquetas = [];
        $bajo = [];
        $medio = [];
        $alto = [];

        foreach ($diligenciamientos as $d) {
            $evaluacion = $d->evaluaciones->first();
            if (! $evaluacion) {
                continue;
            }

            $etiquetas[] = $d->completado_at->format('d/m/Y');
            $bajo[] = round((float) $evaluacion->prob_0 * 100, 1);
            $medio[] = round((float) $evaluacion->prob_1 * 100, 1);
            $alto[] = round((float) $evaluacion->prob_2 * 100, 1);
        }

        return compact('etiquetas', 'bajo', 'medio', 'alto');
    }

    /**
     * Funcionalidad complementaria, sin HU numerada en el documento fuente:
     * evolución de la escala de dolor abdominal (P13, 1-5).
     *
     * @return array{etiquetas: array<int, string>, valores: array<int, int>}
     */
    public function evolucionDolor(Collection $diligenciamientos, Collection $respuestas): array
    {
        $etiquetas = [];
        $valores = [];

        foreach ($diligenciamientos as $d) {
            $respuesta = $respuestas->first(
                fn (Respuesta $r) => $r->diligenciamiento_id === $d->id && $r->pregunta->codigo === 'P13'
            );

            if (! $respuesta) {
                continue;
            }

            $etiquetas[] = $d->completado_at->format('d/m/Y');
            $valores[] = $respuesta->valor_numerico;
        }

        return compact('etiquetas', 'valores');
    }

    /**
     * Funcionalidad complementaria, sin HU numerada en el documento fuente:
     * por cada uno de los 6 síntomas, su temporalidad (P11) y frecuencia en
     * el último mes (P12) a lo largo de los diligenciamientos.
     *
     * @return array<int, array{sintoma: string, etiquetas: array<int, string>, frecuencia: array<int, int|null>, temporalidad: array<int, int|null>}>
     */
    public function seguimientoSintomas(Collection $diligenciamientos, Collection $respuestas): array
    {
        $paneles = [];

        foreach (self::SINTOMAS as $sintoma) {
            $etiquetas = [];
            $frecuencia = [];
            $temporalidad = [];

            foreach ($diligenciamientos as $d) {
                $rFrecuencia = $respuestas->first(
                    fn (Respuesta $r) => $r->diligenciamiento_id === $d->id
                        && $r->pregunta->codigo === 'P12' && $r->item?->etiqueta === $sintoma
                );
                $rTemporalidad = $respuestas->first(
                    fn (Respuesta $r) => $r->diligenciamiento_id === $d->id
                        && $r->pregunta->codigo === 'P11' && $r->item?->etiqueta === $sintoma
                );

                if (! $rFrecuencia && ! $rTemporalidad) {
                    continue;
                }

                $etiquetas[] = $d->completado_at->format('d/m/Y');
                $frecuencia[] = $rFrecuencia?->valor_numerico;
                $temporalidad[] = $rTemporalidad?->valor_numerico;
            }

            $paneles[] = compact('sintoma', 'etiquetas', 'frecuencia', 'temporalidad');
        }

        return $paneles;
    }

    /**
     * Respuestas de P11/P12/P13 de los diligenciamientos dados, precargadas
     * una sola vez para que evolucionDolor()/seguimientoSintomas() no repitan consultas.
     */
    public function respuestasClinicas(Collection $diligenciamientos): Collection
    {
        return Respuesta::whereIn('diligenciamiento_id', $diligenciamientos->pluck('id'))
            ->whereHas('pregunta', fn ($q) => $q->whereIn('codigo', ['P11', 'P12', 'P13']))
            ->with(['pregunta', 'item'])
            ->get();
    }
}
