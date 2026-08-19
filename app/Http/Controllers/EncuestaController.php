<?php

namespace App\Http\Controllers;

use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\Opcion;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Respuesta;
use App\Modules\Encuestas\Models\Seccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EncuestaController extends Controller
{
    /**
     * HU-004/HU-005: encuentra el diligenciamiento pendiente/en_progreso del
     * estudiante para el instrumento activo (o crea uno), y lo lleva a la
     * primera sección con preguntas sin responder.
     */
    public function iniciar(): RedirectResponse
    {
        $instrumento = Instrumento::where('activo', true)->firstOrFail();
        $user = Auth::user();

        $diligenciamiento = Diligenciamiento::where('user_id', $user->id)
            ->where('instrumento_id', $instrumento->id)
            ->whereIn('estado', ['pendiente', 'en_progreso'])
            ->latest()
            ->first();

        if (! $diligenciamiento) {
            $diligenciamiento = Diligenciamiento::create([
                'user_id' => $user->id,
                'instrumento_id' => $instrumento->id,
                'estado' => 'pendiente',
            ]);
        }

        $ordenPendiente = $this->primeraSeccionPendiente($diligenciamiento);

        if ($ordenPendiente === null) {
            return redirect()->route('encuesta.confirmar', $diligenciamiento);
        }

        return redirect()->route('encuesta.seccion', [$diligenciamiento, $ordenPendiente]);
    }

    public function seccion(Diligenciamiento $diligenciamiento, int $orden): View|RedirectResponse
    {
        // 'view' (no 'update'): un diligenciamiento completado ya no es editable
        // (DiligenciamientoPolicy::update lo bloquea), pero revisitar su URL debe
        // redirigir amablemente al resultado en vez de devolver 403.
        $this->authorize('view', $diligenciamiento);

        if ($diligenciamiento->estado === 'completado') {
            return redirect()->route('resultado.show', $diligenciamiento);
        }

        $seccion = Seccion::where('instrumento_id', $diligenciamiento->instrumento_id)
            ->where('orden', $orden)
            ->with(['preguntas.opciones', 'preguntas.items'])
            ->firstOrFail();

        $totalSecciones = Seccion::where('instrumento_id', $diligenciamiento->instrumento_id)->count();

        $respuestasPorPregunta = Respuesta::where('diligenciamiento_id', $diligenciamiento->id)
            ->whereIn('pregunta_id', $seccion->preguntas->pluck('id'))
            ->get()
            ->groupBy('pregunta_id');

        $campos = $seccion->preguntas->map(
            fn (Pregunta $p) => $this->construirCampo($p, $respuestasPorPregunta->get($p->id, collect()))
        );

        return view('estudiante.encuesta.seccion', [
            'diligenciamiento' => $diligenciamiento,
            'seccion' => $seccion,
            'campos' => $campos,
            'seccionActual' => $orden,
            'totalSecciones' => $totalSecciones,
            'esUltima' => $orden === $totalSecciones,
        ]);
    }

    public function guardar(Request $request, Diligenciamiento $diligenciamiento, int $orden): RedirectResponse
    {
        $this->authorize('update', $diligenciamiento);

        $seccion = Seccion::where('instrumento_id', $diligenciamiento->instrumento_id)
            ->where('orden', $orden)
            ->with(['preguntas.opciones', 'preguntas.items'])
            ->firstOrFail();

        $datos = $request->validate($this->reglasSeccion($seccion));

        foreach ($seccion->preguntas as $pregunta) {
            $this->guardarRespuesta($diligenciamiento, $pregunta, $datos['respuestas'][$pregunta->id]);
        }

        if ($diligenciamiento->estado === 'pendiente') {
            $diligenciamiento->update(['estado' => 'en_progreso']);
        }

        $totalSecciones = Seccion::where('instrumento_id', $diligenciamiento->instrumento_id)->count();

        if ($orden < $totalSecciones) {
            return redirect()->route('encuesta.seccion', [$diligenciamiento, $orden + 1]);
        }

        return redirect()->route('encuesta.confirmar', $diligenciamiento);
    }

    public function confirmar(Diligenciamiento $diligenciamiento): View|RedirectResponse
    {
        $this->authorize('view', $diligenciamiento);

        if ($diligenciamiento->estado === 'completado') {
            return redirect()->route('resultado.show', $diligenciamiento);
        }

        return view('estudiante.encuesta.confirmar', [
            'diligenciamiento' => $diligenciamiento,
            'todoCompleto' => $this->primeraSeccionPendiente($diligenciamiento) === null,
        ]);
    }

    /**
     * HU-006: cierra el diligenciamiento. A partir de aquí queda inmutable
     * (DiligenciamientoPolicy::update ya no lo permite editar). Un reenvío
     * duplicado (doble clic, botón atrás) redirige al resultado en vez de
     * fallar, en lugar de depender del bloqueo 403 de la policy.
     */
    public function finalizar(Diligenciamiento $diligenciamiento): RedirectResponse
    {
        $this->authorize('view', $diligenciamiento);

        if ($diligenciamiento->estado === 'completado') {
            return redirect()->route('resultado.show', $diligenciamiento);
        }

        if ($this->primeraSeccionPendiente($diligenciamiento) !== null) {
            return redirect()->route('encuesta.confirmar', $diligenciamiento)
                ->with('error', 'Aún tienes preguntas sin responder.');
        }

        $diligenciamiento->update([
            'estado' => 'completado',
            'completado_at' => now(),
        ]);

        return redirect()->route('resultado.show', $diligenciamiento);
    }

    /**
     * Las secciones se guardan de forma atómica (todas sus preguntas a la vez,
     * ver reglasSeccion), así que basta con verificar que cada pregunta tenga
     * al menos una respuesta para saber si la sección quedó completa.
     */
    private function primeraSeccionPendiente(Diligenciamiento $diligenciamiento): ?int
    {
        $instrumento = Instrumento::with('secciones.preguntas')->findOrFail($diligenciamiento->instrumento_id);

        $preguntasRespondidas = Respuesta::where('diligenciamiento_id', $diligenciamiento->id)
            ->distinct()
            ->pluck('pregunta_id');

        foreach ($instrumento->secciones as $seccion) {
            $idsPreguntas = $seccion->preguntas->pluck('id');
            if ($idsPreguntas->diff($preguntasRespondidas)->isNotEmpty()) {
                return $seccion->orden;
            }
        }

        return null;
    }

    private function construirCampo(Pregunta $pregunta, Collection $respuestas): array
    {
        return match ($pregunta->tipo) {
            'escala' => [
                'tipo' => 'escala',
                'pregunta' => $pregunta,
                'valor' => $respuestas->first()?->valor_numerico,
            ],
            'single' => [
                'tipo' => 'single',
                'pregunta' => $pregunta,
                'opciones' => $pregunta->opciones->map(fn ($o) => ['id' => $o->id, 'etiqueta' => $o->etiqueta])->all(),
                'seleccionado' => $respuestas->first()?->opcion_id,
            ],
            'multiple' => [
                'tipo' => 'multiple',
                'pregunta' => $pregunta,
                'opciones' => $pregunta->opciones->map(fn ($o) => [
                    'id' => $o->id,
                    'etiqueta' => $o->etiqueta,
                    'es_ninguna' => $o->etiqueta === 'Ninguna',
                ])->all(),
                'seleccionados' => $respuestas->pluck('opcion_id')->all(),
            ],
            'matriz' => [
                'tipo' => 'matriz',
                'pregunta' => $pregunta,
                'items' => $pregunta->items->map(fn ($i) => ['id' => $i->id, 'etiqueta' => $i->etiqueta])->all(),
                'opciones' => $pregunta->opciones->map(fn ($o) => ['valor' => $o->valor_numerico, 'etiqueta' => $o->etiqueta])->all(),
                'respuestas' => $respuestas->pluck('valor_numerico', 'item_pregunta_id')->all(),
            ],
        };
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function reglasSeccion(Seccion $seccion): array
    {
        $reglas = [];

        foreach ($seccion->preguntas as $pregunta) {
            $campo = "respuestas.{$pregunta->id}";

            $reglas[$campo] = match ($pregunta->tipo) {
                'escala', 'single' => ['required', 'integer'],
                'multiple' => ['required', 'array', 'min:1'],
                'matriz' => ['required', 'array'],
            };

            if ($pregunta->tipo === 'matriz') {
                foreach ($pregunta->items as $item) {
                    $reglas["{$campo}.{$item->id}"] = ['required', 'integer'];
                }
            }
        }

        return $reglas;
    }

    private function guardarRespuesta(Diligenciamiento $diligenciamiento, Pregunta $pregunta, mixed $valor): void
    {
        match ($pregunta->tipo) {
            'escala' => $this->guardarPorValorNumerico($diligenciamiento, $pregunta, null, (int) $valor),
            'single' => $this->guardarPorOpcionId($diligenciamiento, $pregunta, (int) $valor),
            'multiple' => $this->guardarMultiple($diligenciamiento, $pregunta, array_map('intval', $valor)),
            'matriz' => $this->guardarMatriz($diligenciamiento, $pregunta, $valor),
        };
    }

    private function guardarPorValorNumerico(Diligenciamiento $d, Pregunta $pregunta, ?int $itemId, int $valorNumerico): void
    {
        $opcion = Opcion::where('pregunta_id', $pregunta->id)->where('valor_numerico', $valorNumerico)->firstOrFail();

        Respuesta::updateOrCreate(
            ['diligenciamiento_id' => $d->id, 'pregunta_id' => $pregunta->id, 'item_pregunta_id' => $itemId],
            ['opcion_id' => $opcion->id, 'valor_numerico' => $valorNumerico],
        );
    }

    private function guardarPorOpcionId(Diligenciamiento $d, Pregunta $pregunta, int $opcionId): void
    {
        $opcion = Opcion::where('pregunta_id', $pregunta->id)->findOrFail($opcionId);

        Respuesta::updateOrCreate(
            ['diligenciamiento_id' => $d->id, 'pregunta_id' => $pregunta->id, 'item_pregunta_id' => null],
            ['opcion_id' => $opcion->id, 'valor_numerico' => $opcion->valor_numerico],
        );
    }

    private function guardarMultiple(Diligenciamiento $d, Pregunta $pregunta, array $opcionIds): void
    {
        Respuesta::where('diligenciamiento_id', $d->id)->where('pregunta_id', $pregunta->id)->delete();

        $opciones = Opcion::where('pregunta_id', $pregunta->id)->whereIn('id', $opcionIds)->get();

        foreach ($opciones as $opcion) {
            Respuesta::create([
                'diligenciamiento_id' => $d->id,
                'pregunta_id' => $pregunta->id,
                'opcion_id' => $opcion->id,
                'valor_numerico' => $opcion->valor_numerico,
            ]);
        }
    }

    private function guardarMatriz(Diligenciamiento $d, Pregunta $pregunta, array $porItem): void
    {
        foreach ($porItem as $itemId => $valorNumerico) {
            $this->guardarPorValorNumerico($d, $pregunta, (int) $itemId, (int) $valorNumerico);
        }
    }
}
