"""Entrena la regresión logística multinomial sobre el dataset preparado, con
validación cruzada, métricas multiclase y chequeo de multicolinealidad (VIF).

Desde la versión 2.0 el ajuste es por máxima verosimilitud PENALIZADA (L2) con
pesos por clase (scikit-learn), no MLE pura (statsmodels.MNLogit, v1.0):

- Con la regla de Y validada, los 347 registros reales quedan muy
  desbalanceados (la categoría 2 tiene muy pocos casos). La MLE pura separa
  esa categoría y produce coeficientes enormes e inestables; la penalización
  L2 los mantiene finitos y los pesos `balanced` evitan que el modelo ignore
  las categorías pequeñas.
- No se generan registros artificiales (SMOTE u otro aumento de datos): con
  tan pocos casos solo replicarían a esos participantes e inflarían las
  métricas (PROBAST+AI, dominio "análisis").
- Las variables se estandarizan SOLO para ajustar (la penalización depende de
  la escala); `coeficientes_escala_original` deshace la estandarización y
  reexpresa el modelo con la categoría 0 como base, que es exactamente lo que
  consume PredictorService (predictor lineal + softmax sobre valores crudos).

Esquema de evaluación:
- C (inverso de la fuerza de regularización) se elige por validación cruzada
  estratificada repetida, maximizando el AUC macro uno-contra-resto fuera de
  pliegue.
- Las métricas `*_test` salen de un modelo ajustado en el 75 % y evaluado en el
  25 % reservado (mismo esquema que v1.0, para que el panel sea comparable).
- Los coeficientes exportados salen de un ajuste sobre TODOS los registros:
  con tan pocos casos de riesgo alto no se puede desperdiciar ninguno.

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
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import balanced_accuracy_score, confusion_matrix, roc_auc_score
from sklearn.model_selection import RepeatedStratifiedKFold, train_test_split
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler
from statsmodels.stats.outliers_influence import variance_inflation_factor

from comun import CATEGORIA_BASE, CATEGORIAS, ORDEN_VARIABLES

ENTRADA = Path(__file__).parent / 'datos' / 'dataset_preparado.csv'
SALIDA = Path(__file__).parent / 'datos' / 'resultado_entrenamiento.pkl'

SEMILLA = 2026
K_FOLDS = 5
REPETICIONES = 5
PROPORCION_TEST = 0.25
REJILLA_C = [0.001, 0.003, 0.01, 0.03, 0.1, 0.3, 1.0, 3.0]


def crear_modelo(c: float):
    return make_pipeline(
        StandardScaler(),
        LogisticRegression(C=c, class_weight='balanced', max_iter=5000),
    )


def ajustar(X: pd.DataFrame, y: pd.Series, c: float):
    with warnings.catch_warnings():
        warnings.simplefilter('ignore')
        return crear_modelo(c).fit(X, y)


def auc_por_categoria(y: np.ndarray, proba: np.ndarray) -> dict[str, float | None]:
    """AUC uno-contra-resto por categoría; None si la categoría no aparece
    (o es la única) en `y`, en vez de abortar como roc_auc_score multiclase."""
    resultado = {}
    for cat in CATEGORIAS:
        positivos = (y == cat).astype(int)
        if positivos.sum() == 0 or positivos.sum() == len(y):
            resultado[str(cat)] = None
        else:
            resultado[str(cat)] = float(roc_auc_score(positivos, proba[:, cat]))
    return resultado


def auc_macro(aucs: dict[str, float | None]) -> float | None:
    validos = [v for v in aucs.values() if v is not None]
    return float(np.mean(validos)) if validos else None


def predicciones_fuera_de_pliegue(X: pd.DataFrame, y: pd.Series, c: float) -> list[tuple[np.ndarray, np.ndarray]]:
    """Por cada repetición, probabilidades fuera de pliegue de los 347 registros."""
    rskf = RepeatedStratifiedKFold(n_splits=K_FOLDS, n_repeats=REPETICIONES, random_state=SEMILLA)
    repeticiones = []
    proba = np.zeros((len(y), len(CATEGORIAS)))

    with warnings.catch_warnings():
        # Avisa que la categoría 2 tiene menos casos que pliegues; es esperado.
        warnings.simplefilter('ignore')
        for i, (idx_train, idx_val) in enumerate(rskf.split(X, y)):
            modelo = ajustar(X.iloc[idx_train], y.iloc[idx_train], c)
            proba[idx_val] = probabilidades(modelo, X.iloc[idx_val])
            if (i + 1) % K_FOLDS == 0:
                repeticiones.append((y.values.copy(), proba.copy()))
                proba = np.zeros_like(proba)

    return repeticiones


def probabilidades(modelo, X: pd.DataFrame) -> np.ndarray:
    """Probabilidades en columnas 0, 1, 2 aunque el pliegue de entrenamiento
    no haya visto alguna categoría."""
    proba = np.zeros((len(X), len(CATEGORIAS)))
    proba[:, modelo.classes_] = modelo.predict_proba(X)
    return proba


def validacion_cruzada(X: pd.DataFrame, y: pd.Series, c: float) -> dict:
    exactitudes, balanceadas, aucs_macro = [], [], []
    aucs_categoria = {str(cat): [] for cat in CATEGORIAS}

    for y_rep, proba in predicciones_fuera_de_pliegue(X, y, c):
        pred = proba.argmax(axis=1)
        exactitudes.append(float((pred == y_rep).mean()))
        balanceadas.append(float(balanced_accuracy_score(y_rep, pred)))
        aucs = auc_por_categoria(y_rep, proba)
        aucs_macro.append(auc_macro(aucs))
        for cat, valor in aucs.items():
            if valor is not None:
                aucs_categoria[cat].append(valor)

    return {
        'k_folds': K_FOLDS,
        'repeticiones': REPETICIONES,
        'exactitud_promedio': float(np.mean(exactitudes)),
        'exactitud_desviacion': float(np.std(exactitudes)),
        'exactitud_balanceada_promedio': float(np.mean(balanceadas)),
        'auc_macro_ovr_promedio': float(np.mean(aucs_macro)),
        'auc_ovr_por_categoria': {
            cat: (float(np.mean(v)) if v else None) for cat, v in aucs_categoria.items()
        },
    }


def elegir_c(X: pd.DataFrame, y: pd.Series) -> tuple[float, dict]:
    print('Selección de C (validación cruzada estratificada repetida):')
    resultados = {}
    for c in REJILLA_C:
        cv = validacion_cruzada(X, y, c)
        resultados[c] = cv
        print(f'  C = {c:<5}: AUC macro = {cv["auc_macro_ovr_promedio"]:.3f} · '
              f'exactitud balanceada = {cv["exactitud_balanceada_promedio"]:.3f}')

    mejor = max(resultados, key=lambda c: resultados[c]['auc_macro_ovr_promedio'])
    print(f'  -> C elegido: {mejor}')
    return mejor, resultados[mejor]


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


def coeficientes_escala_original(modelo) -> dict:
    """Pasa el modelo ajustado a la forma que consume PredictorService.

    scikit-learn estima un vector por categoría sobre variables estandarizadas
    (z = (x - media) / desviación). Softmax no cambia si a todos los
    predictores lineales se les resta el de la categoría base, así que
    β_k - β_base da el mismo modelo con la base fija en 0; luego se deshace la
    estandarización: β_orig = β_z / desviación e intercepto_orig =
    intercepto_z - Σ β_z · media / desviación.
    """
    escalador = modelo.named_steps['standardscaler']
    regresion = modelo.named_steps['logisticregression']
    assert list(regresion.classes_) == CATEGORIAS, 'El ajuste final debe ver las tres categorías'

    base = CATEGORIAS.index(CATEGORIA_BASE)
    coeficientes = {}
    for idx, cat in enumerate(CATEGORIAS):
        if cat == CATEGORIA_BASE:
            continue
        beta_z = regresion.coef_[idx] - regresion.coef_[base]
        intercepto_z = regresion.intercept_[idx] - regresion.intercept_[base]
        beta = beta_z / escalador.scale_
        coeficientes[str(cat)] = {
            'intercepto': float(intercepto_z - np.sum(beta_z * escalador.mean_ / escalador.scale_)),
            'beta': {var: float(b) for var, b in zip(ORDEN_VARIABLES, beta)},
        }
    return coeficientes


def softmax_escala_original(coeficientes: dict, X: pd.DataFrame) -> np.ndarray:
    """Réplica de PredictorService en numpy, para verificar la conversión."""
    lineales = [np.zeros(len(X))]
    for cat in CATEGORIAS:
        if cat == CATEGORIA_BASE:
            continue
        c = coeficientes[str(cat)]
        beta = np.array([c['beta'][v] for v in ORDEN_VARIABLES])
        lineales.append(c['intercepto'] + X[ORDEN_VARIABLES].values @ beta)
    z = np.column_stack(lineales)
    z -= z.max(axis=1, keepdims=True)
    e = np.exp(z)
    return e / e.sum(axis=1, keepdims=True)


def entrenar() -> dict:
    df = pd.read_csv(ENTRADA)
    X = df[ORDEN_VARIABLES]
    y = df['categoria_riesgo'].astype(int)
    distribucion = {int(k): int(v) for k, v in y.value_counts().sort_index().items()}

    print(f'Entrenando sobre {len(df)} registros, {len(ORDEN_VARIABLES)} predictores.')
    print(f'Distribución de clases: {distribucion}\n')

    c, metricas_cv = elegir_c(X, y)

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=PROPORCION_TEST, random_state=SEMILLA, stratify=y,
    )
    modelo_eval = ajustar(X_train, y_train, c)
    proba_test = probabilidades(modelo_eval, X_test)
    pred_test = proba_test.argmax(axis=1)
    aucs_test = auc_por_categoria(y_test.values, proba_test)

    exactitud_test = float((pred_test == y_test.values).mean())
    linea_base = float((y_test.values == y_train.mode()[0]).mean())

    modelo_final = ajustar(X, y, c)
    coeficientes = coeficientes_escala_original(modelo_final)
    diferencia = np.abs(softmax_escala_original(coeficientes, X) - probabilidades(modelo_final, X)).max()
    assert diferencia < 1e-9, f'La conversión a escala original altera las probabilidades ({diferencia})'

    print('\nChequeo de multicolinealidad (VIF):')
    vif = calcular_vif(X)
    for var, valor in sorted(vif.items(), key=lambda kv: -kv[1])[:5]:
        print(f'  {var}: VIF = {valor:.2f}')
    vif_altos = {k: v for k, v in vif.items() if v > 5}
    if vif_altos:
        print(f'  ADVERTENCIA — VIF > 5 en: {list(vif_altos.keys())}')

    metricas = {
        'metodo': 'Regresión logística multinomial con penalización L2 y pesos balanceados por clase '
                  '(scikit-learn); variables estandarizadas para el ajuste y coeficientes exportados '
                  'en escala original con la categoría 0 como base.',
        'c_regularizacion': c,
        'rejilla_c': REJILLA_C,
        'distribucion_clases': distribucion,
        'validacion_cruzada': metricas_cv,
        'exactitud_test': exactitud_test,
        'exactitud_linea_base_test': linea_base,
        'exactitud_balanceada_test': float(balanced_accuracy_score(y_test, pred_test)),
        'auc_macro_ovr_test': auc_macro(aucs_test),
        'auc_ovr_por_categoria_test': aucs_test,
        'matriz_confusion_test': confusion_matrix(y_test, pred_test, labels=CATEGORIAS).tolist(),
        'categorias_matriz_confusion': CATEGORIAS,
        'calibracion_test': calcular_calibracion(y_test, proba_test),
        'vif': vif,
        'n_train': len(X_train),
        'n_test': len(X_test),
        'n_total': len(df),
        'n_predictores': len(ORDEN_VARIABLES),
        'ajuste_final': 'Coeficientes ajustados sobre los n_total registros; las métricas *_test '
                        'provienen de un ajuste sobre n_train evaluado en n_test.',
        'limitacion_epv': (
            f'{len(df)} registros con {len(ORDEN_VARIABLES)} predictores y distribución de clases '
            f'{distribucion}: la categoría 2 tiene muy pocos casos, así que su ecuación se apoya '
            'casi por completo en la regularización y sus métricas son poco estables. No se '
            'generaron registros artificiales. Se recomienda reentrenar con los datos de la '
            'prueba piloto.'
        ),
    }

    print(f'\nExactitud en test: {exactitud_test:.3f} (línea base «siempre la mayoritaria»: {linea_base:.3f})')
    print(f'Exactitud balanceada en test: {metricas["exactitud_balanceada_test"]:.3f}')
    print(f'AUC por categoría en test: {aucs_test}')
    print(f'Matriz de confusión en test: {metricas["matriz_confusion_test"]}')

    return {
        'modelo_final': modelo_final,
        'coeficientes': coeficientes,
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
