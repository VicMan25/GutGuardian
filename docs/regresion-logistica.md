# Regresión logística multinomial de GutGuardián

> **Qué es este documento.** Una revisión técnica del modelo de predicción que el aplicativo
> usa **hoy**, escrita a partir del código y de los artefactos reales del repositorio, no de
> la teoría general. Todos los coeficientes que aparecen aquí se extrajeron del modelo
> activo y se verificaron en tres fuentes (ver §2.3).
>
> **Fecha de la revisión:** 2026-10-15 · **Versión del modelo revisada:** `2.0` (id 2 en
> `versiones_modelo`, `activo = 1`), entrenada el 2026-10-15 17:52:45 UTC.
> Sustituye a la revisión del 2026-10-07, que describía la versión `1.0` (datos sintéticos).

---

## 0. Advertencia previa (léase antes que los números)

1. **El modelo activo se entrenó con los 347 registros reales** (`ml/datos/dataset_real.csv`,
   generado por `ml/etl_datos_reales.py`, ignorado por git). Los equipos de Enfermería y de
   Nutrición y Dietética revisaron la codificación numérica de las respuestas y la aprobaron.
2. **La variable objetivo se derivó con la regla aprobada** por Enfermería y Nutrición
   (`ml/comun.py::derivar_categoria_riesgo`, §3.1). La regla se aprobó tal cual estaba.
3. **Con esa regla, solo 2 de los 347 participantes quedan en riesgo alto** (298 bajo,
   47 medio, 2 alto). La ecuación de riesgo alto (§5.3) se apoya casi por completo en la
   regularización; sus coeficientes **no deben interpretarse como hallazgos**. La de riesgo
   medio (47 casos) sí es interpretable, con cautela. Los cortes del puntaje quedaron en
   consulta con Enfermería (`docs/consulta-cortes-riesgo.md`).
4. Los pesos por clase hacen que las **probabilidades mostradas sobrestimen** los niveles medio
   y alto frente a su frecuencia real (§5.4, hallazgo D10).
5. El aplicativo **no emite diagnósticos**. En este documento se usa «resultado» o «nivel de
   riesgo», no «diagnóstico» (Resolución 3100 de 2019).

---

## 1. Qué modelo usa el proyecto

| Aspecto | Implementación real | Dónde se comprueba |
|---|---|---|
| Tipo | **Regresión logística multinomial** (3 categorías), no binaria | `ml/entrenar_modelo.py::crear_modelo` |
| Librería de entrenamiento | `sklearn.linear_model.LogisticRegression` (Python, fuera de Laravel). La v1.0 usaba `statsmodels.MNLogit` | `ml/entrenar_modelo.py:61-65` |
| Función objetivo | Máxima verosimilitud **penalizada (L2)**, con **pesos balanceados por clase** (`class_weight='balanced'`) | `ml/entrenar_modelo.py:64` |
| Fuerza de la regularización | `C = 0.01`, elegido por validación cruzada estratificada 5×5 (máximo AUC macro) en la rejilla 0.001–3 | `metricas.c_regularizacion`, `ml/entrenar_modelo.py::elegir_c` |
| Intercepto | Sí (uno por categoría no base) | `coeficientes[k].intercepto` |
| Categoría de referencia (base) | `0` (riesgo bajo) | `ml/comun.py:96`, `categoria_base` en el JSON |
| Estandarización | **Solo durante el ajuste** (`StandardScaler`). Los coeficientes exportados se reexpresan en la escala original, así que la aplicación sigue recibiendo los valores crudos (0–3, 0–6, 1–5, conteos, 0/1) | `ml/entrenar_modelo.py::coeficientes_escala_original` |
| Aumento de datos | **Ninguno.** No se generan registros artificiales (SMOTE u otros) | docstring de `ml/entrenar_modelo.py` |
| Datos del ajuste final | **Los 347 registros.** Las métricas de prueba salen de otro ajuste sobre 260 registros, evaluado en 87 reservados | `metricas.ajuste_final` |
| Inferencia en producción | Aritmética pura en PHP: predictor lineal + softmax (sin cambios respecto a v1.0) | `app/Modules/Analitica/Services/PredictorService.php` |
| Regla de decisión | **Argmax** (la categoría de mayor probabilidad). **No hay umbral numérico** | `PredictorService.php:117-121` |

### 1.1 Por qué multinomial y no binaria

La variable objetivo tiene tres niveles (0, 1, 2). El modelo se expresa como **dos
ecuaciones**: una que compara la categoría 1 contra la 0 y otra que compara la 2 contra la 0.
La categoría 0 no tiene coeficientes propios: su predictor lineal vale siempre 0. Por eso el
modelo tiene:

- 2 interceptos (uno por categoría no base),
- 2 × 21 = 42 coeficientes de variables,
- **44 parámetros en total.**

scikit-learn estima internamente un vector por cada una de las tres categorías. Como softmax
no cambia si a todos los predictores lineales se les resta el de la base, el exportador resta
el vector de la categoría 0 y obtiene exactamente la forma de dos ecuaciones que consume
`PredictorService`. El script comprueba que las probabilidades no cambien (diferencia
máxima < 10⁻⁹, `assert` en `ml/entrenar_modelo.py::entrenar`).

La fórmula `P(y=1) = 1 / (1 + e^(−z))` del caso binario **no es la que usa el sistema**. La
equivalente aquí es la función softmax (§7).

### 1.2 Por qué regularización y pesos, y no MLE pura

Antes de entrenar la v2.0 se intentó un ajuste por MLE pura (el método de la v1.0) sobre los
datos reales. El resultado fue inservible:

| Síntoma | MLE pura (`MNLogit`) | v2.0 (L2 + pesos) |
|---|---|---|
| Coeficiente máximo en la ecuación de riesgo alto | ±49 (separación: 2 casos) | 0.55 |
| Matriz de diseño | Singular (`genero_otro` constante) | Variable retirada |
| Riesgo medio detectado en prueba | 4 de 12 | **8 de 12** |
| Exactitud en prueba | 0.851 | 0.816 |

Con MLE pura la exactitud (0.851) es incluso **menor** que la de clasificar a todos como
riesgo bajo (0.862). El modelo v2.0 tiene menos exactitud global, pero duplica la detección
de riesgo medio, que es para lo que sirve un tamizaje.

---

## 2. Dónde están los coeficientes

### 2.1 Cadena de custodia del modelo

```
docs/ENCUESTAS ANALISIS TABLAS .xlsx (no versionado)
ml/etl_datos_reales.py           → ml/datos/dataset_real.csv        (347 registros reales)
ml/preparar_datos.py             → ml/datos/dataset_preparado.csv   (exclusiones + Y + X)
ml/entrenar_modelo.py            → ml/datos/resultado_entrenamiento.pkl  (modelo, coeficientes + métricas)
ml/exportar_modelo.py            → storage/app/models/modelo_v2.json     (coeficientes en JSON)
/admin/modelos (ImportadorModeloService) → tabla versiones_modelo (MySQL)
PredictorService::versionActiva() → lee la fila con activo = 1 en cada predicción
```

`storage/app/models/modelo_v1.json` (datos sintéticos) se conserva como evidencia de
trazabilidad. Lo siguen usando `VersionModeloSeeder` y los tests de HU-009 y HU-026.

Los coeficientes **no están escritos en el código PHP**. En tiempo de ejecución salen de la
tabla `versiones_modelo`, columna `coeficientes` (JSON), de la única fila con `activo = 1`.
La forma de esa columna es:

```json
{
  "1": { "intercepto": -0.7882..., "beta": { "edad": -0.0144..., "...": "..." } },
  "2": { "intercepto": -6.7837..., "beta": { "edad": 0.0178..., "...": "..." } }
}
```

La columna `mapa_variables` guarda `orden_variables`, `categoria_base` (0), `categorias`
([0, 1, 2]), las etiquetas en lenguaje llano y el texto de limitaciones.

### 2.2 Cómo se cambia de modelo

Un admin sube un JSON nuevo en `/admin/modelos` (HU-026). `ImportadorModeloService` lo valida
(categorías exactamente 0/1/2, todas las variables con coeficiente, valores finitos, variables
que `PredictorService` sepa calcular) y solo entonces permite activarlo. Activar una versión
**no recalcula** las evaluaciones anteriores: las que se hicieron con la v1.0 conservan su
`version_modelo_id = 1`.

Para producir una versión nueva basta con cambiar `VERSION`, `NOMBRE` y `LIMITACIONES` en
`ml/exportar_modelo.py`; el archivo de salida toma el número de la versión.

### 2.3 Verificación hecha para este documento

| Comparación | Resultado |
|---|---|
| `versiones_modelo` (BD, versión activa) vs `modelo_v2.json` | Mismas 44 cifras; diferencia máxima 2.8 × 10⁻¹⁷ (redondeo de MySQL JSON) |
| `modelo_v2.json` vs el modelo de scikit-learn | Probabilidades idénticas en los 347 registros (diferencia < 10⁻⁹, verificado por el propio script) |
| `PredictorService` (PHP) vs réplica en Python | Diferencia máxima 1.1 × 10⁻¹⁶ en 5 vectores de prueba |
| `orden_variables` en BD vs JSON vs `ml/comun.py::ORDEN_VARIABLES` | Idénticos (21 variables) |
| Versiones registradas en BD | `1.0` (inactiva) y `2.0` (activa) |

---

## 3. Variable objetivo (Y)

| Valor | Etiqueta en la interfaz (`pista-riesgo`, `insignia-riesgo`) | Definición conceptual (CLAUDE.md §7) |
|---|---|---|
| `0` | Riesgo bajo | Ausencia o mínima presencia de síntomas |
| `1` | Riesgo medio | Síntomas recurrentes o combinación de factores de riesgo |
| `2` | Riesgo alto | Sintomatología persistente + antecedentes + patrones alimentarios de riesgo |

### 3.1 Cómo se construye Y para entrenar (regla aprobada)

Solo se aplica **durante el entrenamiento**, en Python. La aplicación no calcula Y: la
predice. Regla (`ml/comun.py:117-136`), un puntaje de 0 a 6, **aprobada sin cambios por
Enfermería y Nutrición**:

| Señal (+1 punto cada una) | Condición |
|---|---|
| Síntomas recurrentes | suma de P12 (6 síntomas, 0–3 c/u) ≥ 7 |
| Síntomas persistentes | suma de P12 ≥ 11 |
| Síntomas recientes | nº de síntomas de P11 con valor ≥ 4 (último mes o última semana) ≥ 2 |
| Dolor intenso | P13 ≥ 4 |
| Algún antecedente | nº de enfermedades de P20 ≥ 1 |
| Antecedentes múltiples | nº de enfermedades de P20 ≥ 3 |

`puntaje ≥ 5 → Y = 2`; `puntaje ≥ 3 → Y = 1`; en otro caso `Y = 0`.

**Distribución en los 347 registros reales:**

| Puntaje | 0 | 1 | 2 | 3 | 4 | 5 | 6 |
|---|---:|---:|---:|---:|---:|---:|---:|
| Participantes | 127 | 113 | 58 | 38 | 9 | 2 | 0 |
| Categoría | bajo | bajo | bajo | medio | medio | alto | alto |

Resultado: **298 bajo (85.9 %), 47 medio (13.5 %), 2 alto (0.6 %).**

**Consecuencia importante:** P11, P12, P13 y P20 se usan para fabricar Y y por eso están
**excluidas de los predictores** (evitar fuga de información, `ml/comun.py:43-49`). En
producción, **los síntomas que el estudiante reporta en P11, P12, P13 y P20 no entran en el
cálculo del nivel de riesgo.** El resultado depende solo de sociodemográficos, hábitos,
antecedentes, medicamentos y estilo de vida.

---

## 4. Variables de entrada y codificación de respuestas

### 4.1 De la respuesta al número

1. Cada opción del instrumento tiene un `valor_numerico` fijo, sembrado por
   `database/seeders/InstrumentoSeeder.php`. Enfermería y Nutrición validaron esta
   codificación y la del ETL de los datos reales.
2. Al guardar, `EncuestaController::guardarRespuesta` copia en `respuestas` el `opcion_id`
   **y** el `valor_numerico` de la opción elegida. Matriz: una fila por ítem. Selección
   múltiple: una fila por opción marcada.
3. `PredictorService::calcularVariablesDisponibles` (desde la línea 151) agrupa las respuestas
   por código de pregunta y arma las variables. No hay estandarización ni escalado en PHP.
   Calcula también `genero_otro`, pero como no está en `orden_variables` de la v2.0, no
   entra al cálculo.
4. **Respuesta ausente → 0.** Todos los extractores usan `?? 0`. En el flujo normal no ocurre
   porque la encuesta exige completar todo antes de evaluar.

### 4.2 Escalas compartidas

**Escala de frecuencia de 4 niveles** (`opcionesEscalaFrecuencia`): P01–P07, ítems de P09,
P10, P12, P17, P19.

| Respuesta | Valor enviado al modelo |
|---|---:|
| Nunca | 0 |
| Ocasionalmente (1 o más veces al mes) | 1 |
| Algunas veces (1 a 6 veces por semana) | 2 |
| Siempre (diario) | 3 |

**Escala de temporalidad** (`opcionesTemporalidad`): P11 y P16.

| Respuesta | Valor |
|---|---:|
| Nunca | 0 |
| Último año | 1 |
| Últimos 6 meses | 2 |
| Últimos 2 meses | 3 |
| Último mes | 4 |
| Última semana | 5 |

### 4.3 Las 21 variables del modelo

| # | Variable (`orden_variables`) | Pregunta | Tipo | Cómo se calcula en `PredictorService` | Rango |
|---|---|---|---|---|---|
| 1 | `edad` | SD2 Rango de edad | Ordinal tratada como numérica | `valor_numerico` de SD2: 17–18 = 1, 19–20 = 2, 21–22 = 3, 23–25 = 4, 26+ = 5 | 1–5 |
| 2 | `semestre` | SD4 Semestre | Numérica | Número del semestre | 0–10 |
| 3 | `genero_femenino` | SD1 Género | Dummy | 1 si SD1 = 2 (Femenino); si no, 0. **Referencia: Masculino** | 0/1 |
| 4 | `p01_frutas` | P01 | Ordinal | Escala de frecuencia | 0–3 |
| 5 | `p02_ensaladas` | P02 | Ordinal | Escala de frecuencia | 0–3 |
| 6 | `p03_integrales` | P03 | Ordinal | Escala de frecuencia | 0–3 |
| 7 | `p04_embutidos` | P04 | Ordinal | Escala de frecuencia | 0–3 |
| 8 | `p05_empaquetados` | P05 | Ordinal | Escala de frecuencia | 0–3 |
| 9 | `p06_azucares` | P06 | Ordinal | Escala de frecuencia | 0–3 |
| 10 | `p07_frituras` | P07 | Ordinal | Escala de frecuencia | 0–3 |
| 11 | `p08_tiempos_comida` | P08 | Ordinal | Ninguna = 0, 1 tiempo = 1 … 5 tiempos = 5, Más de 5 = 6 | 0–6 |
| 12 | `p09_alcohol` | P09, ítem «Alcohol» | Ordinal | Escala de frecuencia del ítem | 0–3 |
| 13 | `p09_tabaco` | P09, ítem «Tabaco» | Ordinal | Escala de frecuencia del ítem | 0–3 |
| 14 | `p10_lavado_manos` | P10 | Ordinal | Escala de frecuencia | 0–3 |
| 15 | `p14_antecedentes_personales_count` | P14 | Conteo | Suma de `valor_numerico` de las opciones marcadas (cada condición = 1, «Ninguna» = 0) | 0–8 |
| 16 | `p15_antecedentes_familiares_count` | P15 | Conteo | Igual que P14 | 0–8 |
| 17 | `p16_medicamentos_recientes_count` | P16 (7 medicamentos) | Conteo | Nº de medicamentos con temporalidad ≥ 4 (último mes o última semana). Constante `RECIENCIA_ALTA = 4` | 0–7 |
| 18 | `p17_medicamentos_frecuentes_count` | P17 (7 medicamentos) | Conteo | Nº de medicamentos con frecuencia ≥ 2 (algunas veces o siempre). Constante `FRECUENCIA_ALTA = 2` | 0–7 |
| 19 | `p18_practica_deporte` | P18 | Binaria | 1 si se marcó «Practica deporte» | 0/1 |
| 20 | `p18_factores_riesgo_count` | P18 | Conteo | Nº de opciones marcadas entre «Sobrecarga académica», «Estrés psicológico», «Sedentarismo» | 0–3 |
| 21 | `p19_frecuencia_deporte` | P19 | Ordinal | Escala de frecuencia | 0–3 |

**Preguntas que se recogen pero NO entran al modelo:** SD3 (programa académico, excluido para
no inflar el modelo con 8 categorías), P11, P12, P13 y P20 (usadas para construir Y, §3.1).

**Variable retirada en la v2.0:** `genero_otro`. Ningún participante real marcó «No binario /
Otro», así que la columna es constante y su coeficiente no se puede estimar. En la práctica,
un estudiante que marque «Otro» recibe el mismo tratamiento que la categoría de referencia
(masculino).

**Supuesto de linealidad.** Las variables ordinales entran como un único número, no como
categorías separadas. El modelo asume que pasar de «Nunca» a «Ocasionalmente» tiene el mismo
efecto que pasar de «Algunas veces» a «Siempre». Lo mismo en P08: cada tiempo de comida
adicional pesa igual, de 0 a 6.

---

## 5. Coeficientes reales e interceptos

Valores redondeados a 4 decimales; la precisión completa está en la §12 y en el JSON.
**No hay valores p:** en una estimación penalizada los errores estándar de Wald no son
válidos, así que no se reportan. Para medir incertidumbre haría falta un bootstrap (§13).

### 5.1 Interceptos (β₀)

| Ecuación | Intercepto β₀ | e^β₀ | Origen |
|---|---:|---:|---|
| Riesgo medio (1) vs bajo (0) | **−0.7882408686377027** | 0.4546 | `coeficientes["1"]["intercepto"]` |
| Riesgo alto (2) vs bajo (0) | **−6.7837911137700315** | 0.0011 | `coeficientes["2"]["intercepto"]` |
| Riesgo bajo (0) | no tiene (base, η₀ = 0) | — | `categoria_base = 0` |

**Cómo participa.** Es el punto de partida de cada predictor lineal:
`η₁ = β₀⁽¹⁾ + Σ βⱼ⁽¹⁾·xⱼ` (`PredictorService` inicia `$eta = $coef['intercepto']` y luego
suma los términos). Representa el log-odds de la categoría frente a riesgo bajo cuando
**todas** las variables valen 0, un perfil que no existe (la edad nunca vale 0). El
intercepto de riesgo medio (≈ −0.79) es mucho menos negativo que en una estimación sin pesos,
porque `class_weight='balanced'` le da a cada categoría el mismo peso total (ver D10). El
intercepto de riesgo alto (≈ −6.78) refleja que esa categoría casi no aparece.

### 5.2 Ecuación 1: riesgo medio vs riesgo bajo (47 frente a 298 casos)

| Pregunta / variable | Identificador en código | Tipo | Codificación | Coeficiente β⁽¹⁾ | OR = e^β | Interpretación |
|---|---|---|---|---:|---:|---|
| Rango de edad | `edad` | Ordinal | 1–5 | −0.0145 | 0.9856 | Prácticamente nulo |
| Semestre | `semestre` | Numérica | 0–10 | 0.0077 | 1.0077 | Prácticamente nulo |
| Género femenino | `genero_femenino` | Dummy | 0/1 | 0.5225 | 1.6863 | Frente a masculino: ×1.69. El efecto más fuerte de la ecuación |
| Consumo de frutas | `p01_frutas` | Ordinal | 0–3 | −0.1212 | 0.8858 | ×0.89 por nivel (protector) |
| Consumo de ensaladas | `p02_ensaladas` | Ordinal | 0–3 | −0.1899 | 0.8270 | ×0.83 por nivel (protector) |
| Consumo de integrales | `p03_integrales` | Ordinal | 0–3 | −0.0164 | 0.9837 | Prácticamente nulo |
| Consumo de embutidos | `p04_embutidos` | Ordinal | 0–3 | 0.0729 | 1.0756 | ×1.08 por nivel; débil |
| Consumo de empaquetados | `p05_empaquetados` | Ordinal | 0–3 | −0.0087 | 0.9914 | Prácticamente nulo |
| Consumo de azúcares refinados | `p06_azucares` | Ordinal | 0–3 | 0.1132 | 1.1199 | ×1.12 por nivel |
| Consumo de frituras | `p07_frituras` | Ordinal | 0–3 | −0.1130 | 0.8932 | ×0.89 por nivel (signo contrario al esperado; ver D7) |
| Tiempos de comida al día | `p08_tiempos_comida` | Ordinal | 0–6 | −0.2100 | 0.8106 | Cada tiempo de comida más: ×0.81 (protector) |
| Consumo de alcohol | `p09_alcohol` | Ordinal | 0–3 | 0.1218 | 1.1295 | ×1.13 por nivel |
| Consumo de tabaco | `p09_tabaco` | Ordinal | 0–3 | 0.0869 | 1.0908 | ×1.09 por nivel |
| Lavado de manos | `p10_lavado_manos` | Ordinal | 0–3 | 0.0671 | 1.0694 | ×1.07; débil (signo contrario al esperado) |
| Antecedentes personales | `p14_antecedentes_personales_count` | Conteo | 0–8 | 0.2488 | 1.2825 | Cada antecedente más: ×1.28 |
| Antecedentes familiares | `p15_antecedentes_familiares_count` | Conteo | 0–8 | 0.1021 | 1.1075 | ×1.11 por antecedente |
| Medicamentos recientes | `p16_medicamentos_recientes_count` | Conteo | 0–7 | 0.2039 | 1.2262 | ×1.23 por medicamento |
| Medicamentos frecuentes | `p17_medicamentos_frecuentes_count` | Conteo | 0–7 | 0.2833 | 1.3275 | ×1.33 por medicamento |
| Practica deporte | `p18_practica_deporte` | Binaria | 0/1 | 0.0424 | 1.0434 | Prácticamente nulo |
| Factores de estilo de vida de riesgo | `p18_factores_riesgo_count` | Conteo | 0–3 | 0.2288 | 1.2571 | Cada factor más: ×1.26 |
| Frecuencia de deporte | `p19_frecuencia_deporte` | Ordinal | 0–3 | 0.0562 | 1.0578 | ×1.06; débil |

Lectura de conjunto: lo que más separa a riesgo medio de riesgo bajo en estos datos es el
**género femenino**, seguido de los **medicamentos** (frecuentes y recientes), los
**antecedentes personales** y los **factores de estilo de vida** (sobrecarga, estrés,
sedentarismo). Los hábitos alimentarios tienen efectos pequeños. La regularización encoge
todos los coeficientes hacia 0, así que los OR son conservadores.

### 5.3 Ecuación 2: riesgo alto vs riesgo bajo (2 frente a 298 casos)

> **No interpretar.** Esta ecuación se estimó con 2 casos. Varios signos contradicen lo
> esperado (frutas, ensaladas y lavado de manos aumentan el riesgo; embutidos y alcohol lo
> disminuyen): reflejan el perfil de esos dos participantes, no un patrón. Se reporta solo por
> transparencia y porque la aplicación la usa en el cálculo.

| Pregunta / variable | Identificador en código | Codificación | Coeficiente β⁽²⁾ | OR = e^β |
|---|---|---|---:|---:|
| Rango de edad | `edad` | 1–5 | 0.0178 | 1.0180 |
| Semestre | `semestre` | 0–10 | 0.0020 | 1.0020 |
| Género femenino | `genero_femenino` | 0/1 | 0.3978 | 1.4886 |
| Consumo de frutas | `p01_frutas` | 0–3 | 0.3733 | 1.4525 |
| Consumo de ensaladas | `p02_ensaladas` | 0–3 | 0.1583 | 1.1715 |
| Consumo de integrales | `p03_integrales` | 0–3 | 0.1070 | 1.1130 |
| Consumo de embutidos | `p04_embutidos` | 0–3 | −0.2774 | 0.7578 |
| Consumo de empaquetados | `p05_empaquetados` | 0–3 | −0.0310 | 0.9695 |
| Consumo de azúcares refinados | `p06_azucares` | 0–3 | 0.4600 | 1.5841 |
| Consumo de frituras | `p07_frituras` | 0–3 | 0.0215 | 1.0218 |
| Tiempos de comida al día | `p08_tiempos_comida` | 0–6 | 0.3492 | 1.4180 |
| Consumo de alcohol | `p09_alcohol` | 0–3 | −0.5540 | 0.5746 |
| Consumo de tabaco | `p09_tabaco` | 0–3 | −0.1147 | 0.8916 |
| Lavado de manos | `p10_lavado_manos` | 0–3 | 0.2946 | 1.3426 |
| Antecedentes personales | `p14_antecedentes_personales_count` | 0–8 | 0.1396 | 1.1498 |
| Antecedentes familiares | `p15_antecedentes_familiares_count` | 0–8 | 0.0997 | 1.1048 |
| Medicamentos recientes | `p16_medicamentos_recientes_count` | 0–7 | 0.3939 | 1.4828 |
| Medicamentos frecuentes | `p17_medicamentos_frecuentes_count` | 0–7 | 0.4258 | 1.5308 |
| Practica deporte | `p18_practica_deporte` | 0/1 | −0.2717 | 0.7621 |
| Factores de estilo de vida de riesgo | `p18_factores_riesgo_count` | 0–3 | 0.4133 | 1.5119 |
| Frecuencia de deporte | `p19_frecuencia_deporte` | 0–3 | −0.0605 | 0.9413 |

### 5.4 Métricas del modelo activo

Tomadas de `metricas` en el JSON / BD.

**Validación cruzada estratificada repetida (5 pliegues × 5 repeticiones, 347 registros):**

| Métrica | Valor |
|---|---:|
| AUC uno-contra-resto, riesgo bajo | 0.782 |
| AUC uno-contra-resto, riesgo medio | 0.761 |
| AUC uno-contra-resto, riesgo alto | 0.897 (2 casos: **no confiable**) |
| AUC macro | 0.813 |
| Exactitud | 0.769 (DE 0.008) |
| Exactitud balanceada | 0.474 |

**Conjunto de prueba (ajuste sobre 260, evaluación sobre 87):**

| Métrica | Valor |
|---|---:|
| Exactitud | 0.816 |
| Línea base (clasificar a todos como riesgo bajo) | 0.862 |
| Exactitud balanceada | 0.753 |
| AUC riesgo bajo / medio | 0.817 / 0.750 |
| AUC riesgo alto | no calculable (la prueba no tiene casos de riesgo alto) |
| AUC macro (sobre las categorías presentes) | 0.783 |

Matriz de confusión en prueba (filas = real 0/1/2, columnas = predicho 0/1/2):
`[[63, 12, 0], [3, 8, 1], [0, 0, 0]]`. Detecta 8 de 12 casos de riesgo medio, a costa de
marcar como riesgo medio a 12 de 75 estudiantes de riesgo bajo.

Otros datos:

- **Exactitud menor que la línea base.** Es esperado y deliberado: los pesos por clase
  cambian aciertos fáciles en riesgo bajo por detecciones en riesgo medio. En tamizaje, un
  falso «riesgo medio» (que invita a revisar hábitos) cuesta menos que un caso de riesgo medio
  no detectado.
- **Exactitud balanceada en validación cruzada (0.474) frente a prueba (0.753).** La primera
  promedia las tres categorías e incluye la de riesgo alto, que casi nunca se acierta. En la
  prueba no hay riesgo alto, así que el promedio es solo sobre bajo y medio.
- **Probabilidades sobrestimadas** (ver D10). Promedio de las probabilidades que da el modelo
  en los 347 registros: P(bajo) 0.55, P(medio) 0.37, P(alto) 0.08, frente a frecuencias
  reales de 0.86, 0.14 y 0.006. Clasificados por argmax: 247 bajo, 96 medio, 4 alto.
- VIF: todos entre 1.11 y 1.63 (sin multicolinealidad relevante).
- Registros: 347 reales, 0 excluidos por `preparar_datos.py`.

---

## 6. Cómo interpretar un coeficiente en este modelo

1. **Un coeficiente compara contra riesgo bajo, siempre.** β⁽¹⁾ cambia el log-odds de
   `P(medio) / P(bajo)`; β⁽²⁾ cambia el de `P(alto) / P(bajo)`. No hay coeficiente que
   compare directamente medio contra alto (se obtiene restando: β⁽²⁾ − β⁽¹⁾).
2. **Signo.**
   - β > 0: subir la variable una unidad aumenta el log-odds de esa categoría frente a riesgo bajo.
   - β < 0: lo disminuye (efecto protector).
   - β cercano a 0 (|β| < 0.1, OR entre 0.9 y 1.1): influencia pequeña.
3. **Odds ratio (OR = e^β), aquí técnicamente un *relative risk ratio*.** Es el factor por el
   que se multiplica el cociente `P(k)/P(0)` por **cada unidad** de la variable. Ejemplo:
   `p17_medicamentos_frecuentes_count` en la ecuación 1, OR = 1.33. Cada medicamento de
   consumo frecuente adicional multiplica por 1.33 el cociente medio/bajo; tres medicamentos
   lo multiplican por 1.33³ ≈ 2.3, con todo lo demás igual.
4. **Un coeficiente NO es un porcentaje de probabilidad.** OR = 1.33 no quiere decir «1.33
   veces más probable» ni «+33 %». Multiplica un cociente de odds. Cuánto cambia la
   probabilidad final depende de todas las demás respuestas, porque las tres probabilidades
   se reparten a través del softmax.
5. **La unidad importa.** Un β de un conteo (0–8) y uno de una dummy (0/1) no se comparan
   directamente. Lo que pesa en un caso concreto es el aporte `β·x`, que es lo que ordena el
   `ExplicabilidadService`.
6. **Regularización.** La penalización L2 encoge los coeficientes hacia 0 para evitar el
   sobreajuste. Por eso los OR de la v2.0 son más cercanos a 1 que los de la v1.0: son
   estimaciones conservadoras, no efectos «débiles» en sentido clínico.
7. **Asociación, no causa.** Los datos son transversales. Un OR mayor que 1 indica que la
   variable acompaña al nivel de riesgo en estos 347 estudiantes, no que lo provoque.

---

## 7. Flujo completo: de la respuesta al resultado

```
Respuesta del usuario (formulario Blade)
  → EncuestaController::guardarRespuesta → respuestas.opcion_id + respuestas.valor_numerico
  → al abrir /resultado: ResultadoController → EvaluacionService::evaluar
      → PredictorService::versionActiva()          (fila activa de versiones_modelo)
      → PredictorService::armarVector()            (21 variables, en orden_variables)
      → PredictorService::predecirDesdeVector()
            η₀ = 0
            η₁ = β₀⁽¹⁾ + Σ βⱼ⁽¹⁾ xⱼ
            η₂ = β₀⁽²⁾ + Σ βⱼ⁽²⁾ xⱼ
            softmax → P(0), P(1), P(2)
            argmax  → categoría
      → ExplicabilidadService::explicar()          (top 5 aportes β·x, OR, frase)
      → INSERT evaluaciones_riesgo (categoria, prob_0..2, contribuciones, version_modelo_id)
      → AlertaService::generarSiCambioDeRiesgo     (HU-012)
  → vista: x-pista-riesgo (nivel, % por nivel, factores) + x-aviso-no-diagnostico
```

### 7.1 Fórmulas exactas implementadas

Predictor lineal por categoría (`PredictorService::predecirDesdeVector`):

```
η₀ = 0                                         (categoría base)
η₁ = β₀⁽¹⁾ + β₁⁽¹⁾·x₁ + β₂⁽¹⁾·x₂ + … + β₂₁⁽¹⁾·x₂₁
η₂ = β₀⁽²⁾ + β₁⁽²⁾·x₁ + β₂⁽²⁾·x₂ + … + β₂₁⁽²⁾·x₂₁
```

Softmax (`PredictorService::softmax`, línea 132):

```
P(Y = k) = e^ηₖ / (e^η₀ + e^η₁ + e^η₂)        k ∈ {0, 1, 2}

equivalente, como η₀ = 0:
P(Y = 0) = 1      / (1 + e^η₁ + e^η₂)
P(Y = 1) = e^η₁  / (1 + e^η₁ + e^η₂)
P(Y = 2) = e^η₂  / (1 + e^η₁ + e^η₂)
```

Para estabilidad numérica el código resta `max(η)` antes de exponenciar. El resultado es el
mismo; solo evita desbordamientos.

Relación con la logística binaria: si solo existieran las categorías 0 y 1, la fórmula
anterior se reduce a `P(Y=1) = 1 / (1 + e^(−η₁))`, la sigmoide clásica. La multinomial es su
generalización a tres clases.

**Conversión de la escala estandarizada a la original** (solo en el entrenamiento,
`ml/entrenar_modelo.py::coeficientes_escala_original`). Si scikit-learn estimó βᶻ sobre
`z = (x − media) / desviación`, entonces:

```
β_original    = βᶻ / desviación
β₀_original   = β₀ᶻ − Σ βᶻ · media / desviación
```

Así la aplicación puede seguir multiplicando los coeficientes directamente por las respuestas
crudas.

---

## 8. Del cálculo al resultado: regla de decisión

**No hay umbrales de probabilidad.** El código (`PredictorService.php:117-121`):

```php
$categoriaGanadora = array_key_first(array_filter(
    $probabilidades,
    fn ($p) => $p === max($probabilidades)
));
```

| Condición | Resultado mostrado |
|---|---|
| P(0) es la mayor de las tres | **Riesgo bajo** (`categoria = 0`) |
| P(1) es la mayor | **Riesgo medio** (`categoria = 1`) |
| P(2) es la mayor | **Riesgo alto** (`categoria = 2`) |
| Empate exacto en el máximo | Gana la categoría **más baja** (`array_key_first`) |

Consecuencias:

- Un resultado puede ser «Riesgo medio» con apenas un 34 % de probabilidad, si las otras dos
  quedan por debajo. Por eso la interfaz muestra las tres probabilidades
  (`components/pista-riesgo.blade.php`, redondeadas a 1 decimal) y no solo la etiqueta.
- En la v2.0 los pesos por clase ya desplazan la decisión hacia riesgo medio (D10). Agregar un
  umbral además sería un segundo ajuste sobre lo mismo.
- Los únicos «umbrales» del sistema están en la **construcción de variables**, no en la
  decisión: `RECIENCIA_ALTA = 4` y `FRECUENCIA_ALTA = 2` (`PredictorService.php:23,26`, con
  sus gemelos en `ml/comun.py:104` y `ml/comun.py:160`).

**Cómo se modificaría** (no se ha tocado; solo se indica dónde):

- Para usar umbrales en vez de argmax (p. ej. «alto si P(2) ≥ 0.40»), habría que cambiar
  `PredictorService::predecirDesdeVector` y documentar el criterio clínico en la monografía.
- Los umbrales de construcción de variables deben cambiarse **a la vez** en
  `PredictorService.php` y en `ml/comun.py`, y luego reentrenar. Si se cambian solo en un lado,
  los coeficientes dejan de corresponder al vector que reciben.

### 8.1 Explicabilidad que ve el estudiante

`ExplicabilidadService::explicar`:

- Toma los coeficientes de la categoría predicha. Si la categoría predicha es la base (0), que
  no tiene coeficientes propios, usa los de la categoría 1 (`ExplicabilidadService.php:33-35`).
  Con la v2.0 eso es una ventaja: la ecuación de la categoría 1 es la única interpretable.
- Calcula el aporte `β·x` de cada variable y ordena por `|β·x|`.
- Muestra las 5 primeras (`TOP_N = 5`) con su OR `e^β` redondeado a 2 decimales y una frase:
  «X multiplica por OR la probabilidad relativa de clasificarse en Riesgo …». «Probabilidad
  relativa» quiere decir el cociente frente a riesgo bajo, y el OR es por unidad de la
  variable, no por el valor que respondió el estudiante.
- **Riesgo alto:** si un estudiante sale en riesgo alto, la explicación usa la ecuación 2, que
  no es interpretable (§5.3). Ver D11.

---

## 9. Ejemplo completo de cálculo

Se calculó a mano y se comprobó con el propio servicio (`PredictorService::predecirDesdeVector`
y `ExplicabilidadService::explicar` sobre la versión activa, vía `php artisan tinker`). Ambos
dan el mismo resultado. Es el mismo perfil ficticio de la revisión anterior, para poder
comparar.

### 9.1 Respuestas del estudiante (perfil ficticio) y conversión

| Pregunta | Respuesta | Variable | x |
|---|---|---|---:|
| SD1 | Femenino (valor 2) | `genero_femenino` | 1 |
| SD2 | 21 a 22 años | `edad` | 3 |
| SD4 | Semestre 5 | `semestre` | 5 |
| P01 | Ocasionalmente | `p01_frutas` | 1 |
| P02 | Ocasionalmente | `p02_ensaladas` | 1 |
| P03 | Nunca | `p03_integrales` | 0 |
| P04 | Algunas veces | `p04_embutidos` | 2 |
| P05 | Algunas veces | `p05_empaquetados` | 2 |
| P06 | Siempre | `p06_azucares` | 3 |
| P07 | Algunas veces | `p07_frituras` | 2 |
| P08 | 3 tiempos | `p08_tiempos_comida` | 3 |
| P09 | Alcohol: Ocasionalmente · Tabaco: Nunca | `p09_alcohol` / `p09_tabaco` | 1 / 0 |
| P10 | Algunas veces | `p10_lavado_manos` | 2 |
| P14 | Estrés, Gastroenteritis | `p14_antecedentes_personales_count` | 2 |
| P15 | Ninguna | `p15_antecedentes_familiares_count` | 0 |
| P16 | Antiinflamatorios: Último mes (4); Antibióticos: Últimos 6 meses (2); demás: Nunca | `p16_medicamentos_recientes_count` (cuenta ≥ 4) | 1 |
| P17 | Antiinflamatorios: Algunas veces (2); demás: Nunca | `p17_medicamentos_frecuentes_count` (cuenta ≥ 2) | 1 |
| P18 | Sobrecarga académica, Estrés psicológico | `p18_practica_deporte` / `p18_factores_riesgo_count` | 0 / 2 |
| P19 | Ocasionalmente | `p19_frecuencia_deporte` | 1 |
| P11, P12, P13, P20 | (lo que sea) | — | no entran al modelo |

### 9.2 Aplicación de coeficientes (β·x)

| Variable | x | β⁽¹⁾ | β⁽¹⁾·x | β⁽²⁾ | β⁽²⁾·x |
|---|---:|---:|---:|---:|---:|
| **Intercepto** | — | −0.7882 | **−0.7882** | −6.7838 | **−6.7838** |
| `edad` | 3 | −0.0145 | −0.0434 | 0.0178 | 0.0534 |
| `semestre` | 5 | 0.0077 | 0.0386 | 0.0020 | 0.0099 |
| `genero_femenino` | 1 | 0.5225 | 0.5225 | 0.3978 | 0.3978 |
| `p01_frutas` | 1 | −0.1212 | −0.1212 | 0.3733 | 0.3733 |
| `p02_ensaladas` | 1 | −0.1899 | −0.1899 | 0.1583 | 0.1583 |
| `p03_integrales` | 0 | −0.0164 | 0 | 0.1070 | 0 |
| `p04_embutidos` | 2 | 0.0729 | 0.1457 | −0.2774 | −0.5547 |
| `p05_empaquetados` | 2 | −0.0087 | −0.0174 | −0.0310 | −0.0620 |
| `p06_azucares` | 3 | 0.1132 | 0.3396 | 0.4600 | 1.3801 |
| `p07_frituras` | 2 | −0.1130 | −0.2259 | 0.0215 | 0.0430 |
| `p08_tiempos_comida` | 3 | −0.2100 | −0.6300 | 0.3492 | 1.0477 |
| `p09_alcohol` | 1 | 0.1218 | 0.1218 | −0.5540 | −0.5540 |
| `p09_tabaco` | 0 | 0.0869 | 0 | −0.1147 | 0 |
| `p10_lavado_manos` | 2 | 0.0671 | 0.1341 | 0.2946 | 0.5892 |
| `p14_antecedentes_personales_count` | 2 | 0.2488 | 0.4976 | 0.1396 | 0.2792 |
| `p15_antecedentes_familiares_count` | 0 | 0.1021 | 0 | 0.0997 | 0 |
| `p16_medicamentos_recientes_count` | 1 | 0.2039 | 0.2039 | 0.3939 | 0.3939 |
| `p17_medicamentos_frecuentes_count` | 1 | 0.2833 | 0.2833 | 0.4258 | 0.4258 |
| `p18_practica_deporte` | 0 | 0.0424 | 0 | −0.2717 | 0 |
| `p18_factores_riesgo_count` | 2 | 0.2288 | 0.4576 | 0.4133 | 0.8267 |
| `p19_frecuencia_deporte` | 1 | 0.0562 | 0.0562 | −0.0605 | −0.0605 |
| **Suma de términos sin intercepto** | | | 1.5731 | | 4.7472 |
| **Predictor lineal η** | | | **η₁ = 0.784874** | | **η₂ = −2.036632** |

### 9.3 Softmax

```
η₀ = 0           → e^η₀ = 1
η₁ = 0.784874    → e^η₁ = 2.19213
η₂ = −2.036632   → e^η₂ = 0.13047
Denominador = 1 + 2.19213 + 0.13047 = 3.32260

P(0) = 1       / 3.32260 = 0.30097  → 30.1 %
P(1) = 2.19213 / 3.32260 = 0.65976  → 66.0 %
P(2) = 0.13047 / 3.32260 = 0.03927  →  3.9 %
                         suma = 1.00000
```

Salida exacta del servicio:
`{"categoria":1,"probabilidades":[0.3009693663096414, 0.6597639632530381, 0.03926667043732059]}`.

### 9.4 Decisión y explicación

- Argmax: la mayor es P(1) = 0.660, así que el resultado es **Riesgo medio**
  (`evaluaciones_riesgo.categoria = 1`). Con la v1.0 este mismo perfil también salía en riesgo
  medio (57.8 %).
- Top 5 de `ExplicabilidadService` (coeficientes de la categoría 1, orden por |β·x|):
  1. Tiempos de comida al día: aporte −0.6300, OR 0.81 (reduce)
  2. Género femenino: 0.5225, OR 1.69
  3. Cantidad de antecedentes personales: 0.4976, OR 1.28
  4. Factores de estilo de vida de riesgo: 0.4576, OR 1.26
  5. Consumo de azúcares refinados: 0.3396, OR 1.12
- Junto al resultado se muestra siempre `x-aviso-no-diagnostico`.

El primer factor de la lista **reduce** el riesgo: el servicio ordena por magnitud del aporte,
sin importar el signo, y la frase lo dice («reduce (×0.8) la probabilidad relativa…»).

---

## 10. Cómo reproducir una predicción a mano

1. Obtener los coeficientes de la versión activa (BD) o de `storage/app/models/modelo_v2.json`.
2. Convertir cada respuesta con las tablas de §4 (ojo con los conteos de P14–P18 y el dummy
   de género).
3. Calcular `η₁` y `η₂` sumando el intercepto y cada `β·x` (§7.1).
4. Aplicar softmax con `η₀ = 0`.
5. Elegir la categoría de mayor probabilidad (en caso de empate, la menor).

En código, sin pasar por la encuesta:

```php
// php artisan tinker
$v = App\Modules\Analitica\Models\VersionModelo::where('activo', true)->first();
$x = ['edad' => 3, 'semestre' => 5, /* … las 21 variables … */];
app(App\Modules\Analitica\Services\PredictorService::class)->predecirDesdeVector($x, $v);
```

---

## 11. Validación de consistencia y discrepancias encontradas

Se comprobó:

- [x] Las 21 variables documentadas son exactamente `orden_variables` de la versión activa y
      están entre las que construye `PredictorService::calcularVariablesDisponibles`.
- [x] Coeficientes e interceptos: BD = JSON = modelo de scikit-learn (§2.3).
- [x] Codificación: validada por Enfermería y Nutrición; `InstrumentoSeeder` = lo que espera
      `ml/comun.py::construir_predictores` (mismos umbrales 4 y 2).
- [x] Regla de decisión: argmax, sin umbral (código y ejemplo ejecutado).
- [x] Resultado del ejemplo: cálculo manual = salida del servicio.

Hallazgos que el equipo debe conocer:

| # | Hallazgo | Detalle | Impacto |
|---|---|---|---|
| D1 | ~~Modelo sintético en producción~~ | **Resuelto en v2.0**: la versión activa se entrenó con los 347 registros reales. | — |
| D2 | Los síntomas no influyen en el resultado | P11, P12, P13 y P20 se excluyen de X por diseño (definen Y). Un estudiante con síntomas intensos pero sin factores de riesgo puede salir en «Riesgo bajo». | Conviene explicarlo en la monografía y en la interfaz: el modelo estima riesgo por **factores**, no por la sintomatología actual. |
| D3 | Cifras de CLAUDE.md §7 | La v2.0 usa 21 predictores y 347 registros sin exclusiones; el ajuste final usa los 347. | Usar estas cifras en la monografía. |
| D4 | Sin umbrales clínicos de decisión | La decisión es argmax. | Los pesos por clase ya la desplazan hacia riesgo medio (D10). |
| D5 | Ordinales como lineales | Cada nivel de escala pesa lo mismo; P08 lineal de 0 a 6 («Más de 5» = 6). | Supuesto de modelado que debe declararse. |
| D6 | Respuesta faltante = 0 | `?? 0` en todos los extractores. | Hoy no ocurre (la encuesta se exige completa), pero sería silencioso si ocurriera. |
| D7 | Signos inesperados en la ecuación 1 | `p07_frituras` (−0.11) y `p10_lavado_manos` (+0.07) tienen el signo contrario al esperado, aunque con efectos pequeños. | Puede ser ruido, o reflejar que quien ya tiene molestias cambia sus hábitos (causalidad inversa, propia de un diseño transversal). Discutirlo en la monografía. |
| D8 | Evaluaciones no se recalculan | Al activar la v2.0, los resultados hechos con la v1.0 conservan `version_modelo_id = 1`. | Correcto para trazabilidad TRIPOD+AI. Las evaluaciones de prueba hechas con la v1.0 siguen mostrando su resultado anterior. |
| D9 | Frase de explicabilidad | El OR que se muestra es por **una unidad** de la variable y compara contra riesgo bajo. | La frase de la interfaz habla de «probabilidad relativa», que es correcto, pero puede leerse como probabilidad absoluta. |
| D10 | **Probabilidades sobrestimadas** | `class_weight='balanced'` iguala el peso total de cada categoría. Eso mejora la detección de riesgo medio, pero desplaza los interceptos: en promedio el modelo da P(medio) 0.37 y P(alto) 0.08, frente a frecuencias reales de 0.14 y 0.006. | Los porcentajes de `x-pista-riesgo` no son frecuencias poblacionales. Hay que declararlo en la monografía. Se podría recalibrar el intercepto, pero entonces casi todos saldrían en riesgo bajo: es una decisión de diseño que corresponde al equipo. |
| D11 | Riesgo alto apoyado en 2 casos | La ecuación 2 no es interpretable (§5.3) y su AUC en validación (0.897) se calcula sobre 2 positivos. | Si un estudiante sale en riesgo alto, los factores que se le muestran salen de esa ecuación. Mitigación: recalibrar los cortes con Enfermería o reentrenar con los datos del piloto (§13). |
| D12 | `genero_otro` retirado | Constante en los datos reales. | Quien marque «Otro» se calcula como la referencia (masculino). Declararlo. |

---

## 12. Tabla final de referencia (valores reales del modelo activo v2.0)

Cada variable tiene **dos** coeficientes: uno por ecuación (medio vs bajo, alto vs bajo).
Dirección: ↑ aumenta el log-odds de esa categoría frente a riesgo bajo; ↓ lo disminuye.
**La columna de riesgo alto no es interpretable (2 casos, §5.3).**

| # | Pregunta / variable | Código | Valor de entrada | β medio (1) | OR medio | Dir. | β alto (2) | OR alto | Dir. |
|---|---|---|---|---:|---:|:-:|---:|---:|:-:|
| 0 | **Intercepto** | `const` | 1 (siempre) | −0.788241 | 0.4546 | — | −6.783791 | 0.0011 | — |
| 1 | Rango de edad | `edad` | 1–5 | −0.014466 | 0.9856 | ↓ | 0.017816 | 1.0180 | ↑ |
| 2 | Semestre | `semestre` | 0–10 | 0.007716 | 1.0077 | ↑ | 0.001971 | 1.0020 | ↑ |
| 3 | Género femenino (ref. masculino) | `genero_femenino` | 0/1 | 0.522528 | 1.6863 | ↑ | 0.397845 | 1.4886 | ↑ |
| 4 | P01 Frutas | `p01_frutas` | 0–3 | −0.121221 | 0.8858 | ↓ | 0.373291 | 1.4525 | ↑ |
| 5 | P02 Ensaladas frescas | `p02_ensaladas` | 0–3 | −0.189943 | 0.8270 | ↓ | 0.158305 | 1.1715 | ↑ |
| 6 | P03 Integrales | `p03_integrales` | 0–3 | −0.016423 | 0.9837 | ↓ | 0.107040 | 1.1130 | ↑ |
| 7 | P04 Embutidos | `p04_embutidos` | 0–3 | 0.072852 | 1.0756 | ↑ | −0.277367 | 0.7578 | ↓ |
| 8 | P05 Empaquetados | `p05_empaquetados` | 0–3 | −0.008682 | 0.9914 | ↓ | −0.030994 | 0.9695 | ↓ |
| 9 | P06 Azúcares refinados | `p06_azucares` | 0–3 | 0.113196 | 1.1199 | ↑ | 0.460042 | 1.5841 | ↑ |
| 10 | P07 Frituras | `p07_frituras` | 0–3 | −0.112952 | 0.8932 | ↓ | 0.021517 | 1.0218 | ↑ |
| 11 | P08 Tiempos de comida | `p08_tiempos_comida` | 0–6 | −0.209996 | 0.8106 | ↓ | 0.349231 | 1.4180 | ↑ |
| 12 | P09 Alcohol | `p09_alcohol` | 0–3 | 0.121782 | 1.1295 | ↑ | −0.554046 | 0.5746 | ↓ |
| 13 | P09 Tabaco | `p09_tabaco` | 0–3 | 0.086890 | 1.0908 | ↑ | −0.114682 | 0.8916 | ↓ |
| 14 | P10 Lavado de manos | `p10_lavado_manos` | 0–3 | 0.067056 | 1.0694 | ↑ | 0.294606 | 1.3426 | ↑ |
| 15 | P14 Antecedentes personales | `p14_antecedentes_personales_count` | 0–8 | 0.248816 | 1.2825 | ↑ | 0.139614 | 1.1498 | ↑ |
| 16 | P15 Antecedentes familiares | `p15_antecedentes_familiares_count` | 0–8 | 0.102071 | 1.1075 | ↑ | 0.099680 | 1.1048 | ↑ |
| 17 | P16 Medicamentos recientes (≥ último mes) | `p16_medicamentos_recientes_count` | 0–7 | 0.203898 | 1.2262 | ↑ | 0.393920 | 1.4828 | ↑ |
| 18 | P17 Medicamentos frecuentes (≥ algunas veces) | `p17_medicamentos_frecuentes_count` | 0–7 | 0.283329 | 1.3275 | ↑ | 0.425769 | 1.5308 | ↑ |
| 19 | P18 Practica deporte | `p18_practica_deporte` | 0/1 | 0.042438 | 1.0434 | ↑ | −0.271693 | 0.7621 | ↓ |
| 20 | P18 Factores de riesgo (sobrecarga, estrés, sedentarismo) | `p18_factores_riesgo_count` | 0–3 | 0.228788 | 1.2571 | ↑ | 0.413345 | 1.5119 | ↑ |
| 21 | P19 Frecuencia de deporte | `p19_frecuencia_deporte` | 0–3 | 0.056207 | 1.0578 | ↑ | −0.060489 | 0.9413 | ↓ |

Interceptos con precisión completa: β₀⁽¹⁾ = −0.7882408686377027; β₀⁽²⁾ = −6.7837911137700315.
El resto de coeficientes con todos sus decimales está en `storage/app/models/modelo_v2.json`.

**Notas sobre la tabla:**

- **Riesgo bajo (0) no aparece** porque es la categoría de referencia: sus coeficientes valen 0
  por construcción.
- **Género** es categórica nominal: el coeficiente compara «Femenino» contra «Masculino». No
  tiene sentido hablar de «subir el género una unidad».
- **Conteos (P14–P18):** el coeficiente es por cada elemento adicional y **todos los elementos
  pesan igual** (p. ej., «Parásitos intestinales» y «Estrés» suman lo mismo en P14).
- **P16 y P17** dependen de umbrales de construcción (≥ 4 y ≥ 2). Un medicamento tomado «hace
  2 meses» (3) no suma nada en P16.
- **P18 aparece dos veces** (`p18_practica_deporte` y `p18_factores_riesgo_count`), y P19 mide
  algo parecido a `p18_practica_deporte`. Sus efectos se reparten entre las tres variables.
- **Ordinales (escalas 0–3, P08 0–6, edad 1–5):** el OR vale por cada escalón y se acumula de
  forma multiplicativa (ORⁿ por n escalones).

---

## 13. Qué falta y qué se recomienda

Hecho en la v2.0: regla de Y aprobada por Enfermería y Nutrición, codificación validada,
reentrenamiento con los 347 registros reales, regularización y carga en `/admin/modelos`.

Pendiente:

1. **Cortes del puntaje con Enfermería** (`docs/consulta-cortes-riesgo.md`). Si se ajustan
   (p. ej. medio ≥ 2 y alto ≥ 4 → 240 / 96 / 11), cambiar las constantes de `ml/comun.py`,
   reentrenar y publicar como v2.1.
2. **Reentrenar con los datos de la prueba piloto**, si el consentimiento lo permite. Es la
   única forma real de reforzar la categoría de riesgo alto.
3. **Decidir sobre las probabilidades mostradas** (D10): dejarlas como están y declararlo, o
   recalibrar los interceptos y aceptar que casi todos salgan en riesgo bajo.
4. **Intervalos de confianza por bootstrap** para los OR de la ecuación 1, si la monografía
   necesita reportar incertidumbre (la estimación penalizada no da valores p válidos).
5. Monografía: reportar el método de §1.2, las métricas de §5.4 y los hallazgos D2, D7, D10,
   D11 y D12 (dominio «análisis» de PROBAST+AI).
