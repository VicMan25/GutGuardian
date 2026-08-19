<?php

use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Analitica\Services\ExplicabilidadService;

function hu009VersionExplicabilidad(): VersionModelo
{
    return new VersionModelo([
        'coeficientes' => [
            '1' => ['intercepto' => 0.0, 'beta' => [
                'frituras' => 0.9,   // grande, pero x pequeño -> aporte moderado
                'frutas' => -0.2,    // pequeño beta, x grande -> aporte moderado
                'edad' => 0.05,      // beta y x pequeños -> aporte casi nulo
            ]],
            '2' => ['intercepto' => 0.0, 'beta' => [
                'frituras' => 1.5,
                'frutas' => -0.1,
                'edad' => 0.01,
            ]],
        ],
        'mapa_variables' => [
            'orden_variables' => ['frituras', 'frutas', 'edad'],
            'categoria_base' => 0,
            'categorias' => [0, 1, 2],
            'variables' => [
                'frituras' => ['etiqueta' => 'Consumo de frituras'],
                'frutas' => ['etiqueta' => 'Consumo de frutas'],
                'edad' => ['etiqueta' => 'Rango de edad'],
            ],
        ],
    ]);
}

describe('HU-009 — ExplicabilidadService', function () {

    it('ordena las contribuciones por magnitud de aporte (beta*x), no por tamaño de beta', function () {
        $version = hu009VersionExplicabilidad();
        $prediccion = [
            'categoria' => 2,
            'vector' => ['frituras' => 3.0, 'frutas' => 0.0, 'edad' => 2.0],
        ];

        $contribuciones = (new ExplicabilidadService)->explicar($prediccion, $version);

        // frituras: 1.5*3=4.5 | frutas: -0.1*0=0 | edad: 0.01*2=0.02
        expect($contribuciones)->toHaveCount(3)
            ->and($contribuciones[0]['etiqueta'])->toBe('Consumo de frituras')
            ->and($contribuciones[0]['odds'])->toEqualWithDelta(exp(1.5), 0.01);
    });

    it('el odds ratio reportado es exp(beta) de la categoría predicha, no exp(beta*x)', function () {
        $version = hu009VersionExplicabilidad();
        $prediccion = [
            'categoria' => 1,
            'vector' => ['frituras' => 2.0, 'frutas' => 1.0, 'edad' => 1.0],
        ];

        $contribuciones = (new ExplicabilidadService)->explicar($prediccion, $version);
        $frituras = collect($contribuciones)->firstWhere('etiqueta', 'Consumo de frituras');

        expect($frituras['odds'])->toEqualWithDelta(exp(0.9), 0.01)
            ->and($frituras['frase'])->toContain('multiplica por');
    });

    it('cuando la categoría predicha es la base, explica con los coeficientes de la categoría 1 sin invertir el signo', function () {
        $version = hu009VersionExplicabilidad();
        // Vector con alto consumo de frutas, y el resultado fue "riesgo bajo" (categoría base):
        // el beta negativo de frutas en la categoría 1 (-0.2) es justamente lo que explica
        // por qué el estudiante no quedó en riesgo medio.
        $prediccion = [
            'categoria' => 0,
            'vector' => ['frituras' => 0.0, 'frutas' => 3.0, 'edad' => 1.0],
        ];

        $contribuciones = (new ExplicabilidadService)->explicar($prediccion, $version);
        $frutas = collect($contribuciones)->firstWhere('etiqueta', 'Consumo de frutas');

        // beta de frutas en categoría 1 es -0.2, sin invertir -> odds = exp(-0.2) < 1.
        expect($frutas['odds'])->toEqualWithDelta(exp(-0.2), 0.01)
            ->and($frutas['frase'])->toContain('reduce')
            ->and($frutas['frase'])->toContain('Riesgo medio');
    });

});
