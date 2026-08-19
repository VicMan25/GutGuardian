<?php

use App\Http\Controllers\EncuestaController;
use App\Http\Controllers\EstudianteInicioController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\ResultadoController;
use App\Http\Controllers\SeguimientoController;
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

    // Seguimiento y visualización individual (HU-007, 008, 011, 012, 013)
    Route::get('/historial', [HistorialController::class, 'show'])->name('historial.show');
    Route::get('/seguimiento', [SeguimientoController::class, 'show'])->name('seguimiento.show');

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
Route::middleware(['auth', 'consentimiento', 'role:profesional_salud|admin'])->group(function () {
    Route::get('/panel', function () {
        return view('panel.inicio');
    })->name('panel.inicio');
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
