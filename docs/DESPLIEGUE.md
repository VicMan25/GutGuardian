# Tutorial de ingreso, configuración y despliegue — GutGuardián

> Producto esperado §1.5.6 de la tesis: «un tutorial que guiará el proceso de
> ingreso, configuración y despliegue de la solución web». La instalación en un
> equipo de desarrollo está en el `README.md`; este documento cubre el
> **despliegue piloto** en un servidor y el **uso por rol**.

---

## 1. Requisitos del servidor

| Componente | Versión | Nota |
|---|---|---|
| PHP | 8.3 o superior | extensiones `pdo_mysql`, `mbstring`, `openssl`, `gd`, `zip`, `intl`, `fileinfo` |
| MySQL | 8.0 | base con `utf8mb4_unicode_ci` |
| Servidor web | Nginx o Apache | con certificado TLS (HTTPS obligatorio, Ley 1273 de 2009) |
| Composer | 2.x | solo para instalar |
| Node.js | 20.x | solo para compilar los recursos de interfaz (`npm run build`) |

## 2. Instalación en el servidor

```bash
git clone <url-del-repo> gutguardian && cd gutguardian
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los valores de producción:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gutguardian.umariana.edu.co   # siempre https

DB_CONNECTION=mysql
DB_DATABASE=gutguardian
DB_USERNAME=gutguardian_app      # usuario propio, sin privilegios globales
DB_PASSWORD=********

SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true       # la cookie de sesión solo viaja por HTTPS

MAIL_MAILER=smtp                 # necesario para la recuperación de contraseña (HU-003)
```

Crear la base de datos y cargar los datos mínimos (roles, programas,
instrumento de 20 preguntas y la versión inicial del modelo):

```bash
php artisan migrate --force
php artisan db:seed --force
```

> **No ejecutar** `UsuariosTestSeeder` en producción: crea cuentas de prueba con
> contraseñas conocidas.

Optimizar y dar permisos de escritura:

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
```

El servidor web debe apuntar a la carpeta `public/`.

## 3. Crear las cuentas institucionales

El registro público solo crea estudiantes. Las cuentas de administrador y de
profesional de salud se crean por consola; la contraseña se pide sin mostrarse
en pantalla:

```bash
php artisan gutguardian:crear-usuario admin
php artisan gutguardian:crear-usuario profesional_salud
```

Cada cuenta acepta el consentimiento informado en su primer ingreso.

## 4. Verificación previa a abrir el piloto

```bash
php artisan gutguardian:verificar-despliegue
```

El comando revisa HTTPS, modo depuración, cifrado de sesión, conexión a la base
de datos, instrumento activo, roles, existencia de un administrador y validez de
la versión activa del modelo. Termina con error si algo impide operar y con
**avisos** para lo que conviene corregir. Mientras el modelo activo sea el
provisional (datos sintéticos y regla Y provisional) aparecerá el aviso «Modelo
entrenado con datos reales»: es esperado hasta que Enfermería defina la regla
clínica (ver `CLAUDE.md` §7).

## 5. Actualizar el modelo predictivo (rol admin)

1. Fuera de la aplicación, en la carpeta `ml/`, ejecutar el pipeline
   (`preparar_datos.py` → `entrenar_modelo.py` → `exportar_modelo.py`). Genera
   un archivo JSON con coeficientes, métricas y orden de variables.
2. Ingresar como administrador → **Modelo predictivo** → «Registrar una
   versión» y cargar el JSON. El sistema lo valida (categorías, coeficientes
   completos, variables calculables); si falla, explica el motivo y no lo guarda.
3. Revisar métricas, matriz de confusión y coeficientes en el detalle de la versión.
4. Pulsar **Activar esta versión**. Las evaluaciones nuevas usan esa versión;
   las anteriores conservan la versión que las produjo (trazabilidad TRIPOD+AI).
   No hace falta redesplegar.

## 6. Uso por rol

| Rol | Ingreso | Qué puede hacer |
|---|---|---|
| Estudiante | Se registra en `/register`, acepta el consentimiento | Diligenciar la encuesta, ver su resultado con factores explicativos, historial, seguimiento gráfico, alertas, perfil, **Mis datos** (copia en PDF y quién consultó su información) y responder el cuestionario de usabilidad |
| Profesional de salud | Cuenta creada por consola | Listado y ficha de estudiantes con nivel de riesgo, crear/editar/desactivar estudiantes, reporte institucional filtrable y exportable a PDF/Excel |
| Administrador | Cuenta creada por consola | Todo lo anterior, gestión de versiones del modelo, **Validación** (SUS, tiempos, errores) y **Auditoría** de accesos a datos clínicos |

## 7. Prueba piloto de usabilidad (objetivo 1.3.2.4)

1. Invitar al grupo piloto de estudiantes a registrarse y completar la encuesta.
2. Al terminar, el inicio del estudiante le ofrece el cuestionario SUS (10
   afirmaciones, voluntario, una sola vez).
3. El administrador consulta **Validación**: puntaje SUS medio, mediana,
   aceptabilidad, media por afirmación, tiempo de diligenciamiento y tasa de
   errores de ingreso de datos.
4. «Exportar SUS (CSV)» descarga las respuestas **anonimizadas** (sin nombre,
   correo ni código) para el análisis de la monografía.

## 8. Operación y conservación de datos

- **Copias de seguridad:** respaldo diario de la base de datos (`mysqldump`)
  cifrado y fuera del servidor. Los datos de salud se conservan como mínimo
  5 años (Resolución 1995 de 1999); la aplicación nunca borra registros
  clínicos (desactivar una cuenta no elimina su historial).
- **Auditoría:** revisar periódicamente **Auditoría** para detectar accesos no
  justificados a fichas o resultados.
- **Registros de errores:** `storage/logs/laravel.log`. Con `APP_DEBUG=false` el
  usuario nunca ve trazas técnicas.
- **Datos reales:** los 347 registros del estudio no se suben al repositorio ni
  se cargan en el servidor de la aplicación; solo se usan en `ml/`, en un equipo
  del equipo investigador.
