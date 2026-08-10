<?php

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
    Route::get('/inicio', function () {
        return view('estudiante.inicio');
    })->name('estudiante.inicio');
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
