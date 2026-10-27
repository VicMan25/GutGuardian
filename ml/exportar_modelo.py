"""Exporta el resultado de entrenamiento a storage/app/models/modelo_v{N}.json,
en el formato que consume PredictorService (Laravel): coeficientes por
categoría (con intercepto), orden y mapa de variables, y métricas.

Cada versión se escribe en su propio archivo: los JSON anteriores (p. ej.
modelo_v1.json, el de datos sintéticos) se conservan como evidencia de
trazabilidad y siguen sirviendo a VersionModeloSeeder y a los tests.
Para publicar una versión nueva: cambiar VERSION, NOMBRE y LIMITACIONES.

Entrada:  ml/datos/resultado_entrenamiento.pkl
Salida:   storage/app/models/modelo_v{N}.json
"""

from __future__ import annotations

import json
import pickle
from datetime import datetime, timezone
from pathlib import Path

from comun import CATEGORIA_BASE, CATEGORIAS, ETIQUETAS_VARIABLES, ORDEN_VARIABLES

ENTRADA = Path(__file__).parent / 'datos' / 'resultado_entrenamiento.pkl'
VERSION = '2.0'
NOMBRE = 'Modelo multinomial GutGuardián — datos reales (347), regla de riesgo validada'
SALIDA = Path(__file__).parent.parent / 'storage' / 'app' / 'models' / f'modelo_v{VERSION.split(".")[0]}.json'

LIMITACIONES = (
    'Entrenado sobre los 347 registros reales de participantes, con la regla de nivel de '
    'riesgo aprobada por Enfermería y Nutrición. Con esa regla solo 2 participantes quedan '
    'en riesgo alto: la ecuación de esa categoría se apoya sobre todo en la regularización '
    'y sus resultados son poco estables. Se ajustó con penalización L2 y pesos por clase, '
    'sin generar registros artificiales. Es una herramienta de tamizaje y autocuidado; no '
    'emite diagnósticos. Se recomienda reentrenar con los datos de la prueba piloto.'
)


def exportar() -> dict:
    with open(ENTRADA, 'rb') as f:
        entrenamiento = pickle.load(f)

    coeficientes = entrenamiento['coeficientes']

    mapa_variables = {
        var: {'etiqueta': ETIQUETAS_VARIABLES[var]}
        for var in ORDEN_VARIABLES
    }

    return {
        'version': VERSION,
        'nombre': NOMBRE,
        'entrenado_at': datetime.now(timezone.utc).isoformat(),
        'categoria_base': CATEGORIA_BASE,
        'categorias': CATEGORIAS,
        'orden_variables': ORDEN_VARIABLES,
        'mapa_variables': mapa_variables,
        'coeficientes': coeficientes,
        'metricas': entrenamiento['metricas'],
        'limitaciones': LIMITACIONES,
    }


def main() -> None:
    modelo = exportar()
    SALIDA.parent.mkdir(parents=True, exist_ok=True)
    with open(SALIDA, 'w', encoding='utf-8') as f:
        json.dump(modelo, f, ensure_ascii=False, indent=2)
    print(f'Modelo exportado: {SALIDA}')


if __name__ == '__main__':
    main()
