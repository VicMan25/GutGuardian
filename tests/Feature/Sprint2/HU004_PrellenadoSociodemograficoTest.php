<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\Opcion;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Seccion;
use App\Modules\Usuarios\Models\Perfil;
use App\Modules\Usuarios\Models\Programa;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\ProgramasSeeder;
use Database\Seeders\RolesPermisosSeeder;

/**
 * SD1/SD2/SD3/SD4 (Datos Sociodemográficos) ya se preguntan al registrarse
 * (tabla perfiles): no deben repetirse en cada encuesta. Ver
 * EncuestaController::opcionSociodemograficaDesdePerfil /
 * sincronizarPerfilDesdeSociodemografico.
 */
function hu004IdOpcion(string $codigo, string $etiqueta): int
{
    return Opcion::whereHas('pregunta', fn ($q) => $q->where('codigo', $codigo))
        ->where('etiqueta', $etiqueta)
        ->firstOrFail()
        ->id;
}

describe('HU-004 — Prellenado sociodemográfico desde el perfil de registro', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, ProgramasSeeder::class, InstrumentoSeeder::class]);

        $this->estudiante = User::factory()->create();
        $this->estudiante->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $this->estudiante->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->programaEnfermeria = Programa::where('nombre', 'Enfermería')->firstOrFail();

        $this->perfil = Perfil::create([
            'user_id' => $this->estudiante->id,
            'genero' => 'femenino',
            'edad' => 21,
            'programa_id' => $this->programaEnfermeria->id,
            'semestre' => 3,
        ]);

        $this->instrumento = Instrumento::where('activo', true)->firstOrFail();
        $this->diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'pendiente',
        ]);
        $this->seccion1 = Seccion::where('instrumento_id', $this->instrumento->id)->where('orden', 1)->first();
    });

    it('preselecciona SD1/SD2/SD3/SD4 con los datos ya declarados en el perfil', function () {
        $respuesta = $this->actingAs($this->estudiante)
            ->get(route('encuesta.seccion', [$this->diligenciamiento, 1]));

        $respuesta->assertOk()
            ->assertSee('selectorUnico('.hu004IdOpcion('SD1', 'Femenino').')', false)
            ->assertSee('selectorUnico('.hu004IdOpcion('SD2', '21 a 22 años').')', false)
            ->assertSee('selectorUnico('.hu004IdOpcion('SD3', 'Enfermería').')', false)
            ->assertSee('selectorUnico('.hu004IdOpcion('SD4', 'Semestre 3').')', false);
    });

    it('no preselecciona nada cuando el estudiante no tiene perfil', function () {
        $this->perfil->delete();

        $respuesta = $this->actingAs($this->estudiante)
            ->get(route('encuesta.seccion', [$this->diligenciamiento, 1]));

        $respuesta->assertOk()->assertSee('selectorUnico(null)', false);
    });

    it('si el estudiante corrige género/programa/semestre en la encuesta, el perfil se actualiza', function () {
        $payload = ['respuestas' => [
            Pregunta::where('codigo', 'SD1')->first()->id => hu004IdOpcion('SD1', 'Masculino'),
            Pregunta::where('codigo', 'SD2')->first()->id => hu004IdOpcion('SD2', '26 años o más'),
            Pregunta::where('codigo', 'SD3')->first()->id => hu004IdOpcion('SD3', 'Medicina'),
            Pregunta::where('codigo', 'SD4')->first()->id => hu004IdOpcion('SD4', 'Semestre 7'),
        ]];

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), $payload)
            ->assertRedirect();

        $programaMedicina = Programa::where('nombre', 'Medicina')->firstOrFail();

        expect($this->perfil->fresh())
            ->genero->toBe('masculino')
            ->programa_id->toBe($programaMedicina->id)
            ->semestre->toBe(7)
            // SD2 solo captura un rango de edad: no debe pisar la edad exacta del perfil.
            ->edad->toBe(21);
    });

    it('elegir "Otro" en SD3 no borra el programa real ya guardado en el perfil', function () {
        $payload = ['respuestas' => [
            Pregunta::where('codigo', 'SD1')->first()->id => hu004IdOpcion('SD1', 'Femenino'),
            Pregunta::where('codigo', 'SD2')->first()->id => hu004IdOpcion('SD2', '21 a 22 años'),
            Pregunta::where('codigo', 'SD3')->first()->id => hu004IdOpcion('SD3', 'Otro'),
            Pregunta::where('codigo', 'SD4')->first()->id => hu004IdOpcion('SD4', 'Semestre 3'),
        ]];

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), $payload)
            ->assertRedirect();

        expect($this->perfil->fresh()->programa_id)->toBe($this->programaEnfermeria->id);
    });

});
