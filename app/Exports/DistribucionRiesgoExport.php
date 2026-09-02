<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * HU-024: exportación a Excel del detalle del reporte institucional
 * (App\Modules\Reportes\Services\ReporteInstitucionalService::generar).
 */
class DistribucionRiesgoExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $detalle) {}

    public function collection(): Collection
    {
        return $this->detalle->map(fn (array $fila) => array_values($fila));
    }

    public function headings(): array
    {
        return ['Estudiante', 'Código', 'Programa', 'Nivel de riesgo', 'Fecha'];
    }
}
