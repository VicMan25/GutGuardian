<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Respuesta;
use App\Modules\Reportes\Services\SeguimientoService;
use Carbon\Carbon;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function dolorDiligenciamiento(User $user, Instrumento $instrumento, Carbon $fecha, ?int $dolor): Diligenciamiento
{
    $d = Diligenciamiento::create([
        'user_id' => $user->id, 'instrumento_id' => $instrumento->id,
        'estado' => 'completado', 'completado_at' => $fecha,
    ]);

    if ($dolor !== null) {
        $p13 = Pregunta::where('codigo', 'P13')->first();
        Respuesta::create(['diligenciamiento_id' => $d->id, 'pregunta_id' => $p13->id, 'valor_numerico' => $dolor]);
    }

    return $d;
}

describe('Evolución del dolor abdominal (funcionalidad complementaria, sin HU numerada en el documento fuente)', function () {

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

    it('SeguimientoService::evolucionDolor produce los valores de P13 en orden cronológico', function () {
        $d1 = dolorDiligenciamiento($this->estudiante, $this->instrumento, now()->subDays(10), dolor: 2);
        $d2 = dolorDiligenciamiento($this->estudiante, $this->instrumento, now()->subDays(1), dolor: 5);

        $servicio = new SeguimientoService;
        $diligenciamientos = $servicio->diligenciamientosCompletados($this->estudiante);
        $respuestas = $servicio->respuestasClinicas($diligenciamientos);
        $resultado = $servicio->evolucionDolor($diligenciamientos, $respuestas);

        expect($resultado['etiquetas'])->toBe([
            $d1->completado_at->format('d/m/Y'),
            $d2->completado_at->format('d/m/Y'),
        ])
            ->and($resultado['valores'])->toBe([2, 5]);
    });

    it('omite diligenciamientos sin respuesta de P13 en vez de fallar', function () {
        dolorDiligenciamiento($this->estudiante, $this->instrumento, now()->subDays(10), dolor: 3);
        dolorDiligenciamiento($this->estudiante, $this->instrumento, now()->subDays(1), dolor: null);

        $servicio = new SeguimientoService;
        $diligenciamientos = $servicio->diligenciamientosCompletados($this->estudiante);
        $respuestas = $servicio->respuestasClinicas($diligenciamientos);
        $resultado = $servicio->evolucionDolor($diligenciamientos, $respuestas);

        expect($resultado['valores'])->toBe([3]);
    });

    it('la gráfica de dolor aparece en la vista de seguimiento', function () {
        dolorDiligenciamiento($this->estudiante, $this->instrumento, now()->subDays(10), dolor: 2);
        dolorDiligenciamiento($this->estudiante, $this->instrumento, now()->subDays(1), dolor: 4);

        $this->actingAs($this->estudiante)
            ->get(route('seguimiento.show'))
            ->assertOk()
            ->assertSee('Evolución del dolor abdominal');
    });

});
