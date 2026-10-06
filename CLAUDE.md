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
- [x] Sprint 5 — panel institucional y reportes (HU-014/015/016/017/018/019/022/023/024;
      el alcance se infirió en `docs/AVANCE_PROYECTO.md` y la tesis ya lo formalizó en
      §2.4.1.4.5, Tablas 62–75, con los mismos criterios): `EstudianteController` (consulta individual y listado con
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
- [x] Sprint 6 (software) — HU-026 gestión de versiones del modelo (`/admin/modelos`,
      `ImportadorModeloService`, también usado por `VersionModeloSeeder`); validación del
      objetivo 1.3.2.4: cuestionario SUS (`/usabilidad`), tasa de errores por envío de sección
      y tiempo de diligenciamiento, resumidos en `/admin/validacion` con exportación CSV
      anonimizada; Ley 1581: `/mis-datos` (datos, consentimiento, quién consultó, copia PDF) y
      visor `/admin/auditoria`; seguridad: `CabecerasSeguridad` y HTTPS forzado en producción;
      comandos `gutguardian:crear-usuario` y `gutguardian:verificar-despliegue`; tutorial
      `docs/DESPLIEGUE.md`. Corrigió además dos defectos que impedían dibujar **todas** las
      gráficas (seguimiento, ficha del panel y reporte): `@json` dentro de `x-init="…"` emitía
      comillas dobles crudas que cortaban el atributo (ahora `Js::from`), y `BarController` no
      estaba registrado en `app.js`. Comando solo-desarrollo `gutguardian:simular-seguimiento`
      (usa `EvaluacionService`, extraído de `ResultadoController`). Suite: 230 tests. **Pendiente de campo:** ejecutar la prueba piloto
      con estudiantes y desplegar en el servidor institucional.
- [ ] **BLOQUEANTE (sigue abierto):** regla operativa real de la variable dependiente Y
      (requiere a Enfermería). `ml/comun.py::derivar_categoria_riesgo` implementa una regla
      PLACEHOLDER documentada solo para poder ejercitar el pipeline de ingeniería — no usar
      para tamizaje real.
- [~] Dataset de 347 registros: ETL listo (`ml/etl_datos_reales.py` → `ml/datos/dataset_real.csv`,
      ignorado por git). La versión activa del modelo (v1.0) sigue siendo la entrenada con datos
      sintéticos; reentrenar con los datos reales depende de la regla Y.
- [ ] Monografía: el complemento con los cambios resaltados está en `docs/monografia/`
      (Sprint 6, HU-026, objetivo 1.3.2.4, conclusiones y recomendaciones de Ingeniería,
      marcadores pendientes del documento).
- [x] Rediseño UX/UI (post Sprint 6, sin cambios de lógica ni rutas): identidad visual propia
      (superficie de marca verde con patrón de «flujo», elevación suave, microinteracciones),
      espacio reservado para el logo oficial (`x-marca`), barra de pestañas inferior en móvil
      para el estudiante, sidebar de marca en el panel, `x-insignia-riesgo` e `x-icono`
      unificados, estado de carga en envíos POST (`app.js`), controles de encuesta táctiles
      (2×2 en móvil). Corrigió además: navegación del layout estudiante que llevaba a
      profesionales/admin a rutas 403 al abrir `/resultado`, `<select>` del registro sin
      `<label for>`, y modificadores de opacidad de Tailwind que no funcionaban con los tokens.
- [x] Encuesta guiada: una pregunta por paso, matriz ítem a ítem, índice lateral en desktop,
      avance automático en respuestas únicas. Solo presentación (sin cambios en
      `EncuestaController`); tests en `tests/Feature/Sprint2/EncuestaGuiadaTest.php`.

---

## 12. Sistema de diseño

### Tokens de color (CSS variables + utilidades Tailwind `gg-*`)

Cada token tiene además su versión en canales (`--gg-papel-rgb: 246 247 244`) y Tailwind la usa
con `<alpha-value>`, así que funcionan las opacidades (`bg-gg-papel/85`). Al agregar un token,
definir ambas variables en `app.css`.

| Token CSS               | Hex       | Tailwind           | Uso                              |
|-------------------------|-----------|--------------------|----------------------------------|
| `--gg-papel`            | `#F6F7F4` | `gg-papel`         | Fondo de página                  |
| `--gg-superficie`       | `#FFFFFF` | `gg-superficie`    | Tarjetas y formularios           |
| `--gg-tinta`            | `#16302B` | `gg-tinta`         | Texto principal                  |
| `--gg-tinta-suave`      | `#55615B` | `gg-tinta-suave`   | Texto secundario, equivalencias  |
| `--gg-borde`            | `#E0E3DC` | `gg-borde`         | Bordes hairline                  |
| `--gg-primario`         | `#1F5C4A` | `gg-primario`      | Marca, acciones, anillo de foco  |
| `--gg-primario-suave`   | `#E6EFEA` | `gg-primario-suave`| Fondo de opción seleccionada     |
| `--gg-primario-hondo`   | `#174A3B` | `gg-primario-hondo`| Hover del primario, superficie de marca |
| `--gg-primario-noche`   | `#0F2E25` | `gg-primario-noche`| Fondo más profundo de la marca, tooltips |
| `--gg-acento`           | `#CFE3B4` | `gg-acento`        | Detalles **sobre verde** (íconos, indicador activo). Nunca texto sobre papel |
| `--gg-papel-hondo`      | `#EDF0EA` | `gg-papel-hondo`   | Pistas de barras, hover de fantasma |
| `--gg-riesgo-bajo`      | `#3E7D64` | `gg-riesgo-bajo`   | Segmento 0 de la pista           |
| `--gg-riesgo-medio`     | `#C08A2E` | `gg-riesgo-medio`  | Segmento 1 de la pista (ámbar)   |
| `--gg-riesgo-alto`      | `#9C4A32` | `gg-riesgo-alto`   | Segmento 2 (arcilla, NO rojo)    |

### Tipografías

| Familia                | Peso    | Tailwind          | Usos permitidos                                  |
|------------------------|---------|-------------------|--------------------------------------------------|
| Bricolage Grotesque    | 400/500 | `font-display`    | Solo títulos de pantalla y cifras grandes        |
| Source Sans 3          | 400/500 | `font-sans`       | Todo el cuerpo de texto                          |
| IBM Plex Mono          | 400     | `font-mono`       | Códigos de participante, probabilidades, OR      |

**Escala tipográfica:** `12 / 13 / 14 / 16 / 17 / 22 / 28 / 36 / 46 px` (Tailwind: `2xs xs sm base md xl 2xl 3xl 4xl`).
Solo pesos 400 y 500. Formato oración en todos los textos.

| Rol                         | Clase                                   |
|-----------------------------|-----------------------------------------|
| Héroe (acceso, saludo)      | `font-display text-4xl` (`text-3xl` en móvil) |
| Título de pantalla          | `font-display text-3xl`                 |
| Título de sección / tarjeta | `font-display text-xl`                  |
| Enunciado de pregunta, cuerpo | `text-base`                           |
| Botones                     | `text-base` (md/lg), `text-sm` (sm)     |
| Texto secundario, ayudas    | `text-sm`                               |
| Rótulo («eyebrow»)          | `.gg-rotulo` (13 px, tracking)          |
| Errores de campo            | `text-sm text-gg-riesgo-alto`           |
| Mínimo (códigos mono, equivalencias) | `text-2xs` — nunca para información que haya que leer |

### Radios, bordes, elevación y fondos

- Controles interactivos: `rounded-control` (10 px). Tarjetas: `rounded-tarjeta` (16 px).
  Bloques protagonistas (héroes, resultado): `rounded-bloque` (24 px).
- Bordes: 1 px `gg-borde`.
- Elevación (sombras teñidas de la tinta verde, nunca negras): `shadow-elev-1` (tarjetas),
  `shadow-elev-2` (tarjeta protagonista), `shadow-elev-3` (menús, barras flotantes, tarjeta de
  acceso), `shadow-boton` (botón primario).
- Degradados: **solo** en superficies de marca (`.gg-marca`, verde profundo con patrón de
  «flujo») y en el fondo de página (`.gg-fondo`, halos casi imperceptibles). Nunca en botones,
  texto ni en la escala de riesgo.
- `.gg-flujo-claro`: el mismo patrón, muy tenue, sobre superficies claras (estados vacíos,
  tarjeta del último resultado).
- Una utilidad `bg-*` de Tailwind gana sobre `.gg-marca` (capa components): no combinarlas.

### Movimiento

- Curva única `--gg-curva` (`cubic-bezier(0.22, 1, 0.36, 1)`); 150 ms para estados, 200–460 ms
  para entradas.
- `.gg-entrada` en un contenedor anima a sus hijos de forma escalonada al cargar.
- `.gg-interactiva` (o `<x-tarjeta interactiva>`): elevación de 2 px al pasar el cursor; solo
  en elementos clicables.
- `app.js` marca con `[data-cargando]` el botón que envía un formulario **POST** (indicador
  giratorio, evita doble envío). Un formulario puede excluirse con `data-sin-carga`.
- Todo respeta `prefers-reduced-motion`.

### Logo

El logo oficial **no se inventa**. `x-marca` reserva su espacio (recuadro punteado) en el
acceso, la cabecera del estudiante y el sidebar del panel. Para incorporarlo basta con copiar
el archivo en `public/img/logo.svg` (o `logo.png`); ninguna vista necesita cambios.

### Layouts

| Layout                          | Cuándo usarlo                                     |
|---------------------------------|---------------------------------------------------|
| `layouts/publico.blade.php`     | Login, registro, consentimiento. Panel `.gg-marca` fijo a la izquierda desde `lg` (marca, propuesta de valor, adelanto de la pista); tarjeta de formulario `max-w-[460px]` a la derecha. En móvil/tablet: banda de marca arriba y la tarjeta superpuesta. |
| `layouts/estudiante.blade.php`  | Prop `ancho`: `estrecho` (640 px, por defecto: encuesta, resultado, formularios) o `amplio` (1040 px: inicio, seguimiento). Cabecera translúcida con marca, navegación en píldora (md+) y menú de cuenta (Perfil, Mis datos, Salir). En móvil: **barra de pestañas inferior**. Si quien lo ve no es estudiante (resultado abierto desde el panel), oculta la navegación del estudiante y ofrece «Volver al panel». |
| `layouts/profesional.blade.php` | Panel institucional: sidebar `.gg-marca` (drawer en móvil) + topbar translúcida. El contenido se acota a `max-w-[1180px] mx-auto` y entra con `.gg-entrada`. |

Los tres layouts abren con un enlace «Saltar al contenido» (`sr-only` hasta recibir foco) que apunta a `#contenido` en el `<main>`.

### Inventario de componentes Blade

| Componente              | Archivo                          | Descripción                                                     |
|-------------------------|----------------------------------|-----------------------------------------------------------------|
| `escala-frecuencia`     | `components/escala-frecuencia`   | Selector segmentado 4 niveles; Nunca=0…Siempre=3                |
| `escala-dolor`          | `components/escala-dolor`        | Variante 1-5 para P13                                           |
| `matriz-sintomas`       | `components/matriz-sintomas`     | Un ítem a la vez (todas las pantallas) con chips de avance y resumen editable. P09, P11, P12, P16, P17 |
| `opcion-multiple`       | `components/opcion-multiple`     | Checkboxes con "Ninguna" excluyente. Para P14, P15, P18, P20   |
| `pista-riesgo`          | `components/pista-riesgo`        | 3 segmentos + marcador + contribuciones + aviso-no-diagnostico  |
| `tarjeta`               | `components/tarjeta`             | Contenedor superficie, `elev-1`; prop `interactiva` para clicables |
| `boton`                 | `components/boton`               | Variantes: primario, secundario, fantasma, claro (sobre verde). Tamaños sm/md/lg; props `icono` e `icono-final` |
| `campo-texto`           | `components/campo-texto`         | Input 46 px con label, error y ayuda; mostrar/ocultar en contraseñas |
| `marca`                 | `components/marca`               | Espacio del logo + nombre. Tonos claro/oscuro, tamaños sm/md/lg  |
| `insignia-riesgo`       | `components/insignia-riesgo`     | Píldora de nivel (punto + texto); `null` → «Sin evaluar»         |
| `icono`                 | `components/icono`               | Juego único de íconos de trazo (24 px, 1.75) para nav y acciones |
| `alerta`               | `components/alerta`              | Tipos: exito, error, aviso, info. Cierre opcional con Alpine    |
| `aviso-no-diagnostico`  | `components/aviso-no-diagnostico`| **Obligatorio** junto a todo resultado. Res. 3100/2019          |
| `barra-progreso`        | `components/barra-progreso`      | Progreso por sección (en la cabecera); el progreso por pregunta lo dibuja la sección guiada |

Los componentes de pregunta aceptan `destacada` (enunciado grande, sin código: lo muestra el paso).

### Encuesta guiada (`estudiante/encuesta/seccion`)

Una pregunta por paso dentro del **mismo** `<form>` por sección (Alpine `encuestaGuiada` en
`app.js`): los pasos ocultos siguen en el DOM y se envían, y el servidor valida y guarda igual.
- Paso inicial calculado en el servidor: primera pregunta con error de validación, si no la
  primera sin responder.
- Escala y single avanzan solas **solo tras toque/clic** (nunca con flechas del teclado);
  múltiple y matriz avanzan con «Siguiente». Enter avanza de pregunta.
- `novalidate`: el `required` nativo no puede mostrarse en un paso oculto; `enviar()` lleva a la
  primera pendiente. La validación del servidor sigue siendo la red de seguridad.
- Desktop: índice lateral de la sección. Móvil: layout en modo `enfoque` (sin barra inferior),
  barra de acciones fija al pulgar. Aviso del navegador al salir con respuestas sin guardar.
- El contenedor de pasos usa `overflow-x-clip`: la transición horizontal ensanchaba el viewport
  en móvil. Demo navegable en `/ui-kit#guiada`.

### Reglas de calidad de la interfaz

- Responsive desde 360 px. Sin desbordamiento horizontal; tablas del panel pasan a tarjetas en móvil.
- Objetivos táctiles de 44 px como mínimo (inputs, botones md, opciones de encuesta).
- Foco de teclado visible en todo control interactivo.
- Contraste WCAG AA en todos los textos.
- Respetar `prefers-reduced-motion` (CSS global en `app.css`).
- **Prohibido** el color rojo (`#E53E3E` y similares) en la escala de riesgo.
- El componente `aviso-no-diagnostico` no puede modificarse sin autorización del comité de ética.

### Galería de componentes

Ruta `/ui-kit` (solo entorno `local`). Muestra todos los estados de todos los componentes.
Evidencia de los lineamientos de interfaz exigidos por el Sprint 0.
