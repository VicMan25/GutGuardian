"""Exporta el resultado de entrenamiento a storage/app/models/modelo_v1.json,
en el formato que consume PredictorService (Laravel): coeficientes por
categoría (con intercepto), orden y mapa de variables, y métricas.

Entrada:  ml/datos/resultado_entrenamiento.pkl
Salida:   storage/app/models/modelo_v1.json
"""

from __future__ import annotations

import json
import pickle
from datetime import datetime, timezone
from pathlib import Path

from comun import CATEGORIA_BASE, CATEGORIAS, ETIQUETAS_VARIABLES, ORDEN_VARIABLES

ENTRADA = Path(__file__).parent / 'datos' / 'resultado_entrenamiento.pkl'
SALIDA = Path(__file__).parent.parent / 'storage' / 'app' / 'models' / 'modelo_v1.json'

LIMITACIONES = (
    'Modelo entrenado sobre un dataset SINTÉTICO de prueba de concepto, no sobre los '
    '347 registros reales de participantes. La variable dependiente se derivó con una '
    'regla PLACEHOLDER (ver ml/comun.py) pendiente de validación por el equipo de '
    'Enfermería (bloqueante documentado en CLAUDE.md §7 y §11). No usar para tamizaje '
    'real hasta reentrenar con datos y regla clínica definitivos.'
)


def exportar() -> dict:
    with open(ENTRADA, 'rb') as f:
        entrenamiento = pickle.load(f)

    resultado = entrenamiento['resultado_statsmodels']
    params = resultado.params.copy()

    categorias_no_base = [c for c in CATEGORIAS if c != CATEGORIA_BASE]
    params.columns = categorias_no_base

    coeficientes = {}
    for categoria in categorias_no_base:
        columna = params[categoria]
        coeficientes[str(categoria)] = {
            'intercepto': float(columna.loc['const']),
            'beta': {var: float(columna.loc[var]) for var in ORDEN_VARIABLES},
        }

    mapa_variables = {
        var: {'etiqueta': ETIQUETAS_VARIABLES[var]}
        for var in ORDEN_VARIABLES
    }

    return {
        'version': '1.0',
        'nombre': 'Modelo multinomial GutGuardián — placeholder de ingeniería (Sprint 4)',
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
