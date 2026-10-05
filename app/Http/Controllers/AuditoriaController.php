<?php

namespace App\Http\Controllers;

use App\Modules\Analitica\Services\ImportadorModeloService;
use App\Modules\Panel\Services\AuditoriaClinicaService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/**
 * Consulta del log de auditoría (Ley 1581 de 2012). Permite al administrador
 * responder quién accedió a qué información clínica y cuándo, además de los
 * cambios de versión del modelo predictivo (HU-026). Solo lectura.
 */
class AuditoriaController extends Controller
{
    /** @var array<string, string> log => etiqueta */
    public const REGISTROS = [
        AuditoriaClinicaService::LOG => 'Acceso a datos clínicos',
        ImportadorModeloService::LOG => 'Modelo predictivo',
    ];

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'registro' => ['nullable', 'in:'.implode(',', array_keys(self::REGISTROS))],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $registro = $filtros['registro'] ?? AuditoriaClinicaService::LOG;

        $actividades = Activity::inLog($registro)
            ->with(['causer', 'subject'])
            ->when($filtros['desde'] ?? null, fn ($q, $desde) => $q->whereDate('created_at', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('created_at', '<=', $hasta))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.auditoria.index', [
            'actividades' => $actividades,
            'registros' => self::REGISTROS,
            'registro' => $registro,
            'filtros' => $filtros,
        ]);
    }
}
