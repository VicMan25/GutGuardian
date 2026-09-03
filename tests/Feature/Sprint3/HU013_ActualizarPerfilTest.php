<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Respuesta;
use App\Modules\Usuarios\Models\Perfil;
use App\Modules\Usuarios\Models\Programa;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\ProgramasSeeder;
use Database\Seeders\RolesPermisosSeeder;

describe('HU-013 — Actualización del perfil del estudiante', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, ProgramasSeeder::class, InstrumentoSeeder::class]);

        $this->estudiante = User::factory()->create();
        $this->estudiante->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $this->estudiante->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->programaInicial = Programa::where('nombre', 'Enfermería')->firstOrFail();
        $this->programaNuevo = Programa::where('nombre', 'Nutrición y Dietética')->firstOrFail();

        $this->perfil = Perfil::create([
            'user_id' => $this->estudiante->id,
            'genero' => 'femenino',
            'edad' => 21,
            'programa_id' => $this->programaInicial->id,
            'semestre' => 3,
        ]);
    });

    it('muestra los datos actualmente registrados', function () {
        $this->actingAs($this->estudiante)
            ->get(route('perfil.edit'))
            ->assertOk()
            ->assertSee('21')
            ->assertSee($this->programaInicial->nombre);
    });

    it('actualiza y persiste los datos con información válida', function () {
        $this->actingAs($this->estudiante)
            ->put(route('perfil.update'), [
                'genero' => 'otro',
                'edad' => 22,
                'programa_id' => $this->programaNuevo->id,
                'semestre' => 4,
            ])
            ->assertRedirect(route('perfil.edit'));

        $this->perfil->refresh();

        expect($this->perfil->genero)->toBe('otro')
            ->and($this->perfil->edad)->toBe(22)
            ->and($this->perfil->programa_id)->toBe($this->programaNuevo->id)
            ->and($this->perfil->semestre)->toBe(4);
    });

    it('rechaza una edad fuera del rango permitido', function () {
        $this->actingAs($this->estudiante)
            ->put(route('perfil.update'), [
                'genero' => 'otro', 'edad' => 10,
                'programa_id' => $this->programaInicial->id, 'semestre' => 3,
            ])
            ->assertSessionHasErrors('edad');

        expect($this->perfil->fresh()->edad)->toBe(21);
    });

    it('rechaza el envío si faltan campos obligatorios', function () {
        $this->actingAs($this->estudiante)
            ->put(route('perfil.update'), ['genero' => 'otro'])
            ->assertSessionHasErrors(['edad', 'programa_id', 'semestre']);
    });

    it('actualizar el perfil no modifica las respuestas ya guardadas de encuestas anteriores', function () {
        $instrumento = Instrumento::where('activo', true)->firstOrFail();
        $d = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $instrumento->id,
            'estado' => 'completado', 'completado_at' => now(),
        ]);
        $pregunta = Pregunta::where('codigo', 'P13')->firstOrFail();
        $respuesta = Respuesta::create([
            'diligenciamiento_id' => $d->id, 'pregunta_id' => $pregunta->id, 'valor_numerico' => 3,
        ]);

        $this->actingAs($this->estudiante)->put(route('perfil.update'), [
            'genero' => 'otro', 'edad' => 30,
            'programa_id' => $this->programaNuevo->id, 'semestre' => 8,
        ]);

        expect($respuesta->fresh()->valor_numerico)->toBe(3);
    });

});
