# GutGuardián

Aplicativo web para monitorear hábitos alimentarios y sintomatología gastrointestinal
en estudiantes de la Universidad Mariana. Clasifica el nivel de riesgo digestivo en tres
categorías mediante regresión logística multinomial.

> **Aviso legal:** esta herramienta es de autocuidado y tamizaje. No emite diagnósticos
> médicos ni sustituye la consulta con un profesional de salud
> (Resolución 3100 de 2019 — Software como Dispositivo Médico).

---

## Requisitos

| Herramienta | Versión mínima |
|-------------|---------------|
| PHP | 8.2 |
| Composer | 2.x |
| MySQL | 8.0 |
| Node.js | 20.x |
| npm | 10.x |

> En Windows se recomienda XAMPP 8.2. Habilitar la extensión `ext-gd` en `php.ini`
> para que la exportación Excel funcione.

---

## Instalación local

### 1. Clonar el repositorio

```bash
git clone <url-del-repo> gutguardian
cd gutguardian
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` y ajustar las credenciales de MySQL:

```
DB_DATABASE=gutguardian
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

### 4. Crear la base de datos

En MySQL (o desde phpMyAdmin en XAMPP):

```sql
CREATE DATABASE gutguardian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Ejecutar migraciones y sembrar roles

```bash
php artisan migrate --seed
```

### 6. Instalar dependencias de frontend y compilar assets

```bash
npm install
npm run build
```

Para desarrollo con recarga automática:

```bash
npm run dev
```

### 7. Levantar el servidor de desarrollo

```bash
php artisan serve
```

La aplicación estará disponible en `http://localhost:8000`.

---

## Comandos frecuentes

```bash
# Restablecer la BD completa (destruye todos los datos)
php artisan migrate:fresh --seed

# Ejecutar tests
php artisan test

# Formatear código con Pint
./vendor/bin/pint

# Análisis estático con Larastan
./vendor/bin/phpstan analyse
```

---

## Estructura de módulos

```
app/Modules/
├── Auth/        Consentimiento informado
├── Usuarios/    Perfiles y programas académicos
├── Encuestas/   Instrumento de 20 preguntas, diligenciamiento y respuestas
├── Analitica/   Motor de inferencia multinomial (PredictorService)
├── Reportes/    Visualización, gráficas (Chart.js), exportación PDF/Excel
└── Panel/       Administración institucional y alertas
```

---

## Importante — datos sensibles

Los 347 registros reales de participantes **nunca** deben subirse a este repositorio.
Trabajar en desarrollo con factories y datos sintéticos (`php artisan db:seed`).
Los patrones de archivos de datos reales están explícitamente excluidos en `.gitignore`.

---

## Licencia

Uso académico — trabajo de grado, Universidad Mariana, 2025–2026.
