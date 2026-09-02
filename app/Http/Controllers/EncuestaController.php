<?php

namespace App\Http\Controllers;

use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\Opcion;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Respuesta;
use App\Modules\Encuestas\Models\Seccion;
use App\Modules\Usuarios\Models\Perfil;
use App\Modules\Usuarios\Models\Programa;
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

        $perfil = $diligenciamiento->usuario->perfil;

        $campos = $seccion->preguntas->map(
            fn (Pregunta $p) => $this->construirCampo($p, $respuestasPorPregunta->get($p->id, collect()), $perfil)
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

        $this->sincronizarPerfilDesdeSociodemografico($diligenciamiento, $seccion->preguntas, $datos['respuestas']);

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

    /**
     * Si la sección se recarga tras un fallo de validación (ej. un ítem de
     * matriz sin responder), Laravel ya deja la entrada anterior en old().
     * Se prioriza sobre lo guardado en BD para que el estudiante no vea su
     * respuesta recién marcada "desaparecer" — solo quedó sin persistir
     * porque otra pregunta de la misma sección falló.
     */
    private function construirCampo(Pregunta $pregunta, Collection $respuestas, ?Perfil $perfil = null): array
    {
        $anterior = old("respuestas.{$pregunta->id}");

        return match ($pregunta->tipo) {
            'escala' => [
                'tipo' => 'escala',
                'pregunta' => $pregunta,
                'valor' => $anterior !== null ? (int) $anterior : $respuestas->first()?->valor_numerico,
            ],
            'single' => [
                'tipo' => 'single',
                'pregunta' => $pregunta,
                'opciones' => $pregunta->opciones->map(fn ($o) => ['id' => $o->id, 'etiqueta' => $o->etiqueta])->all(),
                // SD1/SD2/SD3/SD4 ya se preguntaron al registrarse (tabla perfiles);
                // si aún no hay una respuesta guardada en este diligenciamiento, se
                // preselecciona con lo que el estudiante ya declaró, en vez de
                // pedírselo de nuevo. Sigue siendo editable.
                'seleccionado' => $anterior !== null
                    ? (int) $anterior
                    : ($respuestas->first()?->opcion_id ?? $this->opcionSociodemograficaDesdePerfil($pregunta, $perfil)),
            ],
            'multiple' => [
                'tipo' => 'multiple',
                'pregunta' => $pregunta,
                'opciones' => $pregunta->opciones->map(fn ($o) => [
                    'id' => $o->id,
                    'etiqueta' => $o->etiqueta,
                    'es_ninguna' => $o->etiqueta === 'Ninguna',
                ])->all(),
                'seleccionados' => $anterior !== null ? array_map('intval', (array) $anterior) : $respuestas->pluck('opcion_id')->all(),
            ],
            'matriz' => [
                'tipo' => 'matriz',
                'pregunta' => $pregunta,
                'items' => $pregunta->items->map(fn ($i) => ['id' => $i->id, 'etiqueta' => $i->etiqueta])->all(),
                'opciones' => $pregunta->opciones->map(fn ($o) => ['valor' => $o->valor_numerico, 'etiqueta' => $o->etiqueta])->all(),
                'respuestas' => $anterior !== null
                    ? array_map('intval', array_filter((array) $anterior, fn ($v) => $v !== null && $v !== ''))
                    : $respuestas->pluck('valor_numerico', 'item_pregunta_id')->all(),
            ],
        };
    }

    /**
     * Traduce los datos ya declarados en `perfiles` (registro) al código de
     * opción del instrumento (SD1 género, SD2 rango de edad, SD3 programa,
     * SD4 semestre), para preseleccionar esas preguntas y no repetírselas al
     * estudiante. Solo aplica a preguntas SD*; para cualquier otra devuelve null.
     */
    private function opcionSociodemograficaDesdePerfil(Pregunta $pregunta, ?Perfil $perfil): ?int
    {
        if (! $perfil) {
            return null;
        }

        return match ($pregunta->codigo) {
            // SD1 solo tiene 3 opciones: "otro" y "prefiero_no_decir" del
            // registro colapsan ambos en "No binario / Otro".
            'SD1' => $this->opcionPorValorNumerico($pregunta, match ($perfil->genero) {
                'masculino' => 1,
                'femenino' => 2,
                'otro', 'prefiero_no_decir' => 3,
                default => null,
            }),
            'SD2' => $this->opcionPorValorNumerico($pregunta, $this->rangoEdad($perfil->edad)),
            // SD3 es una lista fija de 8 programas + "Otro", no la tabla `programas`
            // (13 filas) del registro: se empata por nombre y si no coincide con
            // ninguno se deja sin preseleccionar.
            'SD3' => $perfil->programa ? $pregunta->opciones->firstWhere('etiqueta', $perfil->programa->nombre)?->id : null,
            'SD4' => $this->opcionPorValorNumerico($pregunta, $perfil->semestre),
            default => null,
        };
    }

    private function opcionPorValorNumerico(Pregunta $pregunta, ?int $valor): ?int
    {
        return $valor === null ? null : $pregunta->opciones->firstWhere('valor_numerico', $valor)?->id;
    }

    /**
     * SD2 pregunta un rango, no la edad exacta que se guarda en `perfiles`.
     * Fuera de rango (15-16 años) se ubica en el bucket más cercano.
     */
    private function rangoEdad(?int $edad): ?int
    {
        return match (true) {
            $edad === null => null,
            $edad <= 18 => 1,
            $edad <= 20 => 2,
            $edad <= 22 => 3,
            $edad <= 25 => 4,
            default => 5,
        };
    }

    /**
     * Si el estudiante corrige género/programa/semestre directamente en la
     * encuesta (porque cambiaron desde el registro), esa corrección también
     * se refleja en su perfil — así la próxima encuesta parte del dato
     * actualizado. La edad se excluye a propósito: SD2 solo captura un rango,
     * y convertirlo de vuelta a un número exacto pisaría la edad real ya
     * guardada con un valor inventado.
     */
    private function sincronizarPerfilDesdeSociodemografico(Diligenciamiento $diligenciamiento, Collection $preguntas, array $respuestas): void
    {
        $preguntasSociodemograficas = $preguntas->whereIn('codigo', ['SD1', 'SD3', 'SD4']);

        if ($preguntasSociodemograficas->isEmpty()) {
            return;
        }

        $perfil = $diligenciamiento->usuario->perfil;

        if (! $perfil) {
            return;
        }

        $cambios = [];

        foreach ($preguntasSociodemograficas as $pregunta) {
            $opcion = $pregunta->opciones->firstWhere('id', (int) $respuestas[$pregunta->id]);

            if (! $opcion) {
                continue;
            }

            match ($pregunta->codigo) {
                'SD1' => $cambios['genero'] = match ($opcion->valor_numerico) {
                    1 => 'masculino',
                    2 => 'femenino',
                    default => 'otro',
                },
                'SD4' => $cambios['semestre'] = $opcion->valor_numerico,
                'SD3' => $this->agregarCambioPrograma($cambios, $opcion),
                default => null,
            };
        }

        if ($cambios !== []) {
            $perfil->update($cambios);
        }
    }

    /**
     * "Otro" (valor 8 de SD3) no corresponde a ninguna fila real de
     * `programas`, así que no se sobrescribe el programa_id ya guardado.
     */
    private function agregarCambioPrograma(array &$cambios, Opcion $opcion): void
    {
        if ($opcion->valor_numerico === 8) {
            return;
        }

        $programa = Programa::where('nombre', $opcion->etiqueta)->first();

        if ($programa) {
            $cambios['programa_id'] = $programa->id;
        }
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
