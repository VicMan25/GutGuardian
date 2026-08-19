<?php

namespace App\Modules\Analitica\Services;

use App\Modules\Analitica\Models\VersionModelo;

/**
 * Explica una predicción del PredictorService: los 5 predictores con mayor
 * contribución a la categoría asignada, con su odds ratio y una frase en
 * lenguaje llano — exigido por CLAUDE.md §7 ("Explicabilidad").
 */
class ExplicabilidadService
{
    private const TOP_N = 5;

    private const ETIQUETAS_CATEGORIA = ['Riesgo bajo', 'Riesgo medio', 'Riesgo alto'];

    /**
     * @param  array{categoria: int, probabilidades: array<string, float>, vector: array<string, float>}  $prediccion
     * @return array<int, array{etiqueta: string, odds: float, frase: string}>
     */
    public function explicar(array $prediccion, VersionModelo $version): array
    {
        $categoriaPredicha = $prediccion['categoria'];
        $categoriaBase = $version->mapa_variables['categoria_base'];
        $vector = $prediccion['vector'];

        // La categoría base no tiene coeficientes propios (su predictor lineal es
        // siempre 0 en el softmax). Para explicar un resultado en la categoría
        // base se usan, sin invertir el signo, los coeficientes de la categoría
        // no-base más cercana: un aporte negativo (beta*x < 0) es precisamente
        // lo que alejó al estudiante de esa categoría y lo mantuvo en la base.
        $categoriaReferencia = $categoriaPredicha === $categoriaBase
            ? min(array_filter($version->mapa_variables['categorias'], fn ($c) => $c !== $categoriaBase))
            : $categoriaPredicha;

        $coeficientes = $version->coeficientes[(string) $categoriaReferencia];
        $etiquetasVariables = $version->mapa_variables['variables'];

        $contribuciones = [];
        foreach ($coeficientes['beta'] as $variable => $beta) {
            $x = (float) ($vector[$variable] ?? 0.0);

            $contribuciones[] = [
                'variable' => $variable,
                'etiqueta' => $etiquetasVariables[$variable]['etiqueta'] ?? $variable,
                'aporte' => $beta * $x,
                'odds' => exp($beta),
            ];
        }

        usort($contribuciones, fn ($a, $b) => abs($b['aporte']) <=> abs($a['aporte']));
        $top = array_slice($contribuciones, 0, self::TOP_N);

        return array_map(
            fn ($c) => [
                'etiqueta' => $c['etiqueta'],
                'odds' => round($c['odds'], 2),
                'frase' => $this->frase($c, $categoriaReferencia),
            ],
            $top
        );
    }

    private function frase(array $contribucion, int $categoriaReferencia): string
    {
        $etiquetaCategoria = self::ETIQUETAS_CATEGORIA[$categoriaReferencia];
        $vecesOr = round($contribucion['odds'], 1);

        return $contribucion['odds'] >= 1
            ? "{$contribucion['etiqueta']} multiplica por {$vecesOr} la probabilidad relativa de clasificarse en {$etiquetaCategoria}."
            : "{$contribucion['etiqueta']} reduce (×{$vecesOr}) la probabilidad relativa de clasificarse en {$etiquetaCategoria}.";
    }
}
