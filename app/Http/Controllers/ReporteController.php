<?php

namespace App\Http\Controllers;

use App\Exports\DistribucionRiesgoExport;
use App\Modules\Reportes\Services\ReporteInstitucionalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReporteController extends Controller
{
    public function __construct(private readonly ReporteInstitucionalService $reportes) {}

    /**
     * HU-022/HU-023: reporte general del comportamiento de los niveles de
     * riesgo, filtrable por fecha y categoría.
     */
    public function index(Request $request): View
    {
        $datos = $this->reportes->generar($this->filtros($request));

        return view('panel.reportes.index', [
            ...$datos,
            'filtros' => $this->filtros($request),
        ]);
    }

    public function exportarPdf(Request $request): Response
    {
        $datos = $this->reportes->generar($this->filtros($request));

        return Pdf::loadView('reportes.pdf.distribucion-riesgo', $datos)
            ->download('reporte-riesgo-institucional.pdf');
    }

    public function exportarExcel(Request $request): BinaryFileResponse
    {
        $datos = $this->reportes->generar($this->filtros($request));

        return Excel::download(new DistribucionRiesgoExport($datos['detalle']), 'reporte-riesgo-institucional.xlsx');
    }

    /**
     * @return array{desde: ?string, hasta: ?string, categoria: ?string}
     */
    private function filtros(Request $request): array
    {
        return $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'categoria' => ['nullable', 'in:0,1,2'],
        ]);
    }
}
