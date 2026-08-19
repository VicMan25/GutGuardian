"""Prepara los datos crudos del instrumento para entrenamiento:

1. Aplica criterios de inclusión/exclusión.
2. Deriva la variable dependiente Y con la regla placeholder de comun.py.
3. Codifica los predictores (ordinales ya vienen numéricos desde el
   instrumento; P14/P15/P16/P17/P18 se resumen en conteos; género se
   dummy-codifica) en el orden fijo ORDEN_VARIABLES.

Entrada:  ml/datos/dataset_sintetico.csv (o el CSV real exportado de
          `respuestas`, con las mismas columnas, cuando exista).
Salida:   ml/datos/dataset_preparado.csv
"""

from __future__ import annotations

from pathlib import Path

import pandas as pd

from comun import ORDEN_VARIABLES, construir_predictores, derivar_categoria_riesgo

ENTRADA = Path(__file__).parent / 'datos' / 'dataset_sintetico.csv'
SALIDA = Path(__file__).parent / 'datos' / 'dataset_preparado.csv'

# Columnas que deben estar completas para incluir un registro. Un
# diligenciamiento con secciones clínicas sin terminar no permite derivar Y
# de forma confiable y se excluye (criterio de exclusión).
COLUMNAS_REQUERIDAS = ['p13_dolor']


def aplicar_criterios_inclusion(df: pd.DataFrame) -> pd.DataFrame:
    n_inicial = len(df)

    # Exclusión: diligenciamientos incompletos (columnas clínicas clave con NaN).
    df = df.dropna(subset=COLUMNAS_REQUERIDAS)

    # Exclusión: duplicados de codigo_participante (conserva el primer registro).
    df = df.drop_duplicates(subset='codigo_participante', keep='first')

    n_final = len(df)
    print(f'Criterios de inclusión/exclusión: {n_inicial} -> {n_final} registros '
          f'({n_inicial - n_final} excluidos)')

    return df.reset_index(drop=True)


def preparar() -> pd.DataFrame:
    df = pd.read_csv(ENTRADA)
    df = aplicar_criterios_inclusion(df)

    df['categoria_riesgo'] = df.apply(derivar_categoria_riesgo, axis=1)

    predictores = df.apply(construir_predictores, axis=1)
    preparado = pd.concat([df[['codigo_participante']], predictores, df[['categoria_riesgo']]], axis=1)

    assert list(predictores.columns) == ORDEN_VARIABLES, 'El orden de columnas de X debe ser estable'

    return preparado


def main() -> None:
    preparado = preparar()
    SALIDA.parent.mkdir(parents=True, exist_ok=True)
    preparado.to_csv(SALIDA, index=False)

    distribucion = preparado['categoria_riesgo'].value_counts().sort_index()
    print(f'Dataset preparado: {SALIDA} ({len(preparado)} filas, {len(ORDEN_VARIABLES)} predictores)')
    print(f'Distribución de categoría de riesgo:\n{distribucion.to_string()}')


if __name__ == '__main__':
    main()
