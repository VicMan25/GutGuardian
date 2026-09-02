<?php

namespace App\Http\Controllers;

use App\Modules\Usuarios\Models\Programa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class PerfilController extends Controller
{
    /**
     * HU-013: muestra los datos sociodemográficos actuales del estudiante
     * autenticado (género, edad, programa, semestre), separados de sus
     * registros históricos.
     */
    public function edit(): View
    {
        return view('estudiante.perfil', [
            'perfil' => Auth::user()->perfil,
            'programas' => Programa::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validado = $request->validate([
            'genero' => ['required', 'in:masculino,femenino,otro,prefiero_no_decir'],
            'edad' => ['required', 'integer', 'min:15', 'max:99'],
            'programa_id' => ['required', 'exists:programas,id'],
            'semestre' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $request->user()->perfil->update($validado);

        return Redirect::route('perfil.edit')->with('status', 'perfil-actualizado');
    }
}
