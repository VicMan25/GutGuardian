<?php

use App\Http\Controllers\AlertaController;
use App\Http\Controllers\EncuestaController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\EstudianteInicioController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ResultadoController;
use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Raíz: redirige al área correcta según rol o al login
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.inicio');
        }
        if ($user->hasRole('profesional_salud')) {
            return redirect()->route('panel.inicio');
        }

        return redirect()->route('estudiante.inicio');
    }

    return redirect()->route('login');
});

// Área del estudiante
Route::middleware(['auth', 'consentimiento', 'role:estudiante'])->group(function () {
    Route::get('/inicio', [EstudianteInicioController::class, 'show'])->name('estudiante.inicio');

    // Seguimiento y visualización individual — HU-008 y HU-011 reales; el resto
    // de /seguimiento es funcionalidad complementaria sin HU numerada (ver
    // docs/HISTORIAS_USUARIO.md, nota 3).
    Route::get('/historial', [HistorialController::class, 'show'])->name('historial.show');
    Route::get('/seguimiento', [SeguimientoController::class, 'show'])->name('seguimiento.show');

    // HU-007: resumen de respuestas registradas (resuelto por estudiante.inicio)

    // HU-012: alertas internas de cambio de nivel de riesgo
    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::patch('/alertas/{alerta}/leer', [AlertaController::class, 'marcarLeida'])->name('alertas.marcarLeida');

    // HU-013: actualización del perfil sociodemográfico del estudiante
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');

    // Captura y persistencia de encuestas (HU-004, HU-005, HU-006)
    Route::get('/encuesta', [EncuestaController::class, 'iniciar'])->name('encuesta.iniciar');
    Route::get('/encuesta/{diligenciamiento}/confirmar', [EncuestaController::class, 'confirmar'])->name('encuesta.confirmar');
    Route::post('/encuesta/{diligenciamiento}/finalizar', [EncuestaController::class, 'finalizar'])->name('encuesta.finalizar');
    Route::get('/encuesta/{diligenciamiento}/{orden}', [EncuestaController::class, 'seccion'])
        ->whereNumber('orden')->name('encuesta.seccion');
    Route::post('/encuesta/{diligenciamiento}/{orden}', [EncuestaController::class, 'guardar'])
        ->whereNumber('orden')->name('encuesta.guardar');
});

// Resultado de riesgo — accesible a estudiante (propio), profesional_salud y admin.
// La autorización fina la resuelve DiligenciamientoPolicy, no el middleware de rol.
Route::middleware(['auth', 'consentimiento'])->group(function () {
    Route::get('/resultado/{diligenciamiento}', [ResultadoController::class, 'show'])
        ->name('resultado.show');
});

// Área del profesional de salud
Route::middleware(['auth', 'consentimiento', 'role:profesional_salud|admin'])
    ->prefix('panel')->name('panel.')->group(function () {
        Route::get('/', [PanelController::class, 'inicio'])->name('inicio');

        // HU-014/HU-015/HU-016: consulta individual, listado y nivel de riesgo
        Route::middleware('permission:consultar_estudiantes')
            ->get('/estudiantes', [EstudianteController::class, 'index'])->name('estudiantes.index');

        // HU-017: creación de usuarios estudiantes — rutas estáticas /crear
        // deben registrarse antes de /{estudiante} (dinámica), o el binding
        // implícito intentaría resolver "crear" como id de estudiante.
        Route::middleware('permission:crear_usuarios')->group(function () {
            Route::get('/estudiantes/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
            Route::post('/estudiantes', [UsuarioController::class, 'store'])->name('usuarios.store');
        });

        // HU-018: edición de información de usuario
        Route::middleware('permission:editar_usuarios')->group(function () {
            Route::get('/estudiantes/{estudiante}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
            Route::put('/estudiantes/{estudiante}', [UsuarioController::class, 'update'])->name('usuarios.update');
        });

        // HU-019: desactivación/reactivación de cuenta
        Route::middleware('permission:desactivar_usuarios')
            ->post('/estudiantes/{estudiante}/estado', [UsuarioController::class, 'alternarActivo'])
            ->name('usuarios.alternarActivo');

        Route::middleware('permission:consultar_estudiantes')
            ->get('/estudiantes/{estudiante}', [EstudianteController::class, 'show'])->name('estudiantes.show');

        // HU-022/HU-023: reporte general del comportamiento de riesgo, filtrable
        Route::middleware('permission:generar_reportes')
            ->get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');

        // HU-024: exportación de reportes en PDF y Excel
        Route::middleware('permission:exportar_reportes')->group(function () {
            Route::get('/reportes/exportar/pdf', [ReporteController::class, 'exportarPdf'])->name('reportes.exportarPdf');
            Route::get('/reportes/exportar/excel', [ReporteController::class, 'exportarExcel'])->name('reportes.exportarExcel');
        });
    });

// Área de administración
Route::middleware(['auth', 'consentimiento', 'role:admin'])->group(function () {
    Route::get('/admin', function () {
        return view('admin.inicio');
    })->name('admin.inicio');
});

// Galería de componentes — solo entorno local, evidencia de Sprint 0
if (app()->environment('local')) {
    Route::get('/ui-kit', function () {
        return view('ui-kit');
    })->name('ui-kit');
}

require __DIR__.'/auth.php';
