<?php

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function hu024Consentimiento(User $user): void
{
    Consentimiento::create([
        'user_id' => $user->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);
}

function hu024Profesional(): User
{
    $p = User::factory()->create();
    $p->assignRole('profesional_salud');
    hu024Consentimiento($p);

    return $p;
}

function hu024Evaluacion(Instrumento $instrumento): void
{
    $e = User::factory()->create();
    $e->assignRole('estudiante');
    hu024Consentimiento($e);

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
        'categoria' => 1, 'prob_0' => 0.3, 'prob_1' => 0.4, 'prob_2' => 0.3,
        'contribuciones' => [], 'evaluado_at' => now(),
    ]);
}

describe('HU-024 — Exportación de reportes en PDF y Excel', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
        $instrumento = Instrumento::where('activo', true)->firstOrFail();
        hu024Evaluacion($instrumento);
    });

    it('descarga el reporte en PDF', function () {
        $profesional = hu024Profesional();

        $respuesta = $this->actingAs($profesional)->get(route('panel.reportes.exportarPdf'));

        $respuesta->assertOk();
        expect($respuesta->headers->get('Content-Type'))->toContain('application/pdf');
    });

    it('descarga el reporte en Excel', function () {
        $profesional = hu024Profesional();

        $respuesta = $this->actingAs($profesional)->get(route('panel.reportes.exportarExcel'));

        $respuesta->assertOk();
        expect($respuesta->headers->get('Content-Type'))
            ->toContain('spreadsheetml');
    });

    it('un estudiante sin el permiso exportar_reportes recibe 403', function () {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');
        hu024Consentimiento($estudiante);

        $this->actingAs($estudiante)
            ->get(route('panel.reportes.exportarPdf'))
            ->assertForbidden();
    });

});
