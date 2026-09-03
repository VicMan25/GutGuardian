# Historias de usuario — GutGuardián

> Extraído textualmente del documento de tesis (Google Doc) el 2026-09-01.
> Fuente: https://docs.google.com/document/d/1mGfRjs47zWLOUASyGuUv1gS22fBELAmgnA7maNaZ80k
>
> El documento solo desarrolla la "Especificación de historia de usuario" (con
> criterios de aceptación Dado/Cuando/Entonces) hasta el **Sprint 4** inclusive.
> Las HU del backlog completo (tabla única, §"Product Backlog") sí existen para
> las 25 historias, pero HU-014 a HU-019 y HU-022 a HU-024 (Sprint 5) **no
> tienen todavía criterios de aceptación redactados** — solo la descripción de
> una línea y los puntajes de priorización. Sprint 5 y 6 tampoco tienen
> capítulo de "Etapa de análisis/diseño/codificación/pruebas" en el documento.

---

## HU-001 — Registro de estudiante

**Estado en el proyecto:** Completada (Sprint 1, CLAUDE.md §11).

Como estudiante quiero registrarme en el aplicativo web ingresando los datos básicos para acceder a las funcionalidades de monitoreo de salud digestiva.

**Actor:** Estudiante · **Prioridad:** Alta · **Complejidad:** Media

**Criterios de aceptación:**
1. Cuando el estudiante ingrese todos los datos obligatorios con un formato válido, entonces el sistema debe permitir crear la cuenta y mostrar un mensaje de confirmación.
2. Cuando el estudiante ingrese un correo electrónico que ya se encuentre registrado, entonces el sistema debe informar que existe una cuenta asociada al correo ingresado y evitar la creación de un registro duplicado.
3. Cuando el estudiante ingrese datos obligatorios incompletos, entonces el sistema debe indicar los campos que deben ser diligenciados antes de continuar.
4. Cuando el estudiante ingrese una contraseña que no cumpla las condiciones de seguridad establecidas, entonces el sistema debe informar los requisitos que debe cumplir la contraseña.
5. Cuando el estudiante ingrese contraseñas diferentes en los campos de contraseña y confirmación, entonces el sistema debe informar que las contraseñas no coinciden.
6. Cuando el registro se complete correctamente, entonces el sistema debe crear la cuenta con el rol de estudiante y sin privilegios administrativos.

---

## HU-002 — Inicio de sesión

**Estado en el proyecto:** Completada (Sprint 1).

Como estudiante quiero iniciar sesión con mis credenciales para acceder al sistema de forma segura.

**Actor:** Estudiante / Profesional de salud · **Prioridad:** Alta · **Complejidad:** Media

**Criterios de aceptación:**
1. Cuando el usuario ingrese credenciales válidas y la cuenta se encuentre activa, entonces el sistema debe autenticar al usuario y permitirle acceder al sistema.
2. Cuando el usuario ingrese un correo o contraseña incorrectos, entonces el sistema debe informar que las credenciales no son válidas y no permitir el acceso.
3. Cuando el usuario intente acceder con una cuenta deshabilitada, entonces el sistema debe impedir el acceso y mostrar un mensaje informativo.
4. Cuando el usuario inicie sesión correctamente, entonces el sistema debe identificar su rol y cargar únicamente las funcionalidades correspondientes a sus permisos.
5. Cuando el usuario acceda al sistema correctamente, entonces las credenciales o mecanismos de autenticación no deben quedar expuestos en la interfaz.

---

## HU-003 — Recuperación de contraseña

**Estado en el proyecto:** Completada (Sprint 1).

Como estudiante quiero recuperar mi contraseña en caso de olvidarla para no perder el acceso a mi cuenta.

**Actor:** Estudiante / Profesional de salud · **Prioridad:** Alta · **Complejidad:** Baja

**Criterios de aceptación:**
1. Cuando el usuario seleccione la opción de recuperar contraseña, entonces el sistema debe mostrar un formulario para ingresar el correo asociado a la cuenta.
2. Cuando el usuario ingrese un correo registrado, entonces el sistema debe iniciar el proceso de recuperación de contraseña mediante el mecanismo definido para la aplicación.
3. Cuando el usuario ingrese un correo con formato inválido, entonces el sistema debe solicitar la corrección del dato.
4. Cuando el usuario complete correctamente el proceso de recuperación, entonces debe poder establecer una nueva contraseña de acuerdo con las reglas de seguridad definidas.
5. Cuando se establezca una nueva contraseña, entonces la contraseña anterior debe dejar de ser válida para iniciar sesión.

---

## HU-004 — Registro de signos y síntomas gastrointestinales

**Estado en el proyecto:** Completada (Sprint 2) — implementada como captura de encuesta por secciones (`EncuestaController`), no como formulario único. Ver nota de empalme.

Como estudiante quiero diligenciar un formulario de signos y síntomas gastrointestinales para registrar mi estado digestivo.

**Actor:** Estudiante · **Prioridad:** Alta · **Complejidad:** Alta

**Criterios de aceptación:**
1. Cuando el estudiante ingrese al módulo de registro de signos y síntomas, entonces el sistema debe mostrar el formulario correspondiente.
2. Cuando el estudiante diligencie los campos obligatorios con información válida, entonces el sistema debe permitir continuar con el proceso de registro.
3. Cuando el estudiante omita un campo obligatorio, entonces el sistema debe indicar que dicho campo debe ser diligenciado antes de guardar la información.
4. Cuando el estudiante seleccione las opciones correspondientes a sus síntomas, entonces el sistema debe registrar las respuestas seleccionadas de acuerdo con la estructura definida para el formulario.
5. Cuando el estudiante complete correctamente el formulario, entonces el sistema debe permitir enviar y almacenar el registro.
6. Cuando ocurra un error durante el almacenamiento, entonces el sistema debe informar que el registro no pudo ser guardado y evitar mostrarlo como exitoso.
7. Cuando el estudiante complete el registro correctamente, entonces el sistema debe mostrar una confirmación de que la información fue almacenada.
8. Cuando el estudiante consulte posteriormente sus registros, entonces la información registrada debe conservar los valores ingresados originalmente.

> Nota del documento: "El formulario de signos y síntomas fue planteado como un instrumento de captura de información y no como una herramienta de diagnóstico."

---

## HU-005 — Registro de hábitos alimentarios

**Estado en el proyecto:** Completada (Sprint 2, misma implementación que HU-004 — el instrumento de 20 preguntas cubre nutricional + clínico en un solo diligenciamiento).

Como estudiante quiero diligenciar un formulario de hábitos alimentarios para relacionarlos con mi estado de salud digestiva.

**Actor:** Estudiante · **Prioridad:** Alta · **Complejidad:** Alta

**Criterios de aceptación:**
1. Cuando el estudiante ingrese al módulo de hábitos alimentarios, entonces el sistema debe mostrar el formulario correspondiente.
2. Cuando el estudiante diligencie los campos obligatorios con información válida, entonces el sistema debe permitir continuar con el registro.
3. Cuando el estudiante omita información obligatoria, entonces el sistema debe indicar los campos pendientes de diligenciar.
4. Cuando el estudiante seleccione las opciones correspondientes a sus hábitos alimentarios, entonces el sistema debe conservar las respuestas seleccionadas.
5. Cuando el estudiante finalice el formulario correctamente, entonces el sistema debe permitir almacenar la información.
6. Cuando ocurra un error durante el almacenamiento, entonces el sistema debe informar la situación y evitar confirmar un registro que no haya sido guardado.
7. Cuando el registro se almacene correctamente, entonces el sistema debe mostrar un mensaje de confirmación al estudiante.
8. Cuando el estudiante consulte posteriormente la información registrada, entonces las respuestas almacenadas deben corresponder con la información proporcionada durante el diligenciamiento.

---

## HU-006 — Persistencia de respuestas

**Estado en el proyecto:** Completada (Sprint 2).

Como estudiante quiero que mis respuestas queden almacenadas en el sistema para poder consultarlas y hacer seguimiento en diferentes momentos.

**Actor:** Estudiante · **Prioridad:** Alta · **Complejidad:** Media

**Criterios de aceptación:**
1. Cuando el estudiante complete correctamente un formulario, entonces el sistema debe almacenar las respuestas en la base de datos.
2. Cuando se almacene un registro, entonces este debe quedar asociado al identificador del usuario autenticado.
3. Cuando se almacene un registro, entonces debe registrarse la fecha y hora correspondiente al momento de la captura.
4. Cuando el estudiante complete registros en diferentes momentos, entonces cada diligenciamiento debe conservarse como un registro independiente.
5. Cuando ocurra un error de conexión o almacenamiento, entonces el sistema debe informar que la operación no pudo completarse.
6. Cuando el estudiante vuelva a consultar sus registros, entonces el sistema debe recuperar la información previamente almacenada sin alteraciones.
7. Cuando un usuario intente consultar registros pertenecientes a otro estudiante, entonces el sistema debe impedir el acceso a dicha información de acuerdo con las reglas de autorización definidas.
8. Cuando se almacene información correspondiente a un formulario, entonces los datos deben conservar una estructura que permita su posterior procesamiento y análisis.

---

## HU-007 — Resumen de respuestas registradas

**Estado en el proyecto:** ⚠️ Ver nota de empalme — lo implementado en Sprint 3 bajo la etiqueta "HU-007" (`/historial`, `HistorialController`) corresponde en realidad al texto de **HU-011** del documento fuente ("Consulta del historial de registros"), no a este.

Como estudiante quiero ver un resumen de mis últimas respuestas registradas para tener una visión general de mi estado digestivo.

**Actor:** Estudiante · **Prioridad:** Alta · **Complejidad:** Media

**Criterios de aceptación:**
1. Cuando el estudiante acceda al módulo de seguimiento, entonces el sistema debe mostrar un resumen de sus registros más recientes.
2. Cuando el estudiante tenga registros almacenados, entonces el sistema debe presentar la información correspondiente a los registros disponibles.
3. Cuando el estudiante no tenga registros registrados, entonces el sistema debe mostrar un mensaje informativo indicando que aún no existen datos para consultar.
4. Cuando existan múltiples registros, entonces el sistema debe identificar cuáles corresponden a los registros más recientes de acuerdo con su fecha de creación.
5. Cuando el estudiante consulte el resumen, entonces únicamente debe visualizar información asociada a su propia cuenta.
6. Cuando se agregue un nuevo registro, entonces el resumen debe actualizarse para reflejar la información disponible más reciente.
7. Cuando ocurra un error durante la consulta, entonces el sistema debe informar que la información no pudo ser cargada y evitar mostrar datos incompletos como si fueran definitivos.

> Nota del documento: planteada como "una vista simplificada de la información registrada", distinta de un historial completo.

---

## HU-008 — Gráfica de evolución

**Estado en el proyecto:** Completada (Sprint 3, `/seguimiento`, Chart.js) — coincide con el documento.

Como estudiante quiero visualizar una gráfica sencilla de mis registros a lo largo del tiempo para identificar cambios en mi salud digestiva.

**Actor:** Estudiante · **Prioridad:** Media · **Complejidad:** Media

**Criterios de aceptación:**
1. Cuando el estudiante tenga suficientes registros para establecer una evolución, entonces el sistema debe presentar una gráfica basada en los datos almacenados.
2. Cuando existan registros correspondientes a diferentes fechas, entonces la información debe representarse siguiendo un orden cronológico.
3. Cuando el estudiante consulte la gráfica, entonces únicamente deben utilizarse los registros asociados a su cuenta.
4. Cuando el estudiante no tenga registros suficientes para generar una tendencia, entonces el sistema debe mostrar un mensaje indicando que aún no existe información suficiente para visualizar una evolución.
5. Cuando se agregue un nuevo registro, entonces la gráfica debe actualizarse para incorporar la nueva información.
6. Cuando el usuario consulte los valores representados, entonces la interfaz debe proporcionar información suficiente para interpretar los datos mostrados, incluyendo fecha y valor correspondiente.
7. Cuando se produzca un error al recuperar los datos, entonces el sistema debe informar que la gráfica no pudo ser generada correctamente.

---

## HU-009 — Clasificación del nivel de riesgo gastrointestinal

**Estado en el proyecto:** Sprint 4 (`~`) — pipeline y `PredictorService`/`ExplicabilidadService` funcionan de punta a punta, pero sobre datos sintéticos; bloqueante de la regla clínica de Y sigue abierto.

Como estudiante quiero recibir una clasificación de nivel de riesgo gastrointestinal generada por el modelo predictivo para orientar mi autocuidado.

**Actor:** Estudiante · **Prioridad:** Alta · **Complejidad:** Alta

**Criterios de aceptación:**
1. Cuando el estudiante disponga de información suficiente para realizar una evaluación, entonces el sistema debe permitir ejecutar el proceso de clasificación.
2. Cuando los datos proporcionados cumplan con las condiciones requeridas por el modelo, entonces el sistema debe procesarlos y generar una clasificación de riesgo.
3. Cuando el modelo genere un resultado válido, entonces el sistema debe mostrar el nivel de riesgo correspondiente al estudiante.
4. Cuando el sistema genere una clasificación, entonces el resultado debe quedar asociado al estudiante y a la fecha de evaluación.
5. Cuando no exista información suficiente para realizar la clasificación, entonces el sistema debe informar al estudiante que debe completar los registros requeridos.
6. Cuando ocurra un error durante el procesamiento del modelo, entonces el sistema debe informar que no fue posible generar la clasificación y no debe mostrar un resultado inválido como definitivo.
7. Cuando el estudiante consulte nuevamente su resultado, entonces el sistema debe mostrar la clasificación correspondiente al último proceso de evaluación disponible.
8. Cuando el modelo genere una clasificación diferente a la anterior, entonces el nuevo resultado debe quedar registrado para permitir su comparación y seguimiento posterior.
9. Cuando se genere una clasificación, entonces el sistema debe presentar el resultado mediante una interfaz comprensible para el estudiante.
10. Cuando el resultado sea presentado al estudiante, entonces la interfaz debe aclarar que la clasificación es una herramienta de orientación y seguimiento y no un diagnóstico médico.

---

## HU-010 — Proyección de salud gastrointestinal

**Estado en el proyecto:** ⚠️ Ver nota de empalme — CLAUDE.md §8 registra una corrección de alcance deliberada: se implementó como **evolución histórica**, no como proyección hacia el futuro, porque el modelo es transversal y no puede proyectar. El texto original de la HU sí pide explícitamente una proyección con "datos históricos vs. datos proyectados" diferenciados visualmente.

Como estudiante quiero visualizar una proyección de mi salud gastrointestinal a lo largo del tiempo para anticipar posibles cambios en mi condición digestiva.

**Actor:** Estudiante · **Prioridad:** Alta · **Complejidad:** Alta

**Criterios de aceptación:**
1. Cuando el estudiante disponga de registros históricos suficientes, entonces el sistema debe permitir visualizar una proyección basada en la información disponible.
2. Cuando existan registros correspondientes a diferentes fechas, entonces el sistema debe organizarlos cronológicamente antes de generar la representación.
3. Cuando se genere una proyección, entonces el sistema debe diferenciar visualmente los datos históricos de los valores proyectados.
4. Cuando no exista información suficiente para realizar la proyección, entonces el sistema debe mostrar un mensaje indicando que aún no se dispone de datos suficientes.
5. Cuando el estudiante consulte la proyección, entonces únicamente deben utilizarse los registros asociados a su cuenta.
6. Cuando se incorporen nuevos registros, entonces la información disponible para la proyección debe actualizarse.
7. Cuando se produzca un error durante el cálculo o generación de la proyección, entonces el sistema debe informar que la proyección no pudo ser generada correctamente.
8. Cuando se muestre una proyección, entonces la interfaz debe indicar claramente que corresponde a una estimación y no a una certeza sobre el estado futuro de salud.
9. Cuando el estudiante consulte la proyección, entonces debe poder identificar el periodo correspondiente a los datos históricos y el periodo proyectado.

> Nota del documento: "sus resultados deben interpretarse como estimaciones derivadas de los datos utilizados por el sistema y no como una predicción determinista de la condición futura del estudiante" — esto en realidad respalda la corrección de alcance de CLAUDE.md: incluso el documento fuente pide encuadrar el resultado como estimación, no como certeza.

---

## HU-011 — Consulta del historial de registros

**Estado en el proyecto:** ⚠️ Ver nota de empalme — lo que hoy corre bajo la etiqueta "HU-011" en Sprint 3 (grid de 6 síntomas con frecuencia/temporalidad) no es esto. Esta HU (historial completo cronológico con detalle por registro) es lo que realmente se construyó en `/historial` y se etiquetó como HU-007.

Como estudiante quiero consultar el historial de mis registros anteriores para revisar mi evolución y los datos que he ingresado previamente.

**Actor:** Estudiante · **Prioridad:** Alta · **Complejidad:** Media

**Criterios de aceptación:**
1. Cuando el estudiante acceda al historial, entonces el sistema debe mostrar los registros asociados a su cuenta.
2. Cuando existan varios registros, entonces estos deben organizarse cronológicamente de acuerdo con la fecha de registro.
3. Cuando el estudiante seleccione un registro, entonces el sistema debe permitir consultar el detalle de las respuestas almacenadas.
4. Cuando el estudiante no tenga registros, entonces el sistema debe mostrar un mensaje informativo indicando que no existen registros disponibles.
5. Cuando se consulte el historial, entonces el sistema no debe mostrar registros pertenecientes a otros usuarios.
6. Cuando se incorporen nuevos registros, entonces estos deben aparecer posteriormente en el historial correspondiente.
7. Cuando ocurra un error de consulta, entonces el sistema debe informar la situación y permitir que el usuario intente nuevamente la operación.

---

## HU-012 — Alertas internas de riesgo

**Estado en el proyecto:** ❌ No implementada. Lo etiquetado como "HU-011/HU-012" en Sprint 3 es el grid de síntomas (`seguimientoSintomas()`), que no corresponde a este texto. La tabla `alertas` ya existe en el modelo de datos (CLAUDE.md §5: `id, user_id, evaluacion_id, tipo, mensaje, leida_at`) pero no hay controlador, vista ni lógica de generación/lectura de alertas todavía.

Como estudiante quiero recibir una alerta dentro del aplicativo cuando mi nivel de riesgo cambie para tomar decisiones oportunas sobre mi salud.

**Actor:** Estudiante · **Prioridad:** Media · **Complejidad:** Media

**Criterios de aceptación:**
1. Cuando el sistema detecte que existe un nuevo nivel de riesgo diferente al registrado anteriormente, entonces debe generar una alerta interna asociada al estudiante.
2. Cuando el nivel de riesgo permanezca sin cambios, entonces el sistema no debe generar una nueva alerta por cambio de nivel.
3. Cuando exista una alerta pendiente de lectura, entonces el sistema debe mostrar un indicador que permita identificarla.
4. Cuando el estudiante consulte una alerta, entonces el sistema debe mostrar información sobre el cambio detectado y la fecha en que fue generado.
5. Cuando el estudiante marque la alerta como revisada, entonces el sistema debe actualizar su estado y evitar que continúe apareciendo como pendiente.
6. Cuando no existan cambios en el nivel de riesgo, entonces el sistema debe informar que no existen nuevas alertas relacionadas con cambios de riesgo.
7. Cuando no exista todavía un resultado de clasificación de riesgo, entonces el sistema no debe generar una alerta de cambio de riesgo y debe mantener el módulo preparado para recibir resultados cuando el componente predictivo se encuentre disponible.

> Nota del documento: la funcionalidad de alertas se diseñó deliberadamente como infraestructura independiente de la generación del riesgo (para no depender del algoritmo), a implementar en Sprint 3 y conectar con la clasificación real en HU-009 (Sprint 4). Como HU-009 ya está implementada (aunque con modelo placeholder), HU-012 podría implementarse ahora enganchándose al resultado de `ResultadoController`.

---

## HU-013 — Actualización del perfil del estudiante

**Estado en el proyecto:** ❌ No implementada. Lo etiquetado como "HU-013" en Sprint 3 (`evolucionDolor()`, gráfica de dolor abdominal P13) **no corresponde a ninguna HU del documento fuente** — es una funcionalidad inventada que no está en el backlog. La actualización real de perfil (género/edad/programa/semestre, con separación explícita frente a los registros históricos) sigue sin construirse.

Como estudiante quiero actualizar mis datos de perfil para mantener mi información personal y clínica vigente.

**Actor:** Estudiante · **Prioridad:** Media · **Complejidad:** Baja

**Criterios de aceptación:**
1. Cuando el estudiante acceda a su perfil, entonces el sistema debe mostrar la información actualmente registrada.
2. Cuando el estudiante modifique un campo permitido con información válida, entonces el sistema debe permitir guardar los cambios.
3. Cuando el estudiante ingrese información con un formato inválido, entonces el sistema debe mostrar el mensaje de validación correspondiente.
4. Cuando el estudiante intente guardar campos obligatorios vacíos, entonces el sistema debe impedir la actualización hasta completar la información requerida.
5. Cuando la actualización se complete correctamente, entonces el sistema debe almacenar los nuevos datos y mostrar un mensaje de confirmación.
6. Cuando ocurra un error durante la actualización, entonces el sistema debe informar que los cambios no pudieron ser guardados.
7. Cuando el estudiante vuelva a consultar su perfil, entonces debe visualizar la información actualizada.
8. Cuando el estudiante actualice sus datos personales, entonces los registros históricos de salud digestiva no deben modificarse retroactivamente.

> Nota del documento: separación explícita entre datos de perfil y registros históricos — "permite actualizar información personal sin alterar los datos registrados en momentos anteriores".

---

## HU-014 — Consulta individual de estudiante

**Estado en el proyecto:** Implementada (Sprint 5) — criterios inferidos y detalle en docs/AVANCE_PROYECTO.md §11.3.

Como profesional de salud quiero buscar y consultar el registro individual de un estudiante para hacer seguimiento a su estado digestivo.

**Actor:** Profesional de salud · **Prioridad:** Media · **Complejidad:** — · **Aporte:** 9 · **Urgencia:** 7

**Criterios de aceptación:** no redactados todavía en el documento fuente (sin capítulo de "Etapa de análisis" para Sprint 5). Solo existe la descripción anterior en la tabla de backlog.

---

## HU-015 — Listado general de estudiantes

**Estado en el proyecto:** Implementada (Sprint 5) — ver docs/AVANCE_PROYECTO.md §11.3.

Como profesional de salud quiero ver el listado general de todos los estudiantes registrados para tener una visión global del grupo monitoreado.

**Actor:** Profesional de salud · **Aporte:** 8 · **Urgencia:** 7

**Criterios de aceptación:** no redactados todavía en el documento fuente.

---

## HU-016 — Visualización del nivel de riesgo por estudiante

**Estado en el proyecto:** Implementada (Sprint 5), sin pantalla propia — resuelta dentro de HU-014/015, ver docs/AVANCE_PROYECTO.md §11.2 y §11.3.

Como profesional de salud quiero visualizar el nivel de riesgo asignado a cada estudiante para identificar casos que requieran atención prioritaria.

**Actor:** Profesional de salud · **Aporte:** 9 · **Urgencia:** 8

**Criterios de aceptación:** no redactados todavía en el documento fuente.

---

## HU-017 — Creación de usuarios estudiantes

**Estado en el proyecto:** Implementada (Sprint 5) — ver docs/AVANCE_PROYECTO.md §11.3.

Como profesional de salud quiero crear nuevos usuarios estudiantes en el sistema para incorporarlos al proceso de monitoreo.

**Actor:** Profesional de salud · **Aporte:** 8 · **Urgencia:** 8

**Criterios de aceptación:** no redactados todavía en el documento fuente.

---

## HU-018 — Edición de información de usuario

**Estado en el proyecto:** Implementada (Sprint 5) — ver docs/AVANCE_PROYECTO.md §11.3.

Como profesional de salud quiero editar la información de un usuario registrado para corregir o actualizar sus datos cuando sea necesario.

**Actor:** Profesional de salud · **Aporte:** 7 · **Urgencia:** 7

**Criterios de aceptación:** no redactados todavía en el documento fuente.

---

## HU-019 — Desactivación de cuenta de usuario

**Estado en el proyecto:** Implementada (Sprint 5), incluye el primer bloqueo real de login por cuenta desactivada — ver docs/AVANCE_PROYECTO.md §11.3. Relacionada con la regla dura de CLAUDE.md §5: "desactivar cuenta ≠ eliminar historial" (SoftDeletes), ya prevista en el modelo de datos.

Como profesional de salud quiero desactivar la cuenta de un usuario para suspender su acceso sin eliminar su historial clínico.

**Actor:** Profesional de salud · **Aporte:** 7 · **Urgencia:** 7

**Criterios de aceptación:** no redactados todavía en el documento fuente (pero el propio enunciado ya fija la restricción central: suspender acceso sin borrar historial).

---

## HU-020 — Asignación de roles

**Estado en el proyecto:** Completada (Sprint 1).

Como profesional de salud quiero asignar roles dentro del sistema para controlar qué funcionalidades puede usar cada tipo de usuario.

**Actor:** Profesional de salud autorizado · **Prioridad:** Media · **Complejidad:** Media

**Criterios de aceptación:**
1. Cuando un usuario autorizado consulte la información de un usuario registrado, entonces el sistema debe permitir visualizar el rol actualmente asignado.
2. Cuando un usuario autorizado seleccione un rol permitido, entonces el sistema debe actualizar el rol del usuario seleccionado.
3. Cuando un usuario sin permisos suficientes intente modificar un rol, entonces el sistema debe impedir la operación.
4. Cuando se asigne el rol de estudiante, entonces el usuario debe tener acceso únicamente a las funcionalidades correspondientes a dicho rol.
5. Cuando se asigne el rol de profesional de salud, entonces el usuario debe poder acceder a los módulos autorizados para dicho rol.
6. Cuando se modifique un rol, entonces el cambio debe quedar almacenado y aplicarse en los siguientes accesos del usuario.

---

## HU-021 — Restricción de acceso a módulos sensibles

**Estado en el proyecto:** Completada (Sprint 1).

Como profesional de salud quiero restringir el acceso a módulos sensibles para proteger la información clínica almacenada.

**Actor:** Profesional de salud · **Prioridad:** Alta · **Complejidad:** Media

**Criterios de aceptación:**
1. Cuando un estudiante intente acceder a un módulo exclusivo para profesionales de salud, entonces el sistema debe impedir el acceso al módulo.
2. Cuando un usuario no autenticado intente acceder directamente a una ruta protegida, entonces el sistema debe redirigirlo a la pantalla de inicio de sesión.
3. Cuando un usuario autenticado intente acceder a un módulo para el cual no posee permisos, entonces el sistema debe denegar el acceso y mostrar una respuesta informativa.
4. Cuando un profesional de salud acceda a un módulo autorizado, entonces el sistema debe permitir el acceso según los permisos asociados a su rol.
5. Cuando cambie el rol de un usuario, entonces los permisos de acceso deben actualizarse de acuerdo con el nuevo rol.

---

## HU-022 — Reporte del comportamiento general de niveles de riesgo

**Estado en el proyecto:** Implementada (Sprint 5) — ver docs/AVANCE_PROYECTO.md §11.3.

Como profesional de salud quiero visualizar un reporte del comportamiento general de los niveles de riesgo de los estudiantes para identificar patrones en la población monitoreada.

**Actor:** Profesional de salud · **Aporte:** 9 · **Urgencia:** 7 (Prioridad "Alta" en la tabla de backlog)

**Criterios de aceptación:** no redactados todavía en el documento fuente.

---

## HU-023 — Filtrado de reportes por fecha o nivel de riesgo

**Estado en el proyecto:** Implementada (Sprint 5) — ver docs/AVANCE_PROYECTO.md §11.3.

Como profesional de salud quiero filtrar los reportes por fecha o nivel de riesgo para segmentar el análisis según mis necesidades.

**Actor:** Profesional de salud · **Aporte:** 8 · **Urgencia:** 6

**Criterios de aceptación:** no redactados todavía en el documento fuente.

---

## HU-024 — Exportación de reportes (PDF / Excel)

**Estado en el proyecto:** Implementada (Sprint 5) — ver docs/AVANCE_PROYECTO.md §11.3. CLAUDE.md §2 ya fija las librerías a usar: `barryvdh/laravel-dompdf` + `maatwebsite/excel`.

Como profesional de salud quiero exportar los reportes generados en formato PDF o Excel para compartirlos con otros actores institucionales.

**Actor:** Profesional de salud · **Aporte:** 7 · **Urgencia:** 6

**Criterios de aceptación:** no redactados todavía en el documento fuente.

---

## HU-025 — Cierre de sesión

**Estado en el proyecto:** Completada (Sprint 1).

Como usuario quiero cerrar sesión para proteger mis datos personales y clínicos.

**Actor:** Usuario autenticado · **Prioridad:** Alta · **Complejidad:** Baja

**Criterios de aceptación:**
1. Cuando el usuario seleccione la opción de cerrar sesión, entonces el sistema debe finalizar la sesión activa.
2. Cuando el usuario cierre sesión correctamente, entonces debe ser redirigido a la pantalla de inicio de sesión.
3. Cuando el usuario intente regresar mediante el historial del navegador después de cerrar sesión, entonces el sistema no debe permitir el acceso a las rutas protegidas sin autenticarse nuevamente.
4. Cuando el usuario vuelva a iniciar sesión, entonces el sistema debe recuperar el acceso de acuerdo con el rol que tenga asignado.

---

## Notas de extracción

1. **El documento fuente solo tiene 25 HU (HU-001 a HU-025)**, agrupadas en 6 sprints según la tabla real del backlog (línea ~2499 del documento): Sprint 1 = HU-001,002,003,020,021,025; Sprint 2 = HU-004,005,006; Sprint 3 = HU-007,008,011,012,013; Sprint 4 = HU-009,010; Sprint 5 = HU-014,015,016,017,018,019,022,023,024. No hay HU-026 en adelante.

2. **Sección obsoleta detectada dentro del propio documento (§1.5.5.4):** una narrativa temprana de planeación describe "cinco sprints" con nombres genéricos (Sprint 5 ahí se llama "Pruebas integrales y despliegue"), que **no coincide** con la tabla real de backlog usada en la ejecución (§2.4.1.3, 6 sprints, Sprint 5 = "Panel institucional y reportes", Sprint 6 = pruebas y despliegue). Es contenido temprano/descartado del documento; la tabla de backlog y CLAUDE.md §8 son la fuente correcta y ya coinciden entre sí.

3. **Discrepancia importante para el empalme — Sprint 3 quedó mal mapeado contra el documento real:**
   - Lo implementado como **"HU-007"** (`/historial`, lista completa cronológica) es en realidad el texto de **HU-011** ("Consulta del historial de registros").
   - Lo implementado como **"HU-011/HU-012"** (grid de 6 síntomas con frecuencia/temporalidad) no corresponde al texto de ninguna de las dos — ni HU-011 (que es el historial completo) ni HU-012 (que son alertas internas por cambio de nivel de riesgo, tabla `alertas` ya en el modelo de datos).
   - Lo implementado como **"HU-013"** (evolución del dolor abdominal, gráfica P13) **no existe como HU en el documento** — es contenido inventado. La HU-013 real es "Actualización del perfil del estudiante" y no está construida.
   - HU-007 real ("Resumen de respuestas registradas" — vista simplificada de los últimos registros, distinta de un historial completo) tampoco está construida.
   - HU-012 real (alertas internas de riesgo) tampoco está construida, pese a que la tabla `alertas` ya existe en el esquema.
   - Esto no invalida el trabajo de Sprint 3 (la gráfica de evolución de riesgo, HU-008, sí coincide, y el grid de síntomas / evolución de dolor son funcionalidad útil), pero si el jurado revisa el documento de tesis contra el código, encontrará que las etiquetas HU no calzan. Se recomienda decidir explícitamente antes de sustentar: (a) renombrar/documentar la correspondencia real en la monografía, o (b) construir las HU-007, 011, 012, 013 reales como trabajo adicional y mantener lo ya hecho como funcionalidad extra sin numerar. Esta decisión no se tomó en esta extracción — es una decisión de producto que le corresponde al equipo.

4. **HU-010** sí coincide en espíritu con la corrección de alcance que ya documenta CLAUDE.md §8 (de "proyección" a "evolución histórica"): el propio texto de la HU pide que el resultado se presente como "estimación", no como certeza, lo cual respalda (no contradice) la decisión ya tomada.

5. **HU-014 a HU-019 y HU-022 a HU-024 (todo el Sprint 5) no tienen criterios de aceptación redactados en el documento fuente** — solo la descripción de una línea en la tabla de backlog (Aporte/Urgencia/Prioridad). Antes de implementar Sprint 5 con el mismo rigor que los sprints anteriores, hace falta que alguien (Product Owner / asesora) redacte los criterios Dado/Cuando/Entonces para estas 9 HU, igual que se hizo para las 16 ya desarrolladas — de lo contrario el alcance de Sprint 5 tendría que inferirse de cero, con el mismo riesgo de desalineación que ya ocurrió en Sprint 3.
