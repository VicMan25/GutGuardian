<?php

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Reportes\Services\SeguimientoService;
use Carbon\Carbon;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function hu008Version(): VersionModelo
{
    return VersionModelo::create([
        'nombre' => 'Prueba', 'version' => 'test-'.uniqid(), 'activo' => false,
        'coeficientes' => [], 'metricas' => [], 'mapa_variables' => [],
    ]);
}

function hu008Diligenciamiento(User $user, Instrumento $instrumento, Carbon $fecha, array $probs, int $categoria): Diligenciamiento
{
    $d = Diligenciamiento::create([
        'user_id' => $user->id, 'instrumento_id' => $instrumento->id,
        'estado' => 'completado', 'completado_at' => $fecha,
    ]);

    EvaluacionRiesgo::create([
        'diligenciamiento_id' => $d->id, 'version_modelo_id' => hu008Version()->id,
        'categoria' => $categoria, 'prob_0' => $probs[0], 'prob_1' => $probs[1], 'prob_2' => $probs[2],
        'contribuciones' => [], 'evaluado_at' => $fecha,
    ]);

    return $d;
}

describe('HU-008 — Evolución del nivel de riesgo', function () {

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

    it('muestra un estado vacío con menos de 2 diligenciamientos completados', function () {
        hu008Diligenciamiento($this->estudiante, $this->instrumento, now(), [0.7, 0.2, 0.1], 0);

        $this->actingAs($this->estudiante)
            ->get(route('seguimiento.show'))
            ->assertOk()
            ->assertSee('Aún no hay suficientes datos');
    });

    it('SeguimientoService::evolucionRiesgo produce las probabilidades en porcentaje y en orden cronológico', function () {
        $d1 = hu008Diligenciamiento($this->estudiante, $this->instrumento, now()->subDays(10), [0.7, 0.2, 0.1], 0);
        $d2 = hu008Diligenciamiento($this->estudiante, $this->instrumento, now()->subDays(1), [0.1, 0.2, 0.7], 2);

        $servicio = new SeguimientoService;
        $diligenciamientos = $servicio->diligenciamientosCompletados($this->estudiante);
        $resultado = $servicio->evolucionRiesgo($diligenciamientos);

        expect($resultado['etiquetas'])->toBe([
            $d1->completado_at->format('d/m/Y'),
            $d2->completado_at->format('d/m/Y'),
        ])
            ->and($resultado['bajo'])->toBe([70.0, 10.0])
            ->and($resultado['medio'])->toBe([20.0, 20.0])
            ->and($resultado['alto'])->toBe([10.0, 70.0]);
    });

    it('la vista de seguimiento muestra las gráficas cuando hay al menos 2 diligenciamientos', function () {
        hu008Diligenciamiento($this->estudiante, $this->instrumento, now()->subDays(10), [0.7, 0.2, 0.1], 0);
        hu008Diligenciamiento($this->estudiante, $this->instrumento, now()->subDays(1), [0.1, 0.2, 0.7], 2);

        $this->actingAs($this->estudiante)
            ->get(route('seguimiento.show'))
            ->assertOk()
            ->assertDontSee('Aún no hay suficientes datos')
            ->assertSee('Evolución del nivel de riesgo');
    });

    it('un estudiante nunca ve la evolución de riesgo de otro estudiante', function () {
        $otro = User::factory()->create();
        $otro->assignRole('estudiante');
        hu008Diligenciamiento($otro, $this->instrumento, now()->subDays(10), [0.7, 0.2, 0.1], 0);
        hu008Diligenciamiento($otro, $this->instrumento, now()->subDays(1), [0.1, 0.2, 0.7], 2);

        $servicio = new SeguimientoService;
        $diligenciamientos = $servicio->diligenciamientosCompletados($this->estudiante);

        expect($diligenciamientos)->toHaveCount(0);
    });

});
