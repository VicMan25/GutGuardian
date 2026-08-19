<?php

use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Analitica\Services\PredictorService;

/**
 * Casos conocidos: dado un vector de entrada y unos coeficientes fijos,
 * verifica que PredictorService produce exactamente las probabilidades
 * softmax calculadas a mano (aritmética pura, sin base de datos).
 */
function hu009VersionDePrueba(array $coeficientes, array $ordenVariables, array $categorias = [0, 1, 2], int $base = 0): VersionModelo
{
    return new VersionModelo([
        'coeficientes' => $coeficientes,
        'mapa_variables' => [
            'orden_variables' => $ordenVariables,
            'categoria_base' => $base,
            'categorias' => $categorias,
            'variables' => [],
        ],
    ]);
}

describe('HU-009 — PredictorService: softmax con casos conocidos', function () {

    it('un vector con beta positivo en categoría 1 y negativo en categoría 2 produce las probabilidades esperadas', function () {
        $version = hu009VersionDePrueba(
            coeficientes: [
                '1' => ['intercepto' => 0.0, 'beta' => ['x1' => 1.0]],
                '2' => ['intercepto' => 0.0, 'beta' => ['x1' => -1.0]],
            ],
            ordenVariables: ['x1'],
        );

        $resultado = (new PredictorService)->predecirDesdeVector(['x1' => 1.0], $version);

        // eta = [0, 1, -1] -> softmax exacto:
        $suma = exp(0) + exp(1) + exp(-1);
        expect($resultado['probabilidades']['0'])->toEqualWithDelta(exp(0) / $suma, 0.0001)
            ->and($resultado['probabilidades']['1'])->toEqualWithDelta(exp(1) / $suma, 0.0001)
            ->and($resultado['probabilidades']['2'])->toEqualWithDelta(exp(-1) / $suma, 0.0001)
            ->and($resultado['categoria'])->toBe(1);

        $sumaProbabilidades = array_sum($resultado['probabilidades']);
        expect($sumaProbabilidades)->toEqualWithDelta(1.0, 0.0001);
    });

    it('interceptos crecientes sin predictores activos favorecen la categoría de mayor intercepto', function () {
        $version = hu009VersionDePrueba(
            coeficientes: [
                '1' => ['intercepto' => 1.0, 'beta' => ['x1' => 5.0]],
                '2' => ['intercepto' => 2.0, 'beta' => ['x1' => 5.0]],
            ],
            ordenVariables: ['x1'],
        );

        // x1 = 0: el beta no aporta nada, el resultado depende solo de los interceptos.
        $resultado = (new PredictorService)->predecirDesdeVector(['x1' => 0.0], $version);

        $suma = exp(0) + exp(1) + exp(2);
        expect($resultado['probabilidades']['0'])->toEqualWithDelta(exp(0) / $suma, 0.0001)
            ->and($resultado['probabilidades']['1'])->toEqualWithDelta(exp(1) / $suma, 0.0001)
            ->and($resultado['probabilidades']['2'])->toEqualWithDelta(exp(2) / $suma, 0.0001)
            ->and($resultado['categoria'])->toBe(2);
    });

    it('todos los coeficientes en cero produce probabilidades uniformes (1/3 cada categoría)', function () {
        $version = hu009VersionDePrueba(
            coeficientes: [
                '1' => ['intercepto' => 0.0, 'beta' => ['x1' => 0.0, 'x2' => 0.0]],
                '2' => ['intercepto' => 0.0, 'beta' => ['x1' => 0.0, 'x2' => 0.0]],
            ],
            ordenVariables: ['x1', 'x2'],
        );

        $resultado = (new PredictorService)->predecirDesdeVector(['x1' => 3.0, 'x2' => -2.0], $version);

        expect($resultado['probabilidades']['0'])->toEqualWithDelta(1 / 3, 0.0001)
            ->and($resultado['probabilidades']['1'])->toEqualWithDelta(1 / 3, 0.0001)
            ->and($resultado['probabilidades']['2'])->toEqualWithDelta(1 / 3, 0.0001)
            ->and($resultado['categoria'])->toBe(0);
    });

    it('valores lineales grandes no producen overflow gracias a la estabilización numérica', function () {
        $version = hu009VersionDePrueba(
            coeficientes: [
                '1' => ['intercepto' => 0.0, 'beta' => ['x1' => 100.0]],
                '2' => ['intercepto' => 0.0, 'beta' => ['x1' => -100.0]],
            ],
            ordenVariables: ['x1'],
        );

        $resultado = (new PredictorService)->predecirDesdeVector(['x1' => 5.0], $version);

        expect($resultado['probabilidades']['1'])->toEqualWithDelta(1.0, 0.0001)
            ->and($resultado['probabilidades']['0'])->toEqualWithDelta(0.0, 0.0001)
            ->and($resultado['probabilidades']['2'])->toEqualWithDelta(0.0, 0.0001)
            ->and($resultado['categoria'])->toBe(1)
            ->and(is_finite($resultado['probabilidades']['1']))->toBeTrue();
    });

});
