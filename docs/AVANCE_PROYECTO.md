# Avance de GutGuardián — Sprints 2, 3 y 4

> Documento de trabajo para el sustento del trabajo de grado. Explica **qué** se
> construyó, **en qué archivos**, **por qué** se tomó cada decisión y **cómo**
> se relaciona con el documento de tesis (`CLAUDE.md` es la fuente normativa;
> este archivo es su bitácora explicada). Cubre el trabajo pendiente de commit
> a la fecha: captura de encuestas, seguimiento individual y el módulo
> predictivo multinomial.

---

## 1. Cómo leer este documento

Cada sprint se explica en tres capas:

1. **Qué HU cubre** y qué dice la tesis sobre esa historia de usuario.
2. **Qué se construyó** — archivo por archivo, con su responsabilidad exacta.
3. **Por qué se construyó así** — la decisión de diseño y la alternativa que se
   descartó, cuando aplica.

Al final hay una sección de **bloqueantes abiertos**: nada de lo construido en
el Sprint 4 reemplaza al modelo predictivo real, y eso debe quedar explícito
en la monografía.

---

## 2. Sprint 2 — Captura y persistencia de encuestas (HU-004, HU-005, HU-006)

**Lo que pide la tesis:** el instrumento de 20 preguntas (§6 de `CLAUDE.md`)
debe poder diligenciarse por secciones, permitir reanudar donde el estudiante
lo dejó, y quedar inmutable una vez finalizado — porque a partir de ahí es un
registro clínico (§5, regla de integridad).

### 2.1 `app/Http/Controllers/EncuestaController.php`

Es el único punto de entrada para todo el ciclo de vida de un
diligenciamiento. Cinco acciones:

| Acción | Ruta | Responsabilidad |
|---|---|---|
| `iniciar()` | `GET /encuesta` | Busca un diligenciamiento `pendiente`/`en_progreso` del estudiante para el instrumento activo, o crea uno nuevo, y redirige a la primera sección con preguntas sin responder. |
| `seccion()` | `GET /encuesta/{d}/{orden}` | Renderiza una sección con sus preguntas, prellenando las respuestas ya guardadas. |
| `guardar()` | `POST /encuesta/{d}/{orden}` | Valida y persiste **toda la sección de una vez** (no pregunta por pregunta), y avanza a la siguiente. |
| `confirmar()` | `GET /encuesta/{d}/confirmar` | Pantalla de repaso antes de cerrar. |
| `finalizar()` | `POST /encuesta/{d}/finalizar` | Marca `estado = completado` y `completado_at`, deja de ser editable, y dispara la primera evaluación de riesgo (vía `ResultadoController`). |

**Por qué guardar por sección y no por pregunta:** el instrumento define
`estado` como `pendiente | en_progreso | completado`, y la HU-005 (reanudar)
necesita saber en qué sección quedó el estudiante, no en qué pregunta. Guardar
la sección completa en una sola transacción también simplifica la regla de
"sección completa" a una comparación de conjuntos (`primeraSeccionPendiente()`,
línea 163): compara las preguntas de cada sección contra las que ya tienen
respuesta, sin necesitar una columna de progreso separada.

**Por qué el modelo de persistencia varía según el tipo de pregunta**
(`guardarRespuesta()`, líneas 241-292): el instrumento define cuatro tipos de
pregunta (`escala`, `single`, `multiple`, `matriz`, ver §6 de `CLAUDE.md`), y
cada uno tiene una relación distinta con la tabla `respuestas`:

- `escala`/`single` → una fila (`item_pregunta_id = null`).
- `multiple` → una fila por opción marcada; al reenviar se borran las
  anteriores y se recrean (`guardarMultiple`), porque no hay forma barata de
  hacer diff de un conjunto de checkboxes.
- `matriz` (P09, P11, P12, P16, P17) → una fila por ítem de la matriz
  (`item_pregunta_id` no nulo), reutilizando `guardarPorValorNumerico` con el
  id del ítem.

Esto respeta la regla dura de `CLAUDE.md` §5: **nunca se guarda texto libre**,
siempre `opcion_id` + `valor_numerico`, porque el pipeline de análisis
estadístico (`ml/`) depende de esa codificación numérica.

**Autorización:** cada acción llama a `$this->authorize()` contra
`DiligenciamientoPolicy` (ver 2.2) en vez de filtrar manualmente por
`user_id` — es la regla dura de `CLAUDE.md` §4 ("toda consulta de registros
clínicos debe estar filtrada por policy, no solo por la vista").

### 2.2 `app/Policies/DiligenciamientoPolicy.php` (modificado)

Cambio puntual pero importante:

```diff
 public function update(User $user, Diligenciamiento $diligenciamiento): bool
 {
-    return $user->id === $diligenciamiento->user_id;
+    return $user->id === $diligenciamiento->user_id
+        && $diligenciamiento->estado !== 'completado';
 }
```

Antes, un estudiante podía seguir editando su propio diligenciamiento incluso
después de cerrado. Ahora la policy es la única fuente de verdad sobre
inmutabilidad (HU-006): si alguien reintenta `POST /encuesta/{d}/{orden}`
sobre un diligenciamiento completado (doble clic, botón atrás, URL guardada),
la policy lo bloquea con 403. El controlador además intercepta el caso de
**revisita amable** (`seccion()`, `confirmar()`, `finalizar()` con `estado ===
'completado'`): en vez de dejar que el 403 de la policy llegue al estudiante,
redirige a `/resultado/{d}`, que es la experiencia esperada.

### 2.3 Componente nuevo: `resources/views/components/selector-unico.blade.php`

El instrumento tiene preguntas `single` con opciones propias que no encajan en
los componentes de escala existentes (`escala-frecuencia`, 4 niveles fijos;
`escala-dolor`, 1-5 fijo): el género (SD1), el programa (SD3) y "tiempos de
comida al día" (P08, 7 opciones) no son una escala de frecuencia, son una
lista de opciones arbitraria. Se creó este componente (radios accesibles,
`role="radiogroup"`, estado Alpine `selectorUnico()`) en vez de forzar esas
preguntas dentro de `escala-frecuencia`, que asume semántica de "nunca →
siempre".

### 2.4 Corrección en `resources/views/components/matriz-sintomas.blade.php`

Bug preexistente encontrado al implementar HU-005 (reanudar): el componente
recibía `$respuestas` indexado por `item_pregunta_id` (necesario para que el
backend sepa a qué ítem corresponde cada valor), pero el estado de Alpine.js
dentro del componente indexaba por **posición** (`idx`) dentro del array de
ítems. Al reanudar una encuesta con una matriz ya respondida (P11, P12, P16 o
P17), las respuestas nunca se prellenaban porque las claves no coincidían. La
corrección traduce el índice una sola vez en Blade antes de pasarlo a Alpine:

```php
$respuestasPorIndice = [];
foreach ($items as $idx => $item) {
    if (array_key_exists($item['id'], $respuestas)) {
        $respuestasPorIndice[$idx] = $respuestas[$item['id']];
    }
}
```

Cubierto por el test `HU005_ReanudarEncuestaTest.php` → *"las respuestas de
una matriz ya guardadas se prellenan (bug corregido en matriz-sintomas)"*.

### 2.5 Vistas nuevas

- `estudiante/encuesta/seccion.blade.php` — una sección del instrumento, con
  barra de progreso (`barra-progreso`, por sección, no por pregunta — ver
  inventario de componentes en `CLAUDE.md` §12) y los campos según su tipo.
- `estudiante/encuesta/confirmar.blade.php` — pantalla de repaso antes de
  cerrar; el botón de envío final solo se habilita si `todoCompleto` es
  verdadero.

### 2.6 Tests (`tests/Feature/Sprint2/`)

| Archivo | Casos cubiertos |
|---|---|
| `HU004_CapturarEncuestaTest.php` | Crear diligenciamiento al iniciar, mostrar preguntas de la sección, persistir escala/single/matriz/múltiple, rechazar envío incompleto, 403 sobre diligenciamiento ajeno. |
| `HU005_ReanudarEncuestaTest.php` | Reanudar en la sección siguiente sin repetir la completa, prellenar respuestas (incluida la matriz — el bug de 2.4), no duplicar al regrabar la misma sección, redirigir a confirmar cuando todo está completo. |
| `HU006_FinalizarEncuestaTest.php` | Bloquear finalizar con secciones pendientes, marcar `completado` cuando todo está lleno, habilitar el botón de confirmación solo si corresponde, bloquear edición post-cierre (policy), idempotencia de doble finalización, 403 sobre diligenciamiento ajeno. |

---

## 3. Sprint 3 — Seguimiento y visualización individual (HU-007, 008, 011, 012, 013)

**Nota de alcance:** estas HU no traían criterios de aceptación redactados en
el documento de tesis; el alcance se infirió de la descripción general
("visualización de resultados", módulo `Reportes` en §3) y se implementó
sobre lo que el modelo de datos permite construir sin inventar campos nuevos.

### 3.1 `app/Modules/Reportes/Services/SeguimientoService.php`

Es el corazón de este sprint: centraliza toda la lógica de "cómo se ve la
evolución de un estudiante" para que ningún controlador repita queries.
Cuatro métodos:

- `diligenciamientosCompletados(User $user)` — único punto que decide qué
  cuenta como "historial": solo `estado = completado`, ordenado por
  `completado_at`, con su evaluación de riesgo más reciente precargada
  (evita el problema N+1 en las vistas).
- `evolucionRiesgo()` (HU-008) — arma las series de `prob_0/1/2` en porcentaje
  y en orden cronológico, listas para Chart.js.
- `evolucionDolor()` (HU-013) — extrae la respuesta de P13 (escala 1-5) de
  cada diligenciamiento.
- `seguimientoSintomas()` (HU-011/HU-012) — arma un panel por cada uno de los
  6 síntomas rastreados (Diarrea, Dolor abdominal, Vómito, Náuseas,
  Estreñimiento, Fiebre), cruzando su temporalidad (P11) y frecuencia último
  mes (P12) diligenciamiento por diligenciamiento.

**Por qué existe `respuestasClinicas()` como método separado:** `evolucionDolor`
y `seguimientoSintomas` necesitan las mismas respuestas de P11/P12/P13; se
precargan una sola vez (`SeguimientoController::show()`, línea 28) y se pasan
como colección a ambos métodos, en vez de que cada uno dispare su propia
consulta contra `respuestas`.

**Por qué el servicio vive en `Reportes` y no en `Encuestas`:** sigue la
separación de módulos de `CLAUDE.md` §3 — `Encuestas` es captura, `Reportes`
es "visualización de resultados, gráficas, exportación". `SeguimientoService`
no escribe nada, solo lee y da forma a datos para las vistas.

### 3.2 Controladores nuevos

- `EstudianteInicioController` — reemplaza la ruta con closure que antes
  devolvía siempre `estudiante.inicio` vacía. Ahora inyecta
  `SeguimientoService` y decide entre el estado vacío (sin diligenciamientos)
  y el resumen con el último resultado.
- `HistorialController` (HU-007) — lista completa, más reciente primero.
- `SeguimientoController` (HU-008/011/012/013) — aplica una regla de negocio
  explícita: `MINIMO_PARA_EVOLUCION = 2`. Con un solo diligenciamiento no hay
  "evolución" que mostrar, así que la vista entra en estado vacío en vez de
  graficar un punto suelto.

### 3.3 Vistas y gráficas

- `estudiante/historial.blade.php` — lista de tarjetas enlazando a
  `/resultado/{d}`, coloreadas con la paleta de riesgo de `CLAUDE.md` §12
  (`#3E7D64` / `#C08A2E` / `#9C4A32` — nunca rojo, regla dura de la interfaz).
- `estudiante/seguimiento.blade.php` — tres bloques Chart.js (`x-init="new
  Chart(...)"` sobre Alpine): evolución de riesgo, evolución de dolor y el
  grid de 6 síntomas. La paleta de las gráficas de síntomas se validó aparte
  con la skill `dataviz` (`scripts/validate_palette.js`, mencionado en
  `CLAUDE.md` §11) para que no compitiera visualmente con los colores de la
  escala de riesgo.
- `estudiante/inicio.blade.php` (reescrita) — antes solo mostraba el estado
  vacío; ahora bifurca según `totalCompletados`: si hay al menos un resultado,
  muestra la categoría más reciente y enlaces directos a resultado, historial
  y seguimiento.

### 3.4 Sobre HU-010

`CLAUDE.md` §8 ya deja registrada la corrección de alcance: HU-010 pide
"proyección de salud a lo largo del tiempo", pero un modelo transversal
(entrenado sobre datos de un único punto en el tiempo por participante) no
puede proyectar el futuro. Lo que se construyó en `seguimiento.blade.php` es
la evolución **histórica** de los registros ya existentes del estudiante —
consistente con esa corrección, que debe quedar igual de explícita en la
monografía.

### 3.5 Tests (`tests/Feature/Sprint3/`)

| Archivo | Casos cubiertos |
|---|---|
| `HU007_HistorialTest.php` | Estado vacío, orden descendente, excluye pendientes/en progreso, aislamiento entre estudiantes, cada fila enlaza al resultado. |
| `HU008_EvolucionRiesgoTest.php` | Estado vacío con &lt;2 diligenciamientos, probabilidades en porcentaje y orden cronológico, aparición de la gráfica con ≥2 diligenciamientos, aislamiento entre estudiantes. |
| `HU011_HU012_SeguimientoSintomasTest.php` | Panel por síntoma con frecuencia+temporalidad, no contaminación cruzada entre síntomas, aparición del grid con ≥2 diligenciamientos, aislamiento entre estudiantes. |
| `HU013_EvolucionDolorTest.php` | Valores de P13 en orden cronológico, omite diligenciamientos sin P13 en vez de fallar, la gráfica aparece en la vista. |

---

## 4. Sprint 4 — Módulo predictivo multinomial (HU-009, HU-010)

**Lo que pide la tesis (§7):** un modelo de regresión logística multinomial
entrenado *offline* en Python (PHP no tiene librería multinomial confiable),
exportado a JSON, y consumido en Laravel con aritmética pura (softmax) —
testeable con Pest sin depender de Python en producción. Además, explicar el
resultado con los 3-5 predictores de mayor contribución, expresados como odds
ratio en lenguaje llano.

Este es el sprint más grande y el que tiene el **bloqueante crítico** del
proyecto (ver sección 5).

### 4.1 Pipeline Python (`ml/`) — offline, fuera del runtime de Laravel

Cuatro scripts encadenados, cada uno con una única responsabilidad, todos
importando definiciones compartidas de `ml/comun.py`:

```
generar_dataset_sintetico.py  →  preparar_datos.py  →  entrenar_modelo.py  →  exportar_modelo.py
   (347 filas simuladas)         (aplica criterios,      (MNLogit +            (JSON que consume
                                   deriva Y, arma X)       validación cruzada     PredictorService)
                                                            + VIF)
```

**`ml/comun.py`** — el archivo más importante del pipeline. Define en un solo
lugar lo que de otro modo divergiría entre Python y PHP:

- `ORDEN_VARIABLES` — las ~22 variables predictoras, en un orden fijo. Este
  mismo orden es el que `PredictorService::armarVector()` en Laravel debe
  reproducir exactamente; si divergen, los coeficientes entrenados dejan de
  significar lo que el modelo cree que significan.
- `construir_predictores()` — transforma una fila cruda del instrumento en el
  vector `X`. **P11, P12, P13 y P20 quedan deliberadamente fuera de `X`**
  porque son la fuente de la variable dependiente `Y`; incluirlas como
  predictores sería fuga de información (el modelo "adivinaría" la regla en
  vez de generalizar a partir de hábitos y antecedentes independientes).
- `derivar_categoria_riesgo()` — la función que traduce P11/P12/P13/P20 a
  `Y ∈ {0,1,2}` mediante un sistema de puntaje sobre 6 señales (frecuencia de
  síntomas, recencia, dolor, antecedentes). **Es el placeholder documentado
  como bloqueante** — ver sección 5.
- P14/P15/P16/P17/P18 se resumen en **conteos** (p. ej.
  `p14_antecedentes_personales_count`) en vez de un dummy por ítem: con ~350
  registros, codificar cada antecedente/medicamento como variable individual
  dispararía la dimensionalidad del modelo muy por encima de lo que el tamaño
  de muestra soporta.

**`generar_dataset_sintetico.py`** — genera 347 filas con la forma cruda del
instrumento (una columna por pregunta/ítem), a partir de una variable latente
`z` ("propensión al riesgo") que induce correlaciones plausibles entre
hábitos, antecedentes y síntomas. Existe porque los datos reales de
participantes **nunca se suben al repositorio** (regla dura de `CLAUDE.md`
§10 y del `.gitignore`); sin este script no habría manera de ejercitar el
pipeline durante el desarrollo. Inyecta a propósito registros incompletos y
duplicados para poder probar los criterios de inclusión/exclusión.

**`preparar_datos.py`** — aplica criterios de inclusión/exclusión (descarta
diligenciamientos con P13 vacío, descarta duplicados de
`codigo_participante`), deriva `Y` y construye `X`, y deja
`dataset_preparado.csv` listo para entrenar.

**`entrenar_modelo.py`** — ajusta `statsmodels.MNLogit`, con:
- Validación cruzada estratificada (5 folds).
- Partición train/test (75/25) para métricas de generalización honestas.
- Exactitud, AUC macro OVR, matriz de confusión y una calibración simplificada
  por bins de probabilidad.
- **Chequeo de VIF** (factor de inflación de varianza) para detectar
  multicolinealidad entre predictores, con advertencia impresa si algún VIF
  supera 5.
- Registra explícitamente `limitacion_epv` en las métricas exportadas: con
  ~340 registros, 3 clases y 22 predictores, el ratio eventos-por-variable es
  ajustado — la misma limitación que `CLAUDE.md` §7 pide declarar en el
  dominio "análisis estadístico" de PROBAST+AI.

**`exportar_modelo.py`** — traduce el objeto `statsmodels` (que no es
serializable ni portable) a `storage/app/models/modelo_v1.json`, con esta
forma:

```json
{
  "version": "1.0",
  "categoria_base": 0,
  "categorias": [0, 1, 2],
  "orden_variables": ["edad", "semestre", ...],
  "mapa_variables": { "edad": {"etiqueta": "Rango de edad"}, ... },
  "coeficientes": {
    "1": { "intercepto": ..., "beta": { "edad": ..., ... } },
    "2": { "intercepto": ..., "beta": { ... } }
  },
  "metricas": { "exactitud_test": 0.60, "auc_macro_ovr_test": 0.785, "vif": {...}, "limitacion_epv": "..." },
  "limitaciones": "Modelo entrenado sobre un dataset SINTÉTICO ... No usar para tamizaje real ..."
}
```

El campo `limitaciones` se escribe en el JSON mismo, no solo en la
documentación externa, para que quede pegado al artefacto que
`VersionModeloSeeder` carga a la base de datos.

### 4.2 `app/Modules/Analitica/Services/PredictorService.php`

Es el equivalente Laravel de `construir_predictores()` +
`predecirDesdeVector()`, en PHP puro (sin llamar a Python en tiempo de
ejecución — cumple la restricción de `CLAUDE.md` §2 de no introducir
dependencias externas de IA):

1. `armarVector()` — lee las `respuestas` de un diligenciamiento, las agrupa
   por código de pregunta, y calcula cada una de las ~22 variables con las
   mismas reglas que `ml/comun.py::construir_predictores` (recencia con el
   mismo umbral `RECIENCIA_ALTA = 4`, conteos con los mismos umbrales de
   frecuencia). El orden lo dicta `version.mapa_variables['orden_variables']`
   — leído del modelo activo, no hardcodeado — así que si se entrena una
   versión 2 con variables distintas, el servicio no necesita cambiar.
2. `predecirDesdeVector()` — calcula el predictor lineal (`η`) por categoría
   con los coeficientes del modelo activo, dejando `η = 0` para la categoría
   base (así lo define un `MNLogit`), y aplica softmax.
3. `softmax()` — resta el máximo antes de exponenciar (estabilidad numérica,
   evita overflow con predictores lineales grandes) — cubierto explícitamente
   por un test dedicado.

### 4.3 `app/Modules/Analitica/Services/ExplicabilidadService.php`

Implementa el requisito de explicabilidad de `CLAUDE.md` §7: toma la
predicción y devuelve los 5 predictores de mayor contribución, como odds
ratio (`exp(β)`) y una frase en lenguaje llano ("El consumo diario de
frituras multiplica por 2.3 la probabilidad..."). Dos decisiones no triviales:

- **Ordena por `|β·x|` (aporte real a esta persona), no por `|β|`**: un
  coeficiente grande no importa si la variable vale 0 para este estudiante;
  lo que se muestra es lo que efectivamente influyó en *esta* predicción.
- **Cuando la categoría predicha es la categoría base** (0, riesgo bajo), no
  hay coeficientes propios que explicar — el predictor lineal de la base es
  siempre 0 por construcción del `MNLogit`. En ese caso se usan, sin invertir
  el signo, los coeficientes de la categoría no-base más próxima: un aporte
  negativo (`β·x < 0`) es precisamente la razón por la que el estudiante *no*
  fue clasificado en esa categoría de mayor riesgo, y es información
  legítima para mostrar.

### 4.4 `app/Http/Controllers/ResultadoController.php`

Orquesta el flujo completo y lo persiste:

```
PredictorService::predecir()  →  ExplicabilidadService::explicar()  →  EvaluacionRiesgo::create()
```

`version_modelo_id` queda **siempre** grabado junto al resultado (línea 48) —
la regla dura de `CLAUDE.md` §5: "un resultado sin trazabilidad de qué modelo
lo produjo es inservible para TRIPOD+AI". La evaluación se calcula una sola
vez y se reutiliza en visitas posteriores (`->latest('evaluado_at')->first()
?? $this->evaluar(...)`), evitando recalcular en cada carga de la página.

La ruta `/resultado/{diligenciamiento}` es accesible a estudiante (solo el
propio, vía policy), profesional_salud y admin — a diferencia de
`/encuesta/*`, que está detrás de `role:estudiante`. La autorización fina la
resuelve `DiligenciamientoPolicy::view`, no el middleware de rol (ver diff de
`routes/web.php` en la sección 6).

### 4.5 `Controller.php` (modificado)

```diff
+use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
 abstract class Controller
 {
-    //
+    use AuthorizesRequests;
 }
```

Habilita `$this->authorize()` en todos los controladores — usado por
`EncuestaController` y `ResultadoController` para delegar en las policies en
vez de repetir comparaciones de `user_id`.

### 4.6 `database/seeders/VersionModeloSeeder.php`

Carga `storage/app/models/modelo_v1.json` a la tabla `versiones_modelo`,
desactivando cualquier versión previa antes de activar la nueva (solo una
versión activa a la vez, que es la que usa `PredictorService::versionActiva()`).
Falla explícitamente con un mensaje accionable si el JSON no existe todavía
("ejecuta el pipeline de `ml/` antes de sembrar esta versión") — evita que el
seeder falle con un error críptico de archivo no encontrado. Se agregó a
`DatabaseSeeder::run()` después de `InstrumentoSeeder`.

### 4.7 Modelo actualmente sembrado

`storage/app/models/modelo_v1.json` (versión `1.0`) fue entrenado sobre el
dataset **sintético** de 347 filas (339 tras aplicar criterios de exclusión).
Métricas actuales: exactitud en test ≈ 0.60, AUC macro OVR ≈ 0.785. Estas
cifras **no dicen nada sobre el desempeño real del tamizaje** — son solo
evidencia de que el pipeline de ingeniería (entrenar → exportar → sembrar →
predecir → explicar) corre de punta a punta sin errores.

### 4.8 Tests (`tests/Feature/Sprint4/`)

| Archivo | Casos cubiertos |
|---|---|
| `HU009_SoftmaxTest.php` | Casos conocidos de softmax (β positivo/negativo, interceptos sin predictores activos, coeficientes en cero → 1/3 cada categoría, estabilidad numérica con valores grandes). |
| `HU009_ArmarVectorTest.php` | Construcción correcta del vector desde respuestas reales (sociodemográficas, nutricionales, clínicas resumidas), error explícito si el modelo referencia una variable que el servicio no sabe calcular. |
| `HU009_ExplicabilidadServiceTest.php` | Orden por `|β·x|` y no por `|β|`, el odds ratio es `exp(β)` de la categoría predicha (no `exp(β·x)`), manejo correcto de la categoría base. |
| `HU009_ResultadoControllerTest.php` | Flujo completo con persistencia y `version_modelo_id`, reutilización de la evaluación ya calculada, 403 sobre resultado ajeno, ausencia de evaluación si el diligenciamiento no está completo. |

---

## 5. Bloqueante crítico — regla operativa de la variable Y

Este es el punto que más impacta la validez del entregable y debe explicarse
sin ambigüedad en la sustentación:

**Qué falta:** el documento de tesis no define la regla clínica que traduce
P11 (temporalidad de síntomas), P12 (frecuencia de síntomas), P13 (escala de
dolor) y P20 (enfermedades padecidas) en las tres categorías de riesgo. Esa
regla debe construirse **con el equipo de Enfermería** — es un criterio
clínico, no una decisión de ingeniería.

**Qué existe hoy en su lugar:** `ml/comun.py::derivar_categoria_riesgo()`, un
sistema de puntaje sobre 6 señales con umbrales elegidos únicamente para que
el dataset sintético produzca tres clases no degeneradas. Está documentado
como placeholder en el docstring del módulo, en `exportar_modelo.py`
(campo `limitaciones` del JSON) y en `CLAUDE.md` §7 y §11.

**Por qué esto no invalida el trabajo del Sprint 4:** todo el pipeline de
ingeniería alrededor de esa regla — extracción de predictores, entrenamiento,
validación cruzada, chequeo de VIF, softmax, explicabilidad, persistencia con
trazabilidad de versión — es independiente de cuál sea la regla exacta.
Cuando Enfermería entregue el criterio real, solo cambia
`derivar_categoria_riesgo()` y se re-ejecuta el pipeline (`preparar_datos.py`
→ `entrenar_modelo.py` → `exportar_modelo.py`) sobre los 347 registros reales;
nada del lado Laravel necesita tocarse salvo, eventualmente, el
`orden_variables` si el nuevo criterio sugiere agregar o quitar predictores.

**Qué falta además:** el dataset real de 347 participantes, limpio y
exportado con las mismas columnas que consume `preparar_datos.py`. Hoy el
pipeline solo corre sobre `generar_dataset_sintetico.py`.

---

## 6. Rutas agregadas (`routes/web.php`)

```php
// Área del estudiante (auth + consentimiento + role:estudiante)
GET  /inicio                              → EstudianteInicioController::show
GET  /historial                           → HistorialController::show          (HU-007)
GET  /seguimiento                         → SeguimientoController::show        (HU-008/011/012/013)
GET  /encuesta                            → EncuestaController::iniciar        (HU-004/005)
GET  /encuesta/{d}/{orden}                → EncuestaController::seccion
POST /encuesta/{d}/{orden}                → EncuestaController::guardar
GET  /encuesta/{d}/confirmar              → EncuestaController::confirmar
POST /encuesta/{d}/finalizar              → EncuestaController::finalizar      (HU-006)

// Accesible a estudiante (propio) + profesional_salud + admin — autorización por policy
GET  /resultado/{d}                       → ResultadoController::show          (HU-009)
```

`/inicio` dejó de ser un closure inline que devolvía siempre la misma vista
vacía; ahora resuelve a un controlador con estado real.

---

## 7. Estructura de archivos — resumen

```
app/
├── Http/Controllers/
│   ├── Controller.php                    (+ AuthorizesRequests)
│   ├── EncuestaController.php            NUEVO — HU-004/005/006
│   ├── EstudianteInicioController.php    NUEVO
│   ├── HistorialController.php           NUEVO — HU-007
│   ├── ResultadoController.php           NUEVO — HU-009
│   └── SeguimientoController.php         NUEVO — HU-008/011/012/013
├── Modules/
│   ├── Analitica/Services/
│   │   ├── PredictorService.php          NUEVO — softmax + armado de vector
│   │   └── ExplicabilidadService.php     NUEVO — top-5 contribuciones
│   └── Reportes/Services/
│       └── SeguimientoService.php        NUEVO — series para historial/seguimiento
└── Policies/
    └── DiligenciamientoPolicy.php        (+ inmutabilidad post-cierre)

database/seeders/
├── VersionModeloSeeder.php               NUEVO
└── DatabaseSeeder.php                    (+ VersionModeloSeeder)

ml/                                        NUEVO — pipeline offline (fuera del runtime Laravel)
├── comun.py                               orden de variables, derivación de Y (placeholder), predictores
├── generar_dataset_sintetico.py           347 filas simuladas, nunca datos reales
├── preparar_datos.py                      criterios de inclusión/exclusión + construcción de X/Y
├── entrenar_modelo.py                     MNLogit + validación cruzada + VIF
├── exportar_modelo.py                     → storage/app/models/modelo_v1.json
└── requirements.txt

storage/app/models/
└── modelo_v1.json                        NUEVO — versión activa, entrenada sobre datos sintéticos

resources/views/
├── components/
│   ├── selector-unico.blade.php          NUEVO — radios para single sin escala fija
│   └── matriz-sintomas.blade.php         (fix: prellenado al reanudar)
└── estudiante/
    ├── inicio.blade.php                  (reescrita: estado vacío vs. resumen)
    ├── historial.blade.php               NUEVO — HU-007
    ├── seguimiento.blade.php             NUEVO — HU-008/011/012/013, Chart.js
    ├── resultado.blade.php               NUEVO — HU-009, usa <x-pista-riesgo>
    └── encuesta/
        ├── seccion.blade.php             NUEVO
        └── confirmar.blade.php           NUEVO

tests/Feature/
├── Sprint2/  HU004, HU005, HU006         27 casos — captura y persistencia
├── Sprint3/  HU007, HU008, HU011_HU012, HU013   17 casos — seguimiento
└── Sprint4/  HU009 (Softmax, ArmarVector, ExplicabilidadService, ResultadoController)   14 casos — predictivo
```

---

## 8. Trazabilidad con la tesis

| Elemento construido | Sección del documento de tesis |
|---|---|
| Separación `ml/` (offline) vs. `app/Modules/Analitica` (online) | §2.4.1.3.1 (stack), §7 (flujo del modelo) |
| `version_modelo_id` obligatorio en toda evaluación | §5, regla de integridad — trazabilidad TRIPOD+AI |
| `ExplicabilidadService` (odds ratio + frase) | §7, requisito de explicabilidad |
| `limitacion_epv` y chequeo de VIF en `entrenar_modelo.py` | §7, "limitación conocida a documentar" — PROBAST+AI, dominio análisis estadístico |
| `derivar_categoria_riesgo()` marcada como placeholder | §7 y §11, bloqueante abierto |
| Reescritura de HU-010 como evolución histórica, no proyección | §8, nota sobre HU-010 |
| `DiligenciamientoPolicy` (inmutabilidad + aislamiento entre estudiantes) | §4, regla dura de aislamiento |
| Paleta de riesgo sin rojo en historial/seguimiento | §12, reglas de calidad de interfaz |
| `.gitignore` de `ml/datos/` y datos sintéticos únicos versionados | §10, "nunca subir los 347 registros reales" |

---

## 9. Pendientes abiertos (a la fecha de este documento)

1. **Bloqueante — regla clínica de derivación de Y**, pendiente del equipo de
   Enfermería (sección 5 de este documento).
2. **Dataset real de 347 registros**, exportado y limpio para reemplazar al
   sintético en `ml/datos/dataset_sintetico.csv`.
3. Sprint 5 (panel institucional y reportes, HU-014 a HU-019, HU-022 a
   HU-024) y Sprint 6 (pruebas integrales y despliegue piloto) — no
   iniciados.
4. Reentrenar y re-exportar `modelo_v1.json` en cuanto existan (1) y (2), y
   documentar en la monografía que la versión actual es una prueba de
   concepto de ingeniería, no un modelo validado.

---

## 10. Corrección de empalme HU (post Sprint 4)

Antes de iniciar el Sprint 5 se extrajo `docs/HISTORIAS_USUARIO.md` —el texto
verbatim de las 25 HU del documento de tesis, con sus criterios Dado/Cuando/
Entonces— para tener la fuente exacta del backlog de Sprint 5. Esa extracción
reveló que el Sprint 3, tal como quedó documentado en la sección 3 de este
mismo archivo, tenía las etiquetas HU cruzadas contra el documento real.

### 10.1 Qué estaba mal

| Etiqueta usada en Sprint 3 | Lo que de verdad implementa | HU real correspondiente |
|---|---|---|
| "HU-007" (`/historial`, lista cronológica completa) | Consulta del historial de registros | **HU-011** |
| "HU-011/HU-012" (grid de 6 síntomas, frecuencia/temporalidad) | No corresponde a ninguna HU del documento | — (complementaria) |
| "HU-013" (evolución del dolor abdominal, P13) | No corresponde a ninguna HU del documento | — (complementaria) |

Como consecuencia, tres HU reales habían quedado **sin construir**: la
verdadera HU-007 ("resumen de mis últimas respuestas"), la verdadera HU-012
("alerta interna cuando cambia mi nivel de riesgo") y la verdadera HU-013
("actualizar mis datos de perfil").

### 10.2 Qué se corrigió

- **Renombrado sin cambiar comportamiento:** `HU007_HistorialTest.php` →
  `HU011_HistorialTest.php`; los dos archivos de funcionalidad complementaria
  (`HU011_HU012_SeguimientoSintomasTest.php`, `HU013_EvolucionDolorTest.php`)
  perdieron el prefijo HU en su nombre y en el texto de sus `describe()`, y los
  docblocks de `HistorialController`, `SeguimientoController` y
  `SeguimientoService` quedaron alineados con la tabla de arriba.

- **HU-007 real** ("resumen de respuestas registradas") ya estaba prácticamente
  cubierta por `EstudianteInicioController` + `estudiante/inicio.blade.php`
  (resultado más reciente, estado vacío, aislado por estudiante). Se le agregó
  el docblock correcto y `tests/Feature/Sprint3/HU007_ResumenRespuestasTest.php`.

- **HU-012 real** ("alertas internas de riesgo") — nuevo
  `App\Modules\Panel\Services\AlertaService::generarSiCambioDeRiesgo()`: compara
  la evaluación recién calculada contra la anterior del mismo estudiante y solo
  genera `Alerta` si la categoría cambió (nunca en la primera evaluación). Se
  invoca desde `ResultadoController::evaluar()`, justo después de persistir la
  `EvaluacionRiesgo`. Nuevos `AlertaController` (`/alertas`, marcar como leída),
  `AlertaPolicy` (registrada en `AppServiceProvider`) y vista
  `estudiante/alertas.blade.php`. El modelo `Alerta` y la tabla `alertas` ya
  existían desde el diseño original del esquema (CLAUDE.md §5) — nadie los
  usaba todavía.

- **HU-013 real** ("actualización del perfil del estudiante") — nuevo
  `PerfilController` (`/perfil`, ver/editar género, edad, programa, semestre)
  reutilizando las mismas reglas de validación y el mismo patrón de formulario
  del registro (`RegisteredUserController`, `auth/register.blade.php`). Opera
  siempre sobre `$request->user()->perfil`, nunca sobre un `Perfil` por id de
  ruta, así que no existe vector de acceso cruzado entre estudiantes que
  requiera policy. Actualizar el perfil no toca `respuestas` ni
  `diligenciamientos` ya guardados — son tablas independientes.

- Navbar del layout de estudiante (`layouts/estudiante.blade.php`) ampliado con
  enlaces a "Alertas" (con contador de no leídas) y "Perfil".

### 10.3 Por qué no invalida el trabajo ya hecho

El grid de síntomas y la gráfica de dolor abdominal no se eliminaron: siguen
siendo funcionalidad útil del módulo `Reportes`, solo dejaron de reclamar un
número de HU que no les correspondía. Con esta corrección, Sprint 3 cubre
ahora, de forma exacta, las cinco HU reales que el backlog le asigna
(HU-007/008/011/012/013).

### 10.4 Tests (`tests/Feature/Sprint3/`, tras la corrección)

| Archivo | HU | Casos cubiertos |
|---|---|---|
| `HU007_ResumenRespuestasTest.php` | HU-007 | Estado vacío, resultado más reciente (no uno viejo), aislamiento entre estudiantes, enlaces a historial/seguimiento. |
| `HU008_EvolucionRiesgoTest.php` | HU-008 | Sin cambios — ya coincidía. |
| `HU011_HistorialTest.php` | HU-011 | Renombrado desde `HU007_HistorialTest.php`, mismos casos. |
| `HU012_AlertasRiesgoTest.php` | HU-012 | No genera alerta en la primera evaluación ni cuando la categoría no cambia; genera alerta con el mensaje correcto cuando cambia; indicador de no leída; marcar como leída; 403 sobre alerta ajena; aislamiento en el listado. |
| `HU013_ActualizarPerfilTest.php` | HU-013 | Muestra datos actuales; actualiza con datos válidos; rechaza edad fuera de rango; rechaza campos vacíos; no altera respuestas históricas al actualizar. |
| `SeguimientoSintomasTest.php` | — (complementaria) | Renombrado desde `HU011_HU012_SeguimientoSintomasTest.php`, mismos casos. |
| `EvolucionDolorTest.php` | — (complementaria) | Renombrado desde `HU013_EvolucionDolorTest.php`, mismos casos. |

---

## 11. Sprint 5 — Panel institucional y reportes (HU-014 a HU-019, HU-022 a HU-024)

**Lo que pide la tesis (§2.4.1.3.5, backlog):** solo la narrativa de una línea
por HU — a diferencia de HU-001 a HU-025 (salvo estas 9), el documento **no
desarrolla el capítulo de "Etapa de análisis"** de Sprint 5, así que no existen
criterios Dado/Cuando/Entonces redactados para ninguna de estas 9 HU. Antes de
implementar, y para no repetir el desalineamiento de Sprint 3 (sección 10 de
este documento), se escribieron aquí los criterios inferidos, en el mismo
formato que el resto del documento, **antes** de que quedaran fijados en el
código y los tests.

### 11.1 Hallazgo que simplificó el sprint

`database/seeders/RolesPermisosSeeder.php` (sembrado desde Sprint 1) ya
definía los permisos granulares exactos que este sprint necesita —
`consultar_estudiantes`, `ver_niveles_riesgo`, `generar_reportes`,
`exportar_reportes`, `crear_usuarios`, `editar_usuarios`,
`desactivar_usuarios`, todos asignados a `profesional_salud` — sin que nada
los usara todavía. El middleware `permission:` ya estaba registrado en
`bootstrap/app.php`. Cada ruta de este sprint queda protegida por el permiso
que le corresponde, no solo por el rol.

### 11.2 Decisiones de alcance inferido

- **HU-016** ("visualizar el nivel de riesgo asignado a cada estudiante") no
  es una pantalla propia: se resuelve mostrando el riesgo más reciente en el
  listado (HU-015) y en la ficha individual (HU-014), que es donde tiene
  sentido consultarlo.
- **HU-017/018/019** se acotan a cuentas con rol `estudiante`. HU-014 y
  HU-015 —mismo bloque narrativo del backlog— hablan explícitamente de
  "estudiantes"; en ningún punto el backlog describe gestionar cuentas de
  otros profesionales o administradores.
- **HU-017** (crear usuario): el profesional define un password inicial en
  el mismo formulario, con las mismas reglas que el autorregistro
  (`RegisteredUserController`). No se construyó un flujo de invitación por
  correo — no está pedido en el backlog y el proyecto no tiene mail
  configurado para probarlo honestamente.
- **HU-019** (desactivar): se implementó como una sola acción que alterna
  `activo` (desactivar ⇄ reactivar), no una vía sin retorno — un error de
  clic no debería requerir tocar la base de datos a mano para deshacerse.
  `activo = false` ahora sí bloquea el login (antes el campo existía en el
  esquema desde Sprint 1 pero no se usaba en ningún sitio).
- **HU-022/023** (reporte + filtro) se resolvieron en una sola pantalla:
  distribución de niveles de riesgo (conteos y porcentajes) sobre todas las
  evaluaciones realizadas, filtrable por rango de fecha y por categoría.

### 11.3 Criterios de aceptación inferidos

#### HU-014 — Consulta individual de estudiante

> Como profesional de salud quiero buscar y consultar el registro individual
> de un estudiante para hacer seguimiento a su estado digestivo.

1. Cuando el profesional acceda a la ficha de un estudiante, entonces el
   sistema debe mostrar sus datos básicos (nombre, código, programa,
   semestre, estado de la cuenta).
2. Cuando el estudiante tenga encuestas completadas, entonces el sistema debe
   mostrar su historial cronológico y la evolución de su nivel de riesgo.
3. Cuando el estudiante no tenga encuestas completadas, entonces el sistema
   debe indicarlo sin mostrar una gráfica vacía.
4. Cuando un usuario sin el permiso `consultar_estudiantes` intente acceder,
   entonces el sistema debe denegar el acceso (403).
5. Cuando se solicite la ficha de una cuenta que no tiene rol `estudiante`,
   entonces el sistema debe responder 404 — este flujo es exclusivo para
   estudiantes.

#### HU-015 — Listado general de estudiantes

> Como profesional de salud quiero ver el listado general de todos los
> estudiantes registrados para tener una visión global del grupo monitoreado.

1. Cuando el profesional acceda al listado, entonces el sistema debe mostrar
   todos los estudiantes registrados con su programa y su nivel de riesgo
   más reciente.
2. Cuando el profesional busque por nombre, código o programa, entonces el
   listado debe filtrarse a las coincidencias.
3. Cuando no existan estudiantes registrados o ninguno coincida con la
   búsqueda, entonces el sistema debe mostrar un mensaje informativo.
4. Cuando un usuario sin el permiso `consultar_estudiantes` intente acceder,
   entonces el sistema debe denegar el acceso (403).

#### HU-016 — Visualización del nivel de riesgo por estudiante

> Como profesional de salud quiero visualizar el nivel de riesgo asignado a
> cada estudiante para identificar casos que requieran atención prioritaria.

1. Cuando el profesional consulte el listado general, entonces cada fila debe
   mostrar el nivel de riesgo más reciente del estudiante correspondiente.
2. Cuando el profesional consulte la ficha individual de un estudiante,
   entonces debe poder ver la evolución de su nivel de riesgo a lo largo del
   tiempo.
3. Cuando un estudiante no tenga ninguna evaluación todavía, entonces el
   sistema debe indicarlo explícitamente ("Sin evaluar"), no dejar la celda
   vacía.

*(Sin pantalla propia — resuelta dentro de HU-014 y HU-015, ver 11.2.)*

#### HU-017 — Creación de usuarios estudiantes

> Como profesional de salud quiero crear nuevos usuarios estudiantes en el
> sistema para incorporarlos al proceso de monitoreo.

1. Cuando el profesional complete el formulario con datos válidos, entonces
   el sistema debe crear la cuenta con rol `estudiante` y su perfil
   sociodemográfico asociado.
2. Cuando el correo o el código de participante ya existan, entonces el
   sistema debe rechazar la creación indicando el campo en conflicto.
3. Cuando falte un campo obligatorio, entonces el sistema debe impedir la
   creación hasta completarlo.
4. Cuando la cuenta se cree correctamente, entonces el sistema debe llevar al
   profesional a la ficha del nuevo estudiante.
5. Cuando un usuario sin el permiso `crear_usuarios` intente acceder,
   entonces el sistema debe denegar el acceso (403).

#### HU-018 — Edición de información de usuario

> Como profesional de salud quiero editar la información de un usuario
> registrado para corregir o actualizar sus datos cuando sea necesario.

1. Cuando el profesional edite los datos de un estudiante con información
   válida, entonces el sistema debe guardar los cambios (nombre, correo,
   código, datos sociodemográficos).
2. Cuando el correo o el código editado ya pertenezcan a otra cuenta,
   entonces el sistema debe rechazar el cambio.
3. Cuando se intente editar una cuenta que no tiene rol `estudiante`,
   entonces el sistema debe responder 404.
4. Cuando un usuario sin el permiso `editar_usuarios` intente acceder,
   entonces el sistema debe denegar el acceso (403).

#### HU-019 — Desactivación de cuenta de usuario

> Como profesional de salud quiero desactivar la cuenta de un usuario para
> suspender su acceso sin eliminar su historial clínico.

1. Cuando el profesional desactive la cuenta de un estudiante, entonces el
   sistema debe impedirle iniciar sesión sin borrar sus diligenciamientos ni
   sus respuestas.
2. Cuando un estudiante desactivado intente iniciar sesión, entonces el
   sistema debe rechazar el acceso con el mismo mensaje genérico que unas
   credenciales inválidas — sin revelar que la cuenta fue desactivada.
3. Cuando el profesional reactive una cuenta previamente desactivada,
   entonces el estudiante debe poder volver a iniciar sesión.
4. Cuando un usuario sin el permiso `desactivar_usuarios` intente acceder,
   entonces el sistema debe denegar el acceso (403).

#### HU-022 — Reporte del comportamiento general de niveles de riesgo

> Como profesional de salud quiero visualizar un reporte del comportamiento
> general de los niveles de riesgo de los estudiantes para identificar
> patrones en la población monitoreada.

1. Cuando el profesional acceda al reporte, entonces el sistema debe mostrar
   la distribución (conteo y porcentaje) de niveles de riesgo bajo, medio y
   alto sobre todas las evaluaciones realizadas.
2. Cuando no existan evaluaciones registradas, entonces el sistema debe
   mostrar un mensaje informativo en vez de un reporte vacío.

#### HU-023 — Filtrado de reportes por fecha o nivel de riesgo

> Como profesional de salud quiero filtrar los reportes por fecha o nivel de
> riesgo para segmentar el análisis según mis necesidades.

1. Cuando el profesional filtre por un rango de fechas, entonces el reporte
   debe recalcularse solo sobre las evaluaciones dentro de ese rango.
2. Cuando el profesional filtre por una categoría de riesgo, entonces el
   reporte debe mostrar únicamente esa categoría.
3. Cuando los filtros no arrojen resultados, entonces el sistema debe
   indicarlo explícitamente.
4. Cuando un usuario sin el permiso `generar_reportes` intente acceder,
   entonces el sistema debe denegar el acceso (403).

#### HU-024 — Exportación de reportes (PDF / Excel)

> Como profesional de salud quiero exportar los reportes generados en
> formato PDF o Excel para compartirlos con otros actores institucionales.

1. Cuando el profesional exporte el reporte a PDF, entonces el sistema debe
   generar un documento descargable con el mismo resumen y detalle que la
   pantalla, respetando los filtros aplicados.
2. Cuando el profesional exporte el reporte a Excel, entonces el sistema debe
   generar una hoja de cálculo descargable con el detalle filtrado.
3. Cuando un usuario sin el permiso `exportar_reportes` intente acceder,
   entonces el sistema debe denegar el acceso (403).

### 11.4 Qué se construyó

| Archivo | Responsabilidad |
|---|---|
| `app/Modules/Reportes/Services/ReporteInstitucionalService.php` | `generar(filtros)` — única fuente de verdad (resumen + detalle) para la vista, el PDF y el Excel del reporte. |
| `app/Exports/DistribucionRiesgoExport.php` | `FromCollection`/`WithHeadings` sobre el detalle del servicio anterior — HU-024 (Excel). |
| `app/Http/Controllers/EstudianteController.php` | `index()` (HU-015, búsqueda) y `show()` (HU-014, reutiliza `SeguimientoService` de Sprint 3). |
| `app/Http/Controllers/UsuarioController.php` | `create/store` (HU-017), `edit/update` (HU-018), `alternarActivo` (HU-019). |
| `app/Http/Controllers/ReporteController.php` | `index` (HU-022/023), `exportarPdf`/`exportarExcel` (HU-024, dompdf + maatwebsite/excel). |
| `app/Http/Requests/Auth/LoginRequest.php` (modificado) | `Auth::attempt()` exige `activo => true` — HU-019, sin revelar el motivo del rechazo. |
| `resources/views/panel/estudiantes/{index,show,crear,editar}.blade.php` | Vistas del listado, ficha, creación y edición. |
| `resources/views/panel/reportes/index.blade.php` | Filtros, resumen, gráfica de barras (Chart.js) y detalle. |
| `resources/views/reportes/pdf/distribucion-riesgo.blade.php` | Plantilla HTML/CSS plano para dompdf (sin utilidades Tailwind arbitrarias, que dompdf no procesa). |
| `resources/views/components/layouts/profesional.blade.php` (modificado) | Los ítems "Estudiantes"/"Reportes" del sidebar, antes apuntando a `panel.inicio` como placeholder, ahora enlazan a las rutas reales y se ocultan sin el permiso correspondiente. |

Todas las rutas nuevas viven bajo `/panel`, protegidas por
`role:profesional_salud|admin` + `permission:<nombre>` por grupo de rutas
(ver `routes/web.php`).

### 11.5 Tests (`tests/Feature/Sprint5/`)

| Archivo | Casos cubiertos |
|---|---|
| `HU014_ConsultaIndividualTest.php` | Consulta de la ficha con historial; 403 sin permiso; 404 sobre una cuenta que no es estudiante. |
| `HU015_ListadoEstudiantesTest.php` | Listado general; búsqueda por nombre; estado vacío; 403 sin permiso. |
| `HU017_CrearUsuarioTest.php` | Creación exitosa con perfil asociado; rechazo de correo/código duplicado; 403 sin permiso. |
| `HU018_EditarUsuarioTest.php` | Edición exitosa; 404 sobre una cuenta que no es estudiante; 403 sin permiso. |
| `HU019_DesactivarUsuarioTest.php` | Desactivar sin borrar historial; login bloqueado tras desactivar; login restaurado tras reactivar; 403 sin permiso. |
| `HU016_NivelRiesgoTest.php` | "Sin evaluar" en el listado sin evaluaciones; el listado muestra el riesgo más reciente y no uno antiguo; la ficha grafica la evolución con ≥2 evaluaciones y no con 1; la ficha muestra el `aviso-no-diagnostico` junto al riesgo y lo omite cuando el estudiante no tiene evaluaciones. |
| `HU022_HU023_ReporteGeneralTest.php` | Distribución correcta; filtro por fecha; filtro por categoría; estado vacío con filtros sin resultados; 403 sin permiso. |
| `HU024_ExportarReportesTest.php` | Descarga de PDF (`Content-Type: application/pdf`); descarga de Excel (`spreadsheetml`); 403 sin permiso. |
| `AuditoriaAccesoClinicoTest.php` | Registra causante/sujeto al consultar la ficha; registra la consulta de un resultado por un tercero; **no** audita el acceso del propio estudiante a su resultado; registra la consulta y las dos exportaciones del reporte; conserva causante y fecha. |

Suite completa verificada tras el sprint: 168 tests, 404 assertions.
Verificado también manualmente contra el servidor de desarrollo con el
usuario `profesional@umariana.edu.co` (`UsuariosTestSeeder`): listar y
buscar estudiantes, crear uno nuevo, ver su ficha, editarlo, desactivarlo
(login bloqueado confirmado), reactivarlo (login restaurado confirmado), y
generar + descargar el reporte en PDF (3 páginas válidas) y Excel (.xlsx
válido).

Suite completa verificada tras la corrección: 143 tests, 347 assertions.

### 11.6 Cierre de huecos de cumplimiento legal del Sprint 5

Sprint 5 es el primer sprint que da acceso de terceros (profesional de salud /
admin) a datos clínicos del estudiante. Al revisarlo contra `CLAUDE.md` §9
—que marca esos requisitos como "afectan el código, no son solo papeleo"—
quedaban tres huecos:

1. **Log de auditoría de acceso a datos clínicos (Ley 1581 de 2012).**
   `spatie/laravel-activitylog` estaba instalado y la tabla `activity_log`
   migrada desde Sprint 0, pero nada registraba quién consultaba un registro
   clínico. Nuevo
   `App\Modules\Panel\Services\AuditoriaClinicaService` (log dedicado
   `acceso_clinico`), invocado desde:
   - `EstudianteController::show` → `consulta_ficha` (causante = profesional,
     sujeto = estudiante).
   - `ResultadoController::show` → `consulta_resultado`, **solo cuando el
     visitante no es el dueño del diligenciamiento** — el acceso del propio
     estudiante a su resultado no se audita.
   - `ReporteController::index/exportarPdf/exportarExcel` → `consulta_reporte`
     y `exportacion_reporte`, con los filtros aplicados en `properties`.

   Granularidad elegida: "apertura de un registro individual" + generación de
   reportes. El listado general (`/panel/estudiantes`) no se audita: es un
   directorio agregado, no la apertura de una historia puntual.

2. **`aviso-no-diagnostico` en el panel del profesional (Resolución 3100 de
   2019).** `panel/estudiantes/show` y `panel/reportes/index` mostraban
   niveles de riesgo sin el aviso obligatorio "junto a todo resultado de
   riesgo". Se agregó el componente en ambas vistas (en la ficha, solo cuando
   hay al menos una evaluación). El PDF ya lo traía en el pie.

3. **HU-016 sin test propio.** El alcance inferido (11.2) la resolvía dentro
   de HU-014/015 sin pantalla propia, pero no había aserciones sobre "Sin
   evaluar", el riesgo más reciente en el listado ni el aviso en la ficha.
   Nuevo `HU016_NivelRiesgoTest.php`.

Suite tras el cierre de huecos: 180 tests, 438 assertions.
