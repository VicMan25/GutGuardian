<?php

namespace App\Modules\Analitica\Services;

use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Respuesta;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Calcula la predicción del modelo multinomial (softmax) para un
 * diligenciamiento, a partir de la versión de modelo activa.
 *
 * Aritmética pura, sin dependencias externas — ver CLAUDE.md §7. El vector
 * de predictores debe construirse con exactamente la misma definición que
 * ml/comun.py::construir_predictores; si esas dos implementaciones divergen,
 * las probabilidades dejan de ser válidas para los coeficientes entrenados.
 */
class PredictorService
{
    // Recencia P11/P16 (0-5): valor a partir del cual un ítem cuenta como "reciente".
    private const RECIENCIA_ALTA = 4;

    // Frecuencia P17 (0-3): valor a partir del cual un medicamento cuenta como "frecuente".
    private const FRECUENCIA_ALTA = 2;

    private const P18_FACTORES_RIESGO = ['Sobrecarga académica', 'Estrés psicológico', 'Sedentarismo'];

    public function versionActiva(): VersionModelo
    {
        $version = VersionModelo::where('activo', true)->first();

        if (! $version) {
            throw new RuntimeException('No hay una versión de modelo activa en versiones_modelo.');
        }

        return $version;
    }

    /**
     * @return array<string, float> vector de predictores en el orden declarado por el modelo.
     */
    public function armarVector(Diligenciamiento $diligenciamiento, VersionModelo $version): array
    {
        $respuestas = Respuesta::where('diligenciamiento_id', $diligenciamiento->id)
            ->with(['pregunta', 'item', 'opcion'])
            ->get()
            ->groupBy(fn (Respuesta $r) => $r->pregunta->codigo);

        $disponibles = $this->calcularVariablesDisponibles($respuestas);

        $orden = $version->mapa_variables['orden_variables'];
        $vector = [];
        foreach ($orden as $variable) {
            if (! array_key_exists($variable, $disponibles)) {
                throw new RuntimeException("PredictorService no sabe calcular la variable '{$variable}'.");
            }
            $vector[$variable] = $disponibles[$variable];
        }

        return $vector;
    }

    /**
     * Predictor lineal por categoría (excepto la base, que es 0) + softmax.
     *
     * @return array{categoria: int, probabilidades: array<string, float>, vector: array<string, float>}
     */
    public function predecir(Diligenciamiento $diligenciamiento): array
    {
        $version = $this->versionActiva();
        $vector = $this->armarVector($diligenciamiento, $version);

        return $this->predecirDesdeVector($vector, $version) + ['vector' => $vector];
    }

    /**
     * @param  array<string, float>  $vector
     * @return array{categoria: int, probabilidades: array<string, float>}
     */
    public function predecirDesdeVector(array $vector, VersionModelo $version): array
    {
        $categorias = $version->mapa_variables['categorias'];
        $categoriaBase = $version->mapa_variables['categoria_base'];
        $coeficientes = $version->coeficientes;

        $predictorLineal = [];
        foreach ($categorias as $categoria) {
            if ($categoria === $categoriaBase) {
                $predictorLineal[$categoria] = 0.0;

                continue;
            }

            $coef = $coeficientes[(string) $categoria];
            $eta = $coef['intercepto'];
            foreach ($coef['beta'] as $variable => $beta) {
                $eta += $beta * ($vector[$variable] ?? 0.0);
            }
            $predictorLineal[$categoria] = $eta;
        }

        $probabilidades = $this->softmax($predictorLineal);
        $categoriaGanadora = array_key_first(array_filter(
            $probabilidades,
            fn ($p) => $p === max($probabilidades)
        ));

        return [
            'categoria' => (int) $categoriaGanadora,
            'probabilidades' => $probabilidades,
        ];
    }

    /**
     * @param  array<int, float>  $predictorLineal  categoria => eta
     * @return array<string, float> categoria => probabilidad (suma 1.0)
     */
    private function softmax(array $predictorLineal): array
    {
        // Estabilidad numérica: restar el máximo antes de exponenciar.
        $max = max($predictorLineal);
        $exponenciales = array_map(fn ($eta) => exp($eta - $max), $predictorLineal);
        $suma = array_sum($exponenciales);

        $probabilidades = [];
        foreach ($exponenciales as $categoria => $valor) {
            $probabilidades[(string) $categoria] = $valor / $suma;
        }

        return $probabilidades;
    }

    /**
     * @param  Collection<string, Collection<int, Respuesta>>  $porPregunta
     * @return array<string, float>
     */
    private function calcularVariablesDisponibles(Collection $porPregunta): array
    {
        $unico = fn (string $codigo) => (float) ($porPregunta->get($codigo)?->first()?->valor_numerico ?? 0);

        $porItem = function (string $codigo, string $etiquetaItem) use ($porPregunta): float {
            $fila = $porPregunta->get($codigo)?->first(fn (Respuesta $r) => $r->item?->etiqueta === $etiquetaItem);

            return (float) ($fila->valor_numerico ?? 0);
        };

        $sumaOpciones = fn (string $codigo) => (float) ($porPregunta->get($codigo)?->sum('valor_numerico') ?? 0);

        $contarItemsConUmbral = function (string $codigo, int $umbral) use ($porPregunta): float {
            return (float) ($porPregunta->get($codigo)?->filter(fn (Respuesta $r) => $r->valor_numerico >= $umbral)->count() ?? 0);
        };

        $opcionSeleccionada = function (string $codigo, string $etiquetaOpcion) use ($porPregunta): float {
            $existe = $porPregunta->get($codigo)?->contains(fn (Respuesta $r) => $r->opcion?->etiqueta === $etiquetaOpcion) ?? false;

            return $existe ? 1.0 : 0.0;
        };

        $contarOpciones = function (string $codigo, array $etiquetas) use ($porPregunta): float {
            return (float) ($porPregunta->get($codigo)?->filter(fn (Respuesta $r) => in_array($r->opcion?->etiqueta, $etiquetas, true))->count() ?? 0);
        };

        $generoValor = $unico('SD1');

        return [
            'edad' => $unico('SD2'),
            'semestre' => $unico('SD4'),
            'genero_femenino' => $generoValor === 2.0 ? 1.0 : 0.0,
            'genero_otro' => $generoValor === 3.0 ? 1.0 : 0.0,
            'p01_frutas' => $unico('P01'),
            'p02_ensaladas' => $unico('P02'),
            'p03_integrales' => $unico('P03'),
            'p04_embutidos' => $unico('P04'),
            'p05_empaquetados' => $unico('P05'),
            'p06_azucares' => $unico('P06'),
            'p07_frituras' => $unico('P07'),
            'p08_tiempos_comida' => $unico('P08'),
            'p09_alcohol' => $porItem('P09', 'Alcohol'),
            'p09_tabaco' => $porItem('P09', 'Tabaco'),
            'p10_lavado_manos' => $unico('P10'),
            'p14_antecedentes_personales_count' => $sumaOpciones('P14'),
            'p15_antecedentes_familiares_count' => $sumaOpciones('P15'),
            'p16_medicamentos_recientes_count' => $contarItemsConUmbral('P16', self::RECIENCIA_ALTA),
            'p17_medicamentos_frecuentes_count' => $contarItemsConUmbral('P17', self::FRECUENCIA_ALTA),
            'p18_practica_deporte' => $opcionSeleccionada('P18', 'Practica deporte'),
            'p18_factores_riesgo_count' => $contarOpciones('P18', self::P18_FACTORES_RIESGO),
            'p19_frecuencia_deporte' => $unico('P19'),
        ];
    }
}
