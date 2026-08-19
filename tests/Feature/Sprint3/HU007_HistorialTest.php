<?php

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use Carbon\Carbon;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function hu007VersionMinima(): VersionModelo
{
    return VersionModelo::create([
        'nombre' => 'Prueba', 'version' => 'test-'.uniqid(), 'activo' => false,
        'coeficientes' => [], 'metricas' => [], 'mapa_variables' => [],
    ]);
}

function hu007DiligenciamientoCompletado(User $user, Instrumento $instrumento, Carbon $fecha, int $categoria = 0): Diligenciamiento
{
    $d = Diligenciamiento::create([
        'user_id' => $user->id, 'instrumento_id' => $instrumento->id,
        'estado' => 'completado', 'completado_at' => $fecha,
    ]);

    EvaluacionRiesgo::create([
        'diligenciamiento_id' => $d->id, 'version_modelo_id' => hu007VersionMinima()->id,
        'categoria' => $categoria, 'prob_0' => 0.5, 'prob_1' => 0.3, 'prob_2' => 0.2,
        'contribuciones' => [], 'evaluado_at' => $fecha,
    ]);

    return $d;
}

describe('HU-007 — Historial de diligenciamientos', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);

        $this->estudiante = User::factory()->create();
        $this->estudiante->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $this->estudiante->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->instrumento = Instrumento::where('activo', true)->firstOrFail();
    });

    it('muestra el estado vacío cuando no hay diligenciamientos completados', function () {
        $this->actingAs($this->estudiante)
            ->get(route('historial.show'))
            ->assertOk()
            ->assertSee('Aún no tienes encuestas completadas');
    });

    it('lista los diligenciamientos completados de más reciente a más antiguo', function () {
        $d1 = hu007DiligenciamientoCompletado($this->estudiante, $this->instrumento, now()->subDays(20), categoria: 0);
        $d2 = hu007DiligenciamientoCompletado($this->estudiante, $this->instrumento, now()->subDays(5), categoria: 2);

        $respuesta = $this->actingAs($this->estudiante)->get(route('historial.show'));

        $respuesta->assertOk();
        $posicionReciente = strpos($respuesta->getContent(), $d2->completado_at->format('d/m/Y'));
        $posicionAntigua = strpos($respuesta->getContent(), $d1->completado_at->format('d/m/Y'));

        expect($posicionReciente)->toBeLessThan($posicionAntigua)
            ->and($respuesta->getContent())->toContain('Riesgo alto')
            ->and($respuesta->getContent())->toContain('Riesgo bajo');
    });

    it('no incluye diligenciamientos pendientes o en progreso', function () {
        Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'en_progreso',
        ]);

        $this->actingAs($this->estudiante)
            ->get(route('historial.show'))
            ->assertOk()
            ->assertSee('Aún no tienes encuestas completadas');
    });

    it('un estudiante nunca ve diligenciamientos de otro en su historial', function () {
        $otro = User::factory()->create();
        $otro->assignRole('estudiante');
        hu007DiligenciamientoCompletado($otro, $this->instrumento, now());

        $respuesta = $this->actingAs($this->estudiante)->get(route('historial.show'));

        $respuesta->assertOk()->assertSee('Aún no tienes encuestas completadas');
    });

    it('cada fila enlaza al resultado correspondiente', function () {
        $d = hu007DiligenciamientoCompletado($this->estudiante, $this->instrumento, now());

        $this->actingAs($this->estudiante)
            ->get(route('historial.show'))
            ->assertOk()
            ->assertSee(route('resultado.show', $d), false);
    });

});
