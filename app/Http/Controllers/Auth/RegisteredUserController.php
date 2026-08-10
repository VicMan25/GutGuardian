<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Usuarios\Models\Perfil;
use App\Modules\Usuarios\Models\Programa;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $programas = Programa::orderBy('nombre')->get();

        return view('auth.register', compact('programas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'codigo_participante' => ['required', 'string', 'max:50', 'unique:users,codigo_participante'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'genero' => ['required', 'in:masculino,femenino,otro,prefiero_no_decir'],
            'edad' => ['required', 'integer', 'min:15', 'max:99'],
            'programa_id' => ['required', 'exists:programas,id'],
            'semestre' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'codigo_participante' => $request->codigo_participante,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('estudiante');

        Perfil::create([
            'user_id' => $user->id,
            'genero' => $request->genero,
            'edad' => $request->edad,
            'programa_id' => $request->programa_id,
            'semestre' => $request->semestre,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('estudiante.inicio');
    }
}
