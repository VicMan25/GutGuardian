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

- [x] Sprint 0 — completado
- [x] Sprint 1 — autenticación, registro, consentimiento (Ley 1581), roles y policies HU-021 implementados y con tests
- [x] Sprint 2 — captura y persistencia de encuestas (HU-004/005/006): `EncuestaController`
      (iniciar/reanudar, guardar por sección, confirmar, finalizar), persistencia por tipo de
      pregunta (escala/single/multiple/matriz), inmutabilidad tras completar
      (`DiligenciamientoPolicy`), componente `selector-unico` nuevo para preguntas tipo
      `single`. Corrigió además un bug preexistente en `matriz-sintomas` que impedía
      prellenar respuestas al reanudar.
- [x] Sprint 3 — seguimiento y visualización individual, **HU-007/008/011/012/013 reales**
      (corregidas tras extraer `docs/HISTORIAS_USUARIO.md` del documento de tesis — la primera
      entrega de este sprint tenía las etiquetas cruzadas, ver esa nota de extracción #3):
      `/inicio` resume el resultado más reciente del estudiante (HU-007), `/historial` lista
      cronológicamente los diligenciamientos completados (HU-011), `/seguimiento` con Chart.js
      grafica la evolución del riesgo con los colores de estado ya existentes (HU-008),
      `/alertas` genera y muestra un aviso interno cuando el nivel de riesgo cambia entre
      evaluaciones (HU-012, `AlertaService`) y `/perfil` permite actualizar género/edad/programa/
      semestre sin alterar registros históricos (HU-013, `PerfilController`). La evolución del
      dolor abdominal y el grid de 6 síntomas con frecuencia/temporalidad, ambos en
      `/seguimiento`, son funcionalidad complementaria del módulo `Reportes` sin HU numerada en
      el documento fuente. Paleta de las gráficas de síntomas validada con la skill dataviz
      (`scripts/validate_palette.js`) para no chocar con la escala de riesgo.
- [~] Sprint 4 — pipeline `ml/` (preparación, entrenamiento MNLogit, VIF, exportación) y
      `PredictorService`/`ExplicabilidadService` en Laravel implementados y con tests Pest
      (casos conocidos de softmax). Corre de punta a punta sobre datos **sintéticos**; no
      reemplaza el modelo real. Ver bloqueante abajo.
- [x] Sprint 5 — panel institucional y reportes (HU-014/015/016/017/018/019/022/023/024,
      **alcance inferido y documentado** en `docs/AVANCE_PROYECTO.md` — el documento de tesis
      solo tiene la narrativa de una línea de estas 9 HU en el backlog, sin criterios de
      aceptación redactados): `EstudianteController` (consulta individual y listado con
      búsqueda, HU-014/015/016 — reutiliza `SeguimientoService` de Sprint 3),
      `UsuarioController` (crear/editar/desactivar-reactivar cuentas de estudiantes,
      HU-017/018/019 — acotado a rol `estudiante`), `ReporteController` +
      `ReporteInstitucionalService` (distribución de niveles de riesgo filtrable por fecha y
      categoría, HU-022/023) con exportación a PDF (`barryvdh/laravel-dompdf`) y Excel
      (`maatwebsite/excel`, HU-024). Reutilizó permisos granulares ya sembrados desde Sprint 1
      en `RolesPermisosSeeder` (`consultar_estudiantes`, `crear_usuarios`, etc.) que no se
      habían usado hasta ahora. HU-019 agregó el primer bloqueo real de login por cuenta
      desactivada (`LoginRequest::authenticate`, `activo => true` en las credenciales).
      Cierre de huecos de cumplimiento (ver `docs/AVANCE_PROYECTO.md` §11.6):
      `AuditoriaClinicaService` deja rastro en `activity_log` de todo acceso de
      terceros a datos clínicos —ficha individual, resultado ajeno, consulta y
      exportación de reportes— (Ley 1581); `aviso-no-diagnostico` agregado a las
      vistas de ficha y de reportes del panel (Resolución 3100); HU-016 con test
      propio. Suite: 180 tests.
- [ ] Sprint 6
- [ ] **BLOQUEANTE (sigue abierto):** regla operativa real de la variable dependiente Y
      (requiere a Enfermería). `ml/comun.py::derivar_categoria_riesgo` implementa una regla
      PLACEHOLDER documentada solo para poder ejercitar el pipeline de ingeniería — no usar
      para tamizaje real.
- [ ] Dataset de 347 registros exportado y limpio para entrenamiento (el pipeline actual
      corre sobre `ml/generar_dataset_sintetico.py`, no sobre datos reales)

---

## 12. Sistema de diseño

### Tokens de color (CSS variables + utilidades Tailwind `gg-*`)

| Token CSS               | Hex       | Tailwind           | Uso                              |
|-------------------------|-----------|--------------------|----------------------------------|
| `--gg-papel`            | `#F6F7F4` | `gg-papel`         | Fondo de página                  |
| `--gg-superficie`       | `#FFFFFF` | `gg-superficie`    | Tarjetas y formularios           |
| `--gg-tinta`            | `#16302B` | `gg-tinta`         | Texto principal                  |
| `--gg-tinta-suave`      | `#5A6560` | `gg-tinta-suave`   | Texto secundario, equivalencias  |
| `--gg-borde`            | `#E0E3DC` | `gg-borde`         | Bordes hairline                  |
| `--gg-primario`         | `#1F5C4A` | `gg-primario`      | Marca, acciones, anillo de foco  |
| `--gg-primario-suave`   | `#E6EFEA` | `gg-primario-suave`| Fondo de opción seleccionada     |
| `--gg-riesgo-bajo`      | `#3E7D64` | `gg-riesgo-bajo`   | Segmento 0 de la pista           |
| `--gg-riesgo-medio`     | `#C08A2E` | `gg-riesgo-medio`  | Segmento 1 de la pista (ámbar)   |
| `--gg-riesgo-alto`      | `#9C4A32` | `gg-riesgo-alto`   | Segmento 2 (arcilla, NO rojo)    |

### Tipografías

| Familia                | Peso    | Tailwind          | Usos permitidos                                  |
|------------------------|---------|-------------------|--------------------------------------------------|
| Bricolage Grotesque    | 400/500 | `font-display`    | Solo títulos de pantalla y cifras grandes        |
| Source Sans 3          | 400/500 | `font-sans`       | Todo el cuerpo de texto                          |
| IBM Plex Mono          | 400     | `font-mono`       | Códigos de participante, probabilidades, OR      |

**Escala tipográfica:** `12 / 13 / 14 / 16 / 17 / 22 / 28 / 36 px` (Tailwind: `2xs xs sm base md xl 2xl 3xl`).
Solo pesos 400 y 500. Formato oración en todos los textos.

### Radios y bordes

- Controles interactivos: `rounded-control` (8 px)
- Tarjetas: `rounded-tarjeta` (12 px)
- Bordes: 1 px. Sin sombras, salvo el anillo de foco (`outline: 2px solid --gg-primario`).
- Sin degradados.

### Layouts

| Layout                          | Cuándo usarlo                                     |
|---------------------------------|---------------------------------------------------|
| `layouts/publico.blade.php`     | Login, registro, consentimiento                   |
| `layouts/estudiante.blade.php`  | Encuesta y resultado (1 col, `max-w-[640px]`)     |
| `layouts/profesional.blade.php` | Panel institucional (sidebar + contenido denso)   |

### Inventario de componentes Blade

| Componente              | Archivo                          | Descripción                                                     |
|-------------------------|----------------------------------|-----------------------------------------------------------------|
| `escala-frecuencia`     | `components/escala-frecuencia`   | Selector segmentado 4 niveles; Nunca=0…Siempre=3                |
| `escala-dolor`          | `components/escala-dolor`        | Variante 1-5 para P13                                           |
| `matriz-sintomas`       | `components/matriz-sintomas`     | Acordeón (móvil) / tabla (desktop) para P11, P12, P16, P17     |
| `opcion-multiple`       | `components/opcion-multiple`     | Checkboxes con "Ninguna" excluyente. Para P14, P15, P18, P20   |
| `pista-riesgo`          | `components/pista-riesgo`        | 3 segmentos + marcador + contribuciones + aviso-no-diagnostico  |
| `tarjeta`               | `components/tarjeta`             | Contenedor superficie, sin sombra                               |
| `boton`                 | `components/boton`               | Variantes: primario, secundario, fantasma                       |
| `campo-texto`           | `components/campo-texto`         | Input con label, error y ayuda integrados                       |
| `alerta`               | `components/alerta`              | Tipos: exito, error, aviso, info. Cierre opcional con Alpine    |
| `aviso-no-diagnostico`  | `components/aviso-no-diagnostico`| **Obligatorio** junto a todo resultado. Res. 3100/2019          |
| `barra-progreso`        | `components/barra-progreso`      | Progreso por sección (no por pregunta individual)               |

### Reglas de calidad de la interfaz

- Responsive desde 360 px.
- Foco de teclado visible en todo control interactivo.
- Contraste WCAG AA en todos los textos.
- Respetar `prefers-reduced-motion` (CSS global en `app.css`).
- **Prohibido** el color rojo (`#E53E3E` y similares) en la escala de riesgo.
- El componente `aviso-no-diagnostico` no puede modificarse sin autorización del comité de ética.

### Galería de componentes

Ruta `/ui-kit` (solo entorno `local`). Muestra todos los estados de todos los componentes.
Evidencia de los lineamientos de interfaz exigidos por el Sprint 0.
