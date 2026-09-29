<?php

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function ley1581Usuario(string $rol): User
{
    $u = User::factory()->create();
    $u->assignRole($rol);
    Consentimiento::create([
        'user_id' => $u->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);

    return $u;
}

function ley1581EncuestaEvaluada(User $e): Diligenciamiento
{
    $d = Diligenciamiento::create([
        'user_id' => $e->id, 'instrumento_id' => Instrumento::where('activo', true)->value('id'),
        'estado' => 'completado', 'completado_at' => now(),
    ]);
    $v = VersionModelo::create([
        'nombre' => 'Prueba', 'version' => 'test-'.uniqid(), 'activo' => false,
        'coeficientes' => [], 'metricas' => [], 'mapa_variables' => [],
    ]);
    EvaluacionRiesgo::create([
        'diligenciamiento_id' => $d->id, 'version_modelo_id' => $v->id,
        'categoria' => 1, 'prob_0' => 0.2, 'prob_1' => 0.5, 'prob_2' => 0.3,
        'contribuciones' => [], 'evaluado_at' => now(),
    ]);

    return $d;
}

describe('Ley 1581 — Derechos del titular («Mis datos»)', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
    });

    it('el estudiante ve la información que el sistema guarda y su consentimiento', function () {
        $e = ley1581Usuario('estudiante');

        $this->actingAs($e)
            ->get(route('mis-datos.show'))
            ->assertOk()
            ->assertSee($e->email)
            ->assertSee('Versión 1.0')
            ->assertSee('Nadie más ha consultado tu información clínica.');
    });

    it('el estudiante ve quién consultó su ficha y sus resultados', function () {
        $e = ley1581Usuario('estudiante');
        $d = ley1581EncuestaEvaluada($e);
        $profesional = ley1581Usuario('profesional_salud');

        $this->actingAs($profesional)->get(route('panel.estudiantes.show', $e))->assertOk();
        $this->actingAs($profesional)->get(route('resultado.show', $d))->assertOk();

        $this->actingAs($e)
            ->get(route('mis-datos.show'))
            ->assertSee($profesional->name.' — Consultó tu ficha')
            ->assertSee($profesional->name.' — Consultó uno de tus resultados');
    });

    it('no muestra los accesos a la información de otro estudiante', function () {
        $e = ley1581Usuario('estudiante');
        $otro = ley1581Usuario('estudiante');
        $profesional = ley1581Usuario('profesional_salud');

        $this->actingAs($profesional)->get(route('panel.estudiantes.show', $otro))->assertOk();

        $this->actingAs($e)
            ->get(route('mis-datos.show'))
            ->assertSee('Nadie más ha consultado tu información clínica.');
    });

    it('el estudiante descarga una copia de sus datos en PDF', function () {
        $e = ley1581Usuario('estudiante');
        ley1581EncuestaEvaluada($e);

        $respuesta = $this->actingAs($e)->get(route('mis-datos.descargar'))->assertOk();

        expect($respuesta->headers->get('content-type'))->toContain('application/pdf');
    });

    it('un profesional no usa la sección «Mis datos» del estudiante', function () {
        $this->actingAs(ley1581Usuario('profesional_salud'))
            ->get(route('mis-datos.show'))
            ->assertForbidden();
    });

});

describe('Ley 1581 — Visor del registro de auditoría (admin)', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
    });

    it('el administrador ve quién consultó la ficha de un estudiante', function () {
        $e = ley1581Usuario('estudiante');
        $profesional = ley1581Usuario('profesional_salud');
        $this->actingAs($profesional)->get(route('panel.estudiantes.show', $e));

        $this->actingAs(ley1581Usuario('admin'))
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee($profesional->name)
            ->assertSee('Consulta de ficha')
            ->assertSee($e->codigo_participante);
    });

    it('filtra por rango de fechas', function () {
        $e = ley1581Usuario('estudiante');
        $profesional = ley1581Usuario('profesional_salud');
        $this->actingAs($profesional)->get(route('panel.estudiantes.show', $e));

        $this->actingAs(ley1581Usuario('admin'))
            ->get(route('admin.auditoria.index', ['desde' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertSee('No hay registros para los filtros seleccionados.');
    });

    it('rechaza un registro de auditoría desconocido', function () {
        $this->actingAs(ley1581Usuario('admin'))
            ->get(route('admin.auditoria.index', ['registro' => 'default']))
            ->assertSessionHasErrors('registro');
    });

    it('un profesional de salud no accede al registro de auditoría', function () {
        $this->actingAs(ley1581Usuario('profesional_salud'))
            ->get(route('admin.auditoria.index'))
            ->assertForbidden();
    });

});
