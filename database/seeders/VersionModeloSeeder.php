<?php

namespace Database\Seeders;

use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Analitica\Services\ImportadorModeloService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use RuntimeException;

class VersionModeloSeeder extends Seeder
{
    public function run(ImportadorModeloService $importador): void
    {
        $ruta = storage_path('app/models/modelo_v1.json');

        if (! File::exists($ruta)) {
            throw new RuntimeException(
                "No se encontró {$ruta}. Ejecuta el pipeline de ml/ (generar_dataset_sintetico.py, ".
                'preparar_datos.py, entrenar_modelo.py, exportar_modelo.py) antes de sembrar esta versión.'
            );
        }

        $datos = json_decode(File::get($ruta), true, flags: JSON_THROW_ON_ERROR);

        if (VersionModelo::where('version', $datos['version'])->exists()) {
            $this->command?->info("VersionModeloSeeder: versión {$datos['version']} ya existe, se omite.");

            return;
        }

        // Misma validación que la carga desde /admin/modelos (HU-026).
        $importador->importar($datos, activar: true);
    }
}
