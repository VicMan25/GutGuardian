<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Usuarios\Models\Perfil;
use App\Modules\Usuarios\Models\Programa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * HU-017/HU-018/HU-019: gestión de cuentas de estudiantes por parte del
 * profesional de salud. Acotado a rol `estudiante` — HU-014/015, la misma
 * narrativa del backlog, tampoco hablan de gestionar otros profesionales o
 * administradores (ver docs/AVANCE_PROYECTO.md, sección Sprint 5).
 */
class UsuarioController extends Controller
{
    public function create(): View
    {
        return view('panel.estudiantes.crear', [
            'programas' => Programa::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validado = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'codigo_participante' => ['required', 'string', 'max:50', 'unique:users,codigo_participante'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'genero' => ['required', 'in:masculino,femenino,otro,prefiero_no_decir'],
            'edad' => ['required', 'integer', 'min:15', 'max:99'],
            'programa_id' => ['required', 'exists:programas,id'],
            'semestre' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $estudiante = User::create([
            'name' => $validado['name'],
            'email' => $validado['email'],
            'codigo_participante' => $validado['codigo_participante'],
            'password' => Hash::make($validado['password']),
        ]);

        $estudiante->assignRole('estudiante');

        Perfil::create([
            'user_id' => $estudiante->id,
            'genero' => $validado['genero'],
            'edad' => $validado['edad'],
            'programa_id' => $validado['programa_id'],
            'semestre' => $validado['semestre'],
        ]);

        return redirect()->route('panel.estudiantes.show', $estudiante)
            ->with('status', 'estudiante-creado');
    }

    public function edit(User $estudiante): View
    {
        abort_unless($estudiante->hasRole('estudiante'), 404);

        return view('panel.estudiantes.editar', [
            'estudiante' => $estudiante,
            'programas' => Programa::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, User $estudiante): RedirectResponse
    {
        abort_unless($estudiante->hasRole('estudiante'), 404);

        $validado = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($estudiante->id)],
            'codigo_participante' => ['required', 'string', 'max:50', Rule::unique('users', 'codigo_participante')->ignore($estudiante->id)],
            'genero' => ['required', 'in:masculino,femenino,otro,prefiero_no_decir'],
            'edad' => ['required', 'integer', 'min:15', 'max:99'],
            'programa_id' => ['required', 'exists:programas,id'],
            'semestre' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $estudiante->update([
            'name' => $validado['name'],
            'email' => $validado['email'],
            'codigo_participante' => $validado['codigo_participante'],
        ]);

        $estudiante->perfil->update([
            'genero' => $validado['genero'],
            'edad' => $validado['edad'],
            'programa_id' => $validado['programa_id'],
            'semestre' => $validado['semestre'],
        ]);

        return redirect()->route('panel.estudiantes.show', $estudiante)
            ->with('status', 'estudiante-actualizado');
    }

    /**
     * HU-019: alterna el acceso del estudiante sin tocar su historial
     * clínico. `activo = false` bloquea el login (LoginRequest::authenticate).
     */
    public function alternarActivo(User $estudiante): RedirectResponse
    {
        abort_unless($estudiante->hasRole('estudiante'), 404);

        $estudiante->update(['activo' => ! $estudiante->activo]);

        return back()->with('status', $estudiante->activo ? 'estudiante-reactivado' : 'estudiante-desactivado');
    }
}
