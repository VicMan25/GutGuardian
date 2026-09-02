<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\ItemPregunta;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Respuesta;
use App\Modules\Reportes\Services\SeguimientoService;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

function sintomasRespuestaMatriz(Diligenciamiento $d, string $codigo, string $etiquetaItem, int $valor): void
{
    $pregunta = Pregunta::where('codigo', $codigo)->first();
    $item = ItemPregunta::where('pregunta_id', $pregunta->id)->where('etiqueta', $etiquetaItem)->first();

    Respuesta::create([
        'diligenciamiento_id' => $d->id, 'pregunta_id' => $pregunta->id,
        'item_pregunta_id' => $item->id, 'valor_numerico' => $valor,
    ]);
}

describe('Seguimiento de síntomas (funcionalidad complementaria, sin HU numerada en el documento fuente) — temporalidad y frecuencia', function () {

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

    it('arma un panel por cada uno de los 6 síntomas con su frecuencia (P12) y temporalidad (P11)', function () {
        $d1 = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id,
            'estado' => 'completado', 'completado_at' => now()->subDays(10),
        ]);
        sintomasRespuestaMatriz($d1, 'P12', 'Diarrea', 1);
        sintomasRespuestaMatriz($d1, 'P11', 'Diarrea', 3);

        $d2 = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id,
            'estado' => 'completado', 'completado_at' => now()->subDays(1),
        ]);
        sintomasRespuestaMatriz($d2, 'P12', 'Diarrea', 3);
        sintomasRespuestaMatriz($d2, 'P11', 'Diarrea', 5);

        $servicio = new SeguimientoService;
        $diligenciamientos = $servicio->diligenciamientosCompletados($this->estudiante);
        $respuestas = $servicio->respuestasClinicas($diligenciamientos);
        $paneles = $servicio->seguimientoSintomas($diligenciamientos, $respuestas);

        expect($paneles)->toHaveCount(6);

        $panelDiarrea = collect($paneles)->firstWhere('sintoma', 'Diarrea');
        expect($panelDiarrea['etiquetas'])->toBe([
            $d1->completado_at->format('d/m/Y'),
            $d2->completado_at->format('d/m/Y'),
        ])
            ->and($panelDiarrea['frecuencia'])->toBe([1, 3])
            ->and($panelDiarrea['temporalidad'])->toBe([3, 5]);
    });

    it('las respuestas de un síntoma no contaminan el panel de otro síntoma', function () {
        $d = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id,
            'estado' => 'completado', 'completado_at' => now(),
        ]);
        sintomasRespuestaMatriz($d, 'P12', 'Diarrea', 3);
        sintomasRespuestaMatriz($d, 'P12', 'Fiebre', 0);

        $servicio = new SeguimientoService;
        $diligenciamientos = $servicio->diligenciamientosCompletados($this->estudiante);
        $respuestas = $servicio->respuestasClinicas($diligenciamientos);
        $paneles = $servicio->seguimientoSintomas($diligenciamientos, $respuestas);

        $panelFiebre = collect($paneles)->firstWhere('sintoma', 'Fiebre');
        expect($panelFiebre['frecuencia'])->toBe([0]);
    });

    it('el grid de síntomas aparece en la vista de seguimiento con al menos 2 diligenciamientos', function () {
        foreach ([now()->subDays(10), now()->subDays(1)] as $fecha) {
            $d = Diligenciamiento::create([
                'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id,
                'estado' => 'completado', 'completado_at' => $fecha,
            ]);
            sintomasRespuestaMatriz($d, 'P12', 'Diarrea', 1);
        }

        $this->actingAs($this->estudiante)
            ->get(route('seguimiento.show'))
            ->assertOk()
            ->assertSee('Seguimiento de síntomas')
            ->assertSee('Diarrea')
            ->assertSee('Fiebre');
    });

    it('un estudiante nunca ve el seguimiento de síntomas de otro estudiante', function () {
        $otro = User::factory()->create();
        $otro->assignRole('estudiante');
        $d = Diligenciamiento::create([
            'user_id' => $otro->id, 'instrumento_id' => $this->instrumento->id,
            'estado' => 'completado', 'completado_at' => now(),
        ]);
        sintomasRespuestaMatriz($d, 'P12', 'Diarrea', 3);

        $servicio = new SeguimientoService;
        $diligenciamientos = $servicio->diligenciamientosCompletados($this->estudiante);

        expect($diligenciamientos)->toHaveCount(0);
    });

});
