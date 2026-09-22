<?php

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Panel\Services\AuditoriaClinicaService;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Spatie\Activitylog\Models\Activity;

function audConsentimiento(User $user): void
{
    Consentimiento::create([
        'user_id' => $user->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);
}

function audProfesional(): User
{
    $p = User::factory()->create();
    $p->assignRole('profesional_salud');
    audConsentimiento($p);

    return $p;
}

function audEstudianteConResultado(Instrumento $instrumento): array
{
    $e = User::factory()->create();
    $e->assignRole('estudiante');
    audConsentimiento($e);

    $d = Diligenciamiento::create([
        'user_id' => $e->id, 'instrumento_id' => $instrumento->id,
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

    return [$e, $d];
}

/** Actividades registradas en el log de acceso clínico. */
function accesosClinicos()
{
    return Activity::inLog(AuditoriaClinicaService::LOG)->get();
}

describe('Auditoría de acceso a datos clínicos (Ley 1581 de 2012)', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
        $this->instrumento = Instrumento::where('activo', true)->firstOrFail();
    });

    it('registra quién consultó la ficha clínica de un estudiante', function () {
        $profesional = audProfesional();
        [$estudiante] = audEstudianteConResultado($this->instrumento);

        $this->actingAs($profesional)
            ->get(route('panel.estudiantes.show', $estudiante))
            ->assertOk();

        $registro = accesosClinicos()->firstWhere('event', 'consulta_ficha');

        expect($registro)->not->toBeNull()
            ->and($registro->causer_id)->toBe($profesional->id)
            ->and($registro->subject_id)->toBe($estudiante->id)
            ->and($registro->properties['codigo_participante'])->toBe($estudiante->codigo_participante);
    });

    it('registra la consulta de un resultado de riesgo por parte de un tercero', function () {
        $profesional = audProfesional();
        [, $diligenciamiento] = audEstudianteConResultado($this->instrumento);

        $this->actingAs($profesional)
            ->get(route('resultado.show', $diligenciamiento))
            ->assertOk();

        $registro = accesosClinicos()->firstWhere('event', 'consulta_resultado');

        expect($registro)->not->toBeNull()
            ->and($registro->causer_id)->toBe($profesional->id)
            ->and($registro->properties['diligenciamiento_id'])->toBe($diligenciamiento->id);
    });

    it('no audita el acceso del propio estudiante a su resultado', function () {
        [$estudiante, $diligenciamiento] = audEstudianteConResultado($this->instrumento);

        $this->actingAs($estudiante)
            ->get(route('resultado.show', $diligenciamiento))
            ->assertOk();

        expect(accesosClinicos())->toBeEmpty();
    });

    it('registra la consulta del reporte institucional', function () {
        $profesional = audProfesional();

        $this->actingAs($profesional)
            ->get(route('panel.reportes.index'))
            ->assertOk();

        expect(accesosClinicos()->firstWhere('event', 'consulta_reporte'))->not->toBeNull();
    });

    it('registra la exportación del reporte en PDF y en Excel', function () {
        $profesional = audProfesional();

        $this->actingAs($profesional)->get(route('panel.reportes.exportarPdf'))->assertOk();
        $this->actingAs($profesional)->get(route('panel.reportes.exportarExcel'))->assertOk();

        $exportaciones = accesosClinicos()->where('event', 'exportacion_reporte');

        expect($exportaciones)->toHaveCount(2)
            ->and($exportaciones->pluck('properties.accion')->sort()->values()->all())
            ->toBe(['exportacion_excel', 'exportacion_pdf']);
    });

    it('el log de acceso clínico guarda al profesional como causante y conserva la fecha', function () {
        $profesional = audProfesional();
        [$estudiante] = audEstudianteConResultado($this->instrumento);

        $this->actingAs($profesional)->get(route('panel.estudiantes.show', $estudiante))->assertOk();

        $registro = accesosClinicos()->first();

        expect($registro->causer)->not->toBeNull()
            ->and($registro->causer->is($profesional))->toBeTrue()
            ->and($registro->created_at)->not->toBeNull();
    });

});
