<?php

namespace App\Http\Controllers;

use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Analitica\Services\ImportadorModeloService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

/**
 * HU-026: gestión de versiones del modelo predictivo (rol admin, permiso
 * gestionar_versiones_modelo). Registrar una versión no la activa; activarla
 * cambia qué coeficientes usa PredictorService para las evaluaciones nuevas,
 * sin recalcular las ya registradas (cada una conserva su version_modelo_id).
 */
class ModeloController extends Controller
{
    public function __construct(private readonly ImportadorModeloService $importador) {}

    public function index(): View
    {
        return view('admin.modelos.index', [
            'versiones' => VersionModelo::withCount('evaluaciones')
                ->orderByDesc('activo')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function show(VersionModelo $version): View
    {
        return view('admin.modelos.show', [
            'version' => $version->loadCount('evaluaciones'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'max:2048', 'mimetypes:application/json,text/plain'],
            'activar' => ['nullable', 'boolean'],
        ], [
            'archivo.required' => 'Selecciona el archivo JSON exportado por el pipeline de entrenamiento.',
            'archivo.mimetypes' => 'El archivo debe ser un JSON.',
        ]);

        try {
            $datos = json_decode($request->file('archivo')->get(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['archivo' => 'El archivo no contiene un JSON válido.']);
        }

        if (! is_array($datos)) {
            throw ValidationException::withMessages(['archivo' => 'El archivo no contiene un JSON válido.']);
        }

        $version = $this->importador->importar($datos, $request->user(), $request->boolean('activar'));

        return redirect()->route('admin.modelos.show', $version)
            ->with('status', $version->activo ? 'version-registrada-activa' : 'version-registrada');
    }

    public function activar(Request $request, VersionModelo $version): RedirectResponse
    {
        $this->importador->activar($version, $request->user());

        return redirect()->route('admin.modelos.show', $version)->with('status', 'version-activada');
    }
}
