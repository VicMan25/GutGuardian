# GutGuardián — Contexto del proyecto

> Este archivo es el contexto permanente para Claude Code. Va en la raíz del repositorio.
> Actualízalo al cerrar cada sprint.

---

## 1. Qué es esto

Aplicativo web para monitorear hábitos alimentarios y sintomatología gastrointestinal
en estudiantes de la Universidad Mariana, y clasificar su **nivel de riesgo digestivo**
en tres categorías mediante regresión logística multinomial.

Es el producto del objetivo específico 1.3.2.3 de un trabajo de grado interdisciplinar
(Ingeniería de Sistemas + Enfermería + Nutrición y Dietética). El entregable comprometido
es un **MVP validable**, no un producto comercial.

**Restricción crítica:** el sistema NO emite diagnósticos médicos. Es una herramienta de
autocuidado y tamizaje. Esto debe ser explícito en la interfaz (Resolución 3100 de 2019 —
Dispositivos Médicos Software). Cualquier texto que sugiera diagnóstico es un defecto.

---

## 2. Stack (fijado por el documento de tesis, no cambiar)

| Componente | Elección | Razón |
|---|---|---|
| Framework | **Laravel 13** (PHP 8.3+) | Definido en la tesis §2.4.1.3.1 |
| Base de datos | **MySQL 8** | Definido en la tesis §2.4.1.3.1 |
| Arquitectura | **Monolítica**, patrón **MVC**, organización interna modular | Definido en §2.4.1.3.2 y §2.4.1.3.3 |
| Vistas | Blade + Alpine.js (Breeze) | Mantiene el monolito, sin SPA |
| Estilos | Tailwind CSS | Viene con Breeze |
| Gráficas | Chart.js | HU-008 |
| Roles | spatie/laravel-permission | HU-020, HU-021 |
| Auditoría | spatie/laravel-activitylog | Ley 1581 de 2012 |
| Exportación | barryvdh/laravel-dompdf + maatwebsite/excel | HU-024 |
| Tests | Pest | Aseguramiento de calidad §1.5.5.5 |
| Entrenamiento del modelo | **Python (statsmodels / scikit-learn), fuera de la app** | PHP no tiene librería multinomial confiable |

**No introducir**: microservicios, React/Vue SPA, MongoDB, APIs externas de IA.
Todo eso contradice la arquitectura documentada y no es defendible en sustentación.

---

## 3. Módulos funcionales (§2.4.1.3.3)

Organizar el código en `app/Modules/` para que cada módulo del documento sea rastreable:

```
app/Modules/
├── Auth/         # autenticación y control de acceso
├── Usuarios/     # gestión de usuarios y roles
├── Encuestas/    # captura de información clínica y alimentaria
├── Analitica/    # procesamiento: inferencia del modelo multinomial
├── Reportes/     # visualización de resultados, gráficas, exportación
└── Panel/        # panel administrativo / institucional
```

---

## 4. Roles del sistema

- **estudiante** — diligencia encuestas, ve su historial, su nivel de riesgo y sus gráficas.
- **profesional_salud** — consulta estudiantes, ve niveles de riesgo, genera y exporta reportes,
  crea/edita/desactiva usuarios.
- **admin** — todo lo anterior + gestión de versiones del modelo predictivo.

Regla dura: un estudiante **jamás** puede ver datos de otro estudiante. Toda consulta de
registros clínicos debe estar filtrada por policy de Laravel, no solo por la vista.

---

## 5. Modelo de datos propuesto

Diseño genérico por instrumento (permite versionar la encuesta sin migraciones nuevas):

```
users                  id, name, email, password, codigo_participante(unique), activo, timestamps
roles / permissions    (spatie)
perfiles               user_id, genero, edad, programa_id, semestre
programas              id, nombre

instrumentos           id, nombre, version, tipo(nutricional|clinico), activo
secciones              id, instrumento_id, nombre, orden
preguntas              id, seccion_id, codigo(P01..P20), enunciado,
                       tipo(single|multiple|matriz|escala), orden
items_pregunta         id, pregunta_id, etiqueta, orden      -- filas de matriz: "Diarrea", "Vómito"...
opciones               id, pregunta_id, etiqueta, valor_numerico, orden

diligenciamientos      id, user_id, instrumento_id, estado, completado_at
respuestas             id, diligenciamiento_id, pregunta_id, item_pregunta_id(null),
                       opcion_id(null), valor_numerico, valor_texto

versiones_modelo       id, nombre, version, entrenado_at, activo,
                       coeficientes(json), metricas(json), mapa_variables(json)
evaluaciones_riesgo    id, diligenciamiento_id, version_modelo_id, categoria(0|1|2),
                       prob_0, prob_1, prob_2, contribuciones(json), evaluado_at
alertas                id, user_id, evaluacion_id, tipo, mensaje, leida_at

consentimientos        id, user_id, version_politica, aceptado_at, ip
activity_log           (spatie)
```

**Reglas de integridad:**
- `respuestas` nunca guarda texto libre de la opción: guarda `opcion_id` + `valor_numerico`.
  El análisis estadístico depende de esa codificación numérica.
- `evaluaciones_riesgo` guarda **siempre** `version_modelo_id`. Un resultado sin trazabilidad
  de qué modelo lo produjo es inservible para TRIPOD+AI.
- Nunca borrar registros clínicos (HU-019: desactivar cuenta ≠ eliminar historial). Usar SoftDeletes.

---

## 6. Instrumento de recolección (20 preguntas)

### Sociodemográfico
Género · Edad · Programa · Semestre

### Sección nutricional (P1–P10)
Escala de 4 niveles salvo indicación:
`Siempre (diario) | Algunas veces (1–6/sem) | Ocasionalmente (1+/mes) | Nunca`

1. Consumo de frutas
2. Consumo de ensaladas frescas
3. Consumo de alimentos integrales
4. Consumo de embutidos (salchicha, chorizo, salchichón)
5. Consumo de alimentos empaquetados
6. Consumo de azúcares refinados (chocolates)
7. Consumo de frituras
8. Tiempos de comida al día — *7 opciones*: 1 / 2 / 3 / 4 / 5 / más de 5 / ninguna
9. Consumo de alcohol y de tabaco — *matriz de 2 ítems*, escala de 4 niveles
10. Lavado de manos antes de comer

### Sección clínica (P11–P20)

11. Temporalidad de síntomas — *matriz*: Diarrea, Dolor abdominal, Vómito, Náuseas,
    Estreñimiento, Fiebre.
    Opciones: Últimos 6 meses / Últimos 2 meses / Último mes / Última semana / Último año / Nunca
12. Frecuencia de esos mismos 6 síntomas en el último mes — *matriz*, escala de 4 niveles
13. Escala de dolor abdominal (últimos 6 meses) — 1 a 5
14. Antecedentes personales — *selección múltiple*: parásitos intestinales, inflamación
    del intestino, infecciones bacterianas digestivas, úlceras digestivas, estrés, sobrepeso,
    gastroenteritis, hipersensibilidad visceral, ninguna
15. Antecedentes familiares — *mismas opciones que P14*
16. Temporalidad de consumo de medicamentos — *matriz*: antidepresivos, antiinflamatorios,
    antibióticos, antiespasmódicos, antidiarreicos, laxantes, corticosteroides
17. Frecuencia de esos medicamentos en el último mes — *matriz*, escala de 4 niveles
18. Estilo de vida — *selección múltiple*: sobrecarga académica, estrés psicológico,
    sedentarismo, practica deporte, ninguna
19. Frecuencia de práctica deportiva — escala de 4 niveles
20. Enfermedades padecidas — *selección múltiple*: reflujo gastroesofágico, colitis,
    enfermedad de Crohn, colecistitis, enfermedad celíaca, apendicitis, cálculos, anemia, ninguna

---

## 7. Motor predictivo

**Variable dependiente (3 categorías):**
- `0` — ausencia o mínima presencia de síntomas gastrointestinales
- `1` — síntomas recurrentes o combinación de factores de riesgo nutricionales y clínicos
- `2` — sintomatología persistente + múltiples antecedentes médicos + patrones alimentarios de riesgo

**PENDIENTE BLOQUEANTE:** la regla operativa que deriva Y a partir de P11–P13 y P20 aún no
está definida en el documento. Debe construirse con el equipo de Enfermería y quedar escrita
como algoritmo determinista antes del Sprint 4. Sin eso no hay modelo entrenable.

**Flujo del modelo:**

1. *Offline (Python, carpeta `ml/` del repo, fuera del runtime de Laravel):*
   entrenar `statsmodels.MNLogit` sobre los 347 registros → exportar
   `storage/app/models/modelo_v1.json` con: coeficientes por categoría, intercepto,
   orden y codificación de variables, métricas de desempeño.

2. *Online (Laravel, `app/Modules/Analitica`):*
   `PredictorService` carga el JSON de la versión activa, arma el vector de predictores
   desde las respuestas, calcula el predictor lineal por categoría y aplica **softmax**.
   Es aritmética pura — sin dependencias externas, testeable con Pest.

3. *Explicabilidad (exigida por la tesis):* mostrar los 3–5 predictores con mayor
   contribución al resultado, expresados como odds ratio (`exp(β)`) en lenguaje llano.
   Ejemplo: "El consumo diario de frituras multiplica por 2.3 la probabilidad de
   clasificarse en riesgo alto."

**Limitación conocida a documentar:** 347 registros con 3 clases y ~25 predictores es un
ratio de eventos-por-variable ajustado. Se requiere reducción de variables o regularización,
y debe reportarse honestamente en la monografía (PROBAST+AI, dominio "análisis estadístico").

---

## 8. Historias de usuario y plan de sprints

| Sprint | HU | Entregable | Duración |
|---|---|---|---|
| 0 | — | Entorno, repositorio, arquitectura, lineamientos de seguridad | 2 sem |
| 1 | 001, 002, 003, 020, 021, 025 | Autenticación y control de acceso | 2 sem |
| 2 | 004, 005, 006 | Captura y persistencia de encuestas | 2 sem |
| 3 | 007, 008, 011, 012, 013 | Seguimiento y visualización individual | 2 sem |
| 4 | 009, 010 | Módulo predictivo multinomial | 3 sem |
| 5 | 014–019, 022, 023, 024 | Panel institucional y reportes | 2 sem |
| 6 | — | Pruebas integrales y despliegue piloto | 2 sem |

**Nota sobre HU-010:** redactada como "proyección de salud a lo largo del tiempo". Un modelo
transversal no puede proyectar el futuro. Implementar como **visualización de la evolución
histórica** de los registros del estudiante y ajustar la redacción en la monografía.

---

## 9. Cumplimiento legal (afecta el código, no es solo papeleo)

- **Ley 1581 de 2012 (habeas data):** módulo de consentimiento con versión y fecha; derecho
  de acceso, actualización y rectificación; log de auditoría de todo acceso a datos clínicos.
- **Ley 1273 de 2009 (delitos informáticos):** hashing de contraseñas (bcrypt/argon2 por
  defecto en Laravel), HTTPS obligatorio, control de acceso por policies.
- **Resolución 3100 de 2019 (SaMD):** aviso visible de que el aplicativo no realiza
  diagnóstico y no sustituye consulta médica. Debe aparecer junto a **todo** resultado de riesgo.
- **Resolución 1995 de 1999 + Resolución 2003 de 2014:** confidencialidad y conservación
  de datos de salud; retención de 5 años.

---

## 10. Convenciones de trabajo

- **Idioma:** código, nombres de tablas y de rutas en **español** (el jurado lee el código).
  Comentarios y commits en español.
- **Commits:** Conventional Commits — `feat(encuestas): ...`, `fix(analitica): ...`.
  Referenciar la HU: `feat(auth): registro de estudiante (HU-001)`.
- **Ramas:** `main` estable · `sprint/N` por sprint · `hu/HU-0XX` por historia.
- **Calidad:** `./vendor/bin/pint` antes de cada commit; test Pest por cada HU con criterio
  de aceptación verificable.
- **Migraciones:** una por entidad, nunca editar una migración ya ejecutada en `main`.
- **Seeders:** el instrumento completo (20 preguntas con sus opciones y valores numéricos)
  debe cargarse por seeder, no a mano.
- **Datos reales:** los 347 registros son datos sensibles de personas. Nunca subirlos al
  repositorio. Trabajar con factories y datos sintéticos en desarrollo.

---

## 11. Estado actual

- [x] Sprint 0 — pendiente de arranque
- [ ] Sprint 1 · [ ] Sprint 2 · [ ] Sprint 3 · [ ] Sprint 4 · [ ] Sprint 5 · [ ] Sprint 6
- [ ] **BLOQUEANTE:** regla operativa de la variable dependiente Y (requiere a Enfermería)
- [ ] Dataset de 347 registros exportado y limpio para entrenamiento
