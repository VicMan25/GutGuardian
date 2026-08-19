"""Entrena la regresión logística multinomial (statsmodels.MNLogit) sobre el
dataset preparado, con validación cruzada, métricas multiclase y chequeo de
multicolinealidad (VIF).

Limitación conocida (CLAUDE.md §7): ~22 predictores sobre unos pocos cientos
de registros es un ratio de eventos-por-variable ajustado. Las métricas de
este script deben reportarse con esa salvedad, no como validación clínica.

Entrada:  ml/datos/dataset_preparado.csv
Salida:   ml/datos/resultado_entrenamiento.pkl (para exportar_modelo.py)
"""

from __future__ import annotations

import pickle
import warnings
from pathlib import Path

import numpy as np
import pandas as pd
import statsmodels.api as sm
from sklearn.metrics import confusion_matrix, roc_auc_score
from sklearn.model_selection import StratifiedKFold, train_test_split
from statsmodels.stats.outliers_influence import variance_inflation_factor

from comun import CATEGORIAS, ORDEN_VARIABLES

ENTRADA = Path(__file__).parent / 'datos' / 'dataset_preparado.csv'
SALIDA = Path(__file__).parent / 'datos' / 'resultado_entrenamiento.pkl'

SEMILLA = 2026
K_FOLDS = 5
PROPORCION_TEST = 0.25


def ajustar_mnlogit(X: pd.DataFrame, y: pd.Series):
    exog = sm.add_constant(X, has_constant='add')
    modelo = sm.MNLogit(y, exog)
    with warnings.catch_warnings():
        warnings.simplefilter('ignore')
        return modelo.fit(method='bfgs', maxiter=200, disp=False)


def validacion_cruzada(X: pd.DataFrame, y: pd.Series) -> dict:
    skf = StratifiedKFold(n_splits=K_FOLDS, shuffle=True, random_state=SEMILLA)
    exactitudes = []

    for i, (idx_train, idx_val) in enumerate(skf.split(X, y)):
        resultado = ajustar_mnlogit(X.iloc[idx_train], y.iloc[idx_train])
        exog_val = sm.add_constant(X.iloc[idx_val], has_constant='add')
        pred = resultado.predict(exog_val).values.argmax(axis=1)
        exactitud = (pred == y.iloc[idx_val].values).mean()
        exactitudes.append(exactitud)
        print(f'  Fold {i + 1}/{K_FOLDS}: exactitud = {exactitud:.3f}')

    return {
        'k_folds': K_FOLDS,
        'exactitud_promedio': float(np.mean(exactitudes)),
        'exactitud_desviacion': float(np.std(exactitudes)),
        'exactitudes_por_fold': [float(e) for e in exactitudes],
    }


def calcular_calibracion(y_test: pd.Series, proba: np.ndarray, n_bins: int = 5) -> list[dict]:
    """Calibración simplificada: para cada categoría, agrupa la probabilidad
    predicha en bins y compara contra la frecuencia observada."""
    filas = []
    bordes = np.linspace(0, 1, n_bins + 1)

    for cat in CATEGORIAS:
        p_cat = proba[:, cat]
        y_cat = (y_test.values == cat).astype(int)
        for i in range(n_bins):
            en_bin = (p_cat >= bordes[i]) & (p_cat < bordes[i + 1] if i < n_bins - 1 else p_cat <= bordes[i + 1])
            if en_bin.sum() == 0:
                continue
            filas.append({
                'categoria': cat,
                'bin': f'{bordes[i]:.1f}-{bordes[i + 1]:.1f}',
                'n': int(en_bin.sum()),
                'prob_predicha_media': float(p_cat[en_bin].mean()),
                'frecuencia_observada': float(y_cat[en_bin].mean()),
            })

    return filas


def calcular_vif(X: pd.DataFrame) -> dict:
    exog = sm.add_constant(X, has_constant='add').values
    columnas = ['const'] + list(X.columns)
    vif = {
        columnas[i]: float(variance_inflation_factor(exog, i))
        for i in range(len(columnas))
        if columnas[i] != 'const'
    }
    return vif


def entrenar() -> dict:
    df = pd.read_csv(ENTRADA)
    X = df[ORDEN_VARIABLES]
    y = df['categoria_riesgo'].astype(int)

    print(f'Entrenando sobre {len(df)} registros, {len(ORDEN_VARIABLES)} predictores.')
    print(f'Distribución de clases: {y.value_counts().sort_index().to_dict()}')

    print('\nValidación cruzada (k-fold):')
    metricas_cv = validacion_cruzada(X, y)

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=PROPORCION_TEST, random_state=SEMILLA, stratify=y,
    )

    print(f'\nAjuste final: {len(X_train)} train / {len(X_test)} test.')
    resultado = ajustar_mnlogit(X_train, y_train)

    exog_test = sm.add_constant(X_test, has_constant='add')
    proba_test = resultado.predict(exog_test).values
    pred_test = proba_test.argmax(axis=1)

    exactitud_test = float((pred_test == y_test.values).mean())
    try:
        auc_ovr = float(roc_auc_score(y_test, proba_test, multi_class='ovr', average='macro', labels=CATEGORIAS))
    except ValueError:
        # Puede fallar si el holdout de prueba no contiene las 3 clases.
        auc_ovr = None

    matriz_confusion = confusion_matrix(y_test, pred_test, labels=CATEGORIAS).tolist()
    calibracion = calcular_calibracion(y_test, proba_test)

    print('\nChequeo de multicolinealidad (VIF):')
    vif = calcular_vif(X_train)
    for var, valor in sorted(vif.items(), key=lambda kv: -kv[1])[:5]:
        print(f'  {var}: VIF = {valor:.2f}')
    vif_altos = {k: v for k, v in vif.items() if v > 5}
    if vif_altos:
        print(f'  ADVERTENCIA — VIF > 5 en: {list(vif_altos.keys())}')

    metricas = {
        'validacion_cruzada': metricas_cv,
        'exactitud_test': exactitud_test,
        'auc_macro_ovr_test': auc_ovr,
        'matriz_confusion_test': matriz_confusion,
        'categorias_matriz_confusion': CATEGORIAS,
        'calibracion_test': calibracion,
        'vif': vif,
        'n_train': len(X_train),
        'n_test': len(X_test),
        'n_total': len(df),
        'n_predictores': len(ORDEN_VARIABLES),
        'limitacion_epv': (
            f'{len(df)} registros con 3 clases y {len(ORDEN_VARIABLES)} predictores: '
            'ratio de eventos-por-variable ajustado. Se recomienda regularización o '
            'reducción adicional de variables antes de uso clínico real.'
        ),
    }

    print(f'\nExactitud en test: {exactitud_test:.3f}')
    print(f'AUC macro OVR en test: {auc_ovr}')

    return {
        'resultado_statsmodels': resultado,
        'columnas': ['const'] + ORDEN_VARIABLES,
        'metricas': metricas,
    }


def main() -> None:
    salida = entrenar()
    SALIDA.parent.mkdir(parents=True, exist_ok=True)
    with open(SALIDA, 'wb') as f:
        pickle.dump(salida, f)
    print(f'\nResultado de entrenamiento guardado en {SALIDA}')


if __name__ == '__main__':
    main()
