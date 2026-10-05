<?php

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Respuesta;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\ProgramasSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Database\Seeders\VersionModeloSeeder;

function graficasUsuario(string $rol): User
{
    $u = User::factory()->create();
    $u->assignRole($rol);
    Consentimiento::create([
        'user_id' => $u->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);

    return $u;
}

/**
 * Regresión: con @json las etiquetas de fecha salían como ["19\/08\/2026"],
 * con comillas dobles crudas dentro de x-init="…"; el navegador cortaba el
 * atributo y Alpine no podía dibujar ninguna gráfica.
 */
function graficasSinComillasCrudas(string $html): void
{
    preg_match_all('/x-init="([^"]*)"/', $html, $m);

    expect($m[1])->not->toBeEmpty();
    foreach ($m[1] as $expresion) {
        // Toda expresión de Chart.js debe cerrar su llamada dentro del atributo.
        expect(trim($expresion))->toStartWith('new Chart(')->toEndWith(')');
    }
}

describe('Sprint 6 — Gráficas de Chart.js dentro de atributos Alpine', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, ProgramasSeeder::class, InstrumentoSeeder::class, VersionModeloSeeder::class]);
    });

    it('el seguimiento del estudiante emite expresiones x-init completas', function () {
        $e = graficasUsuario('estudiante');
        $this->artisan('gutguardian:simular-seguimiento', ['email' => $e->email, '--encuestas' => 3])->assertSuccessful();

        $html = $this->actingAs($e)->get(route('seguimiento.show'))->assertOk()->getContent();

        graficasSinComillasCrudas($html);
    });

    it('la ficha del estudiante en el panel emite expresiones x-init completas', function () {
        $e = graficasUsuario('estudiante');
        $this->artisan('gutguardian:simular-seguimiento', ['email' => $e->email, '--encuestas' => 3])->assertSuccessful();

        $html = $this->actingAs(graficasUsuario('profesional_salud'))
            ->get(route('panel.estudiantes.show', $e))->assertOk()->getContent();

        graficasSinComillasCrudas($html);
    });

});

describe('Sprint 6 — Simulación de seguimiento (solo desarrollo)', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, ProgramasSeeder::class, InstrumentoSeeder::class, VersionModeloSeeder::class]);
    });

    it('crea encuestas completas, evaluadas y en fechas escalonadas', function () {
        $e = graficasUsuario('estudiante');

        $this->artisan('gutguardian:simular-seguimiento', ['email' => $e->email, '--encuestas' => 4, '--dias' => 7])
            ->assertSuccessful();

        $diligenciamientos = Diligenciamiento::where('user_id', $e->id)->orderBy('completado_at')->get();
        $fechas = $diligenciamientos->pluck('completado_at');

        expect($diligenciamientos)->toHaveCount(4)
            ->and($diligenciamientos->pluck('estado')->unique()->all())->toBe(['completado'])
            ->and((int) round($fechas->first()->diffInDays($fechas->last())))->toBe(21)
            ->and(EvaluacionRiesgo::whereIn('diligenciamiento_id', $diligenciamientos->pluck('id'))->count())->toBe(4);
    });

    it('responde las 24 preguntas del instrumento en cada encuesta', function () {
        $e = graficasUsuario('estudiante');
        $this->artisan('gutguardian:simular-seguimiento', ['email' => $e->email, '--encuestas' => 2])->assertSuccessful();

        $d = Diligenciamiento::where('user_id', $e->id)->firstOrFail();

        expect(Respuesta::where('diligenciamiento_id', $d->id)->distinct('pregunta_id')->count('pregunta_id'))->toBe(24);
    });

    it('rechaza cuentas que no son de estudiante', function () {
        $this->artisan('gutguardian:simular-seguimiento', ['email' => graficasUsuario('profesional_salud')->email])
            ->assertFailed();
    });

    it('no se ejecuta en producción', function () {
        $e = graficasUsuario('estudiante');
        app()['env'] = 'production';

        $this->artisan('gutguardian:simular-seguimiento', ['email' => $e->email])->assertFailed();

        expect(Diligenciamiento::where('user_id', $e->id)->count())->toBe(0);
    });

});
