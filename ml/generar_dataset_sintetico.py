"""Genera un dataset SINTÉTICO de 347 respondientes con la forma cruda del
instrumento (una columna por pregunta/ítem), para poder ejercitar el pipeline
de entrenamiento sin depender del dataset real de participantes (que nunca se
sube al repositorio — ver .gitignore y CLAUDE.md §10).

No representa personas reales. Los datos se generan a partir de una variable
latente de "propensión al riesgo" que induce una correlación moderada y
plausible entre hábitos, antecedentes y síntomas, para que el entrenamiento
posterior (VIF, métricas, calibración) no sea degenerado. Los coeficientes
que resulten de este dataset son solo una prueba de concepto de ingeniería,
no un modelo clínicamente válido (ver ml/comun.py).

Salida: ml/datos/dataset_sintetico.csv (carpeta ignorada por git).
"""

from __future__ import annotations

from pathlib import Path

import numpy as np
import pandas as pd

from comun import ANTECEDENTES, ENFERMEDADES, MEDICAMENTOS, SINTOMAS

N = 347
SEMILLA = 2026
rng = np.random.default_rng(SEMILLA)

CORTES_4 = np.array([-0.85, -0.25, 0.6])          # -> 0..3
CORTES_6_TEMPORALIDAD = np.array([-1.0, -0.4, 0.1, 0.6, 1.2])  # -> 0..5
CORTES_7_TIEMPOS_COMIDA = np.array([-1.4, -0.85, -0.3, 0.3, 0.85, 1.4])  # -> 0..6
CORTES_5_DOLOR = np.array([-1.0, -0.35, 0.35, 1.0])  # -> 0..4, luego +1


def ordinal(latente: np.ndarray, cortes: np.ndarray) -> np.ndarray:
    return np.digitize(latente, cortes)


def binario(logit: np.ndarray) -> np.ndarray:
    prob = 1 / (1 + np.exp(-logit))
    return (rng.random(len(logit)) < prob).astype(int)


def ruido() -> np.ndarray:
    return rng.normal(0, 1, N)


def generar() -> pd.DataFrame:
    z = rng.normal(0, 1, N)  # propensión latente al riesgo digestivo

    datos: dict[str, np.ndarray] = {
        'codigo_participante': [f'SIM-{i:04d}' for i in range(1, N + 1)],
        'sd1_genero': rng.choice(['masculino', 'femenino', 'otro'], size=N, p=[0.35, 0.55, 0.10]),
        'sd2_edad': rng.choice([1, 2, 3, 4, 5], size=N, p=[0.30, 0.28, 0.20, 0.14, 0.08]),
        'sd3_programa': rng.integers(1, 9, size=N),
        'sd4_semestre': rng.integers(1, 11, size=N),
    }

    # Hábitos nutricionales protectores: bajan cuando sube el riesgo latente.
    for col in ['p01', 'p02', 'p03', 'p10']:
        datos[col] = ordinal(-0.5 * z + ruido(), CORTES_4)

    # Hábitos nutricionales de riesgo: suben con el riesgo latente.
    for col in ['p04', 'p05', 'p06', 'p07']:
        datos[col] = ordinal(0.5 * z + ruido(), CORTES_4)

    datos['p08'] = ordinal(-0.4 * z + ruido(), CORTES_7_TIEMPOS_COMIDA)
    datos['p09_alcohol'] = ordinal(0.5 * z + ruido(), CORTES_4)
    datos['p09_tabaco'] = ordinal(0.5 * z + ruido(), CORTES_4)

    # P11 — temporalidad de síntomas (0-5): fuente de Y, correlación fuerte con z.
    for s in SINTOMAS:
        datos[f'p11_{s}'] = ordinal(0.7 * z + ruido(), CORTES_6_TEMPORALIDAD)

    # P12 — frecuencia de síntomas último mes (0-3): fuente de Y.
    for s in SINTOMAS:
        datos[f'p12_{s}'] = ordinal(0.7 * z + ruido(), CORTES_4)

    # P13 — dolor abdominal 1-5: fuente de Y.
    datos['p13_dolor'] = ordinal(0.6 * z + ruido(), CORTES_5_DOLOR) + 1

    # P14/P15 — antecedentes personales/familiares (binarios, prevalencia base baja).
    for a in ANTECEDENTES:
        datos[f'p14_{a}'] = binario(0.4 * z - 1.1 + rng.normal(0, 0.3, N))
    for a in ANTECEDENTES:
        datos[f'p15_{a}'] = binario(0.3 * z - 1.0 + rng.normal(0, 0.3, N))

    # P16 — temporalidad de medicamentos (0-5), P17 — frecuencia último mes (0-3).
    for m in MEDICAMENTOS:
        datos[f'p16_{m}'] = ordinal(0.4 * z + ruido(), CORTES_6_TEMPORALIDAD)
    for m in MEDICAMENTOS:
        datos[f'p17_{m}'] = ordinal(0.4 * z + ruido(), CORTES_4)

    # P18 — estilo de vida (binarios). Deporte es protector (baja con z).
    datos['p18_sobrecarga_academica'] = binario(0.5 * z - 0.3 + rng.normal(0, 0.3, N))
    datos['p18_estres_psicologico'] = binario(0.5 * z - 0.3 + rng.normal(0, 0.3, N))
    datos['p18_sedentarismo'] = binario(0.5 * z - 0.3 + rng.normal(0, 0.3, N))
    datos['p18_practica_deporte'] = binario(-0.5 * z - 0.2 + rng.normal(0, 0.3, N))

    # P19 — frecuencia de deporte (0-3): coherente con p18_practica_deporte.
    datos['p19'] = ordinal(-0.4 * z + 0.6 * datos['p18_practica_deporte'] + ruido(), CORTES_4)

    # P20 — enfermedades diagnosticadas (binarios): fuente de Y.
    for e in ENFERMEDADES:
        datos[f'p20_{e}'] = binario(0.5 * z - 1.2 + rng.normal(0, 0.3, N))

    df = pd.DataFrame(datos)

    # Inyecta unos pocos registros incompletos y duplicados para ejercitar
    # los criterios de inclusión/exclusión de preparar_datos.py.
    idx_incompletos = rng.choice(N, size=8, replace=False)
    df.loc[idx_incompletos, 'p13_dolor'] = np.nan

    df = pd.concat([df, df.iloc[[0, 1]].assign(
        codigo_participante=['SIM-0001', 'SIM-0002'],
    )], ignore_index=True)

    return df


def main() -> None:
    df = generar()
    salida = Path(__file__).parent / 'datos' / 'dataset_sintetico.csv'
    salida.parent.mkdir(parents=True, exist_ok=True)
    df.to_csv(salida, index=False)
    print(f'Dataset sintético generado: {salida} ({len(df)} filas, {len(df.columns)} columnas)')


if __name__ == '__main__':
    main()
