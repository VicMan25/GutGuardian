<?php

namespace Database\Seeders;

use App\Modules\Analitica\Models\VersionModelo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use RuntimeException;

class VersionModeloSeeder extends Seeder
{
    public function run(): void
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

        VersionModelo::where('activo', true)->update(['activo' => false]);

        VersionModelo::create([
            'nombre' => $datos['nombre'],
            'version' => $datos['version'],
            'entrenado_at' => $datos['entrenado_at'],
            'activo' => true,
            'coeficientes' => $datos['coeficientes'],
            'metricas' => $datos['metricas'],
            // mapa_variables agrupa todo lo que PredictorService/ExplicabilidadService
            // necesitan además de los coeficientes: orden fijo de variables, categoría
            // base del MNLogit, etiquetas en lenguaje llano y limitaciones a mostrar.
            'mapa_variables' => [
                'orden_variables' => $datos['orden_variables'],
                'categoria_base' => $datos['categoria_base'],
                'categorias' => $datos['categorias'],
                'variables' => $datos['mapa_variables'],
                'limitaciones' => $datos['limitaciones'],
            ],
        ]);
    }
}
