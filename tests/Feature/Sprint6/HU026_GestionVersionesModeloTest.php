<?php

use App\Models\User;
use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Analitica\Services\ImportadorModeloService;
use App\Modules\Analitica\Services\PredictorService;
use App\Modules\Auth\Models\Consentimiento;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Database\Seeders\VersionModeloSeeder;
use Illuminate\Http\UploadedFile;
use Spatie\Activitylog\Models\Activity;

function hu026Usuario(string $rol): User
{
    $u = User::factory()->create();
    $u->assignRole($rol);
    Consentimiento::create([
        'user_id' => $u->id, 'version_politica' => '1.0',
        'aceptado_at' => now(), 'ip' => '127.0.0.1',
    ]);

    return $u;
}

/** JSON real exportado por ml/, con la versión cambiada para poder registrarlo de nuevo. */
function hu026Artefacto(string $version = '2.0'): array
{
    $datos = json_decode(file_get_contents(storage_path('app/models/modelo_v1.json')), true);
    $datos['version'] = $version;

    return $datos;
}

function hu026Archivo(array $datos): UploadedFile
{
    return UploadedFile::fake()->createWithContent('modelo.json', json_encode($datos));
}

describe('HU-026 — Gestión de versiones del modelo predictivo', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class, VersionModeloSeeder::class]);
        $this->admin = hu026Usuario('admin');
    });

    it('lista las versiones registradas con su estado y métricas', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.modelos.index'))
            ->assertOk()
            ->assertSee('v1.0')
            ->assertSee('Activa');
    });

    it('registra una versión nueva sin activarla ni tocar la vigente', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.modelos.store'), ['archivo' => hu026Archivo(hu026Artefacto('2.0'))])
            ->assertRedirect();

        $nueva = VersionModelo::where('version', '2.0')->firstOrFail();

        expect($nueva->activo)->toBeFalse()
            ->and(VersionModelo::where('version', '1.0')->value('activo'))->toBeTrue()
            ->and($nueva->mapa_variables['orden_variables'])->toHaveCount(22);
    });

    it('activar una versión desactiva la anterior: solo una activa a la vez', function () {
        $this->actingAs($this->admin)->post(route('admin.modelos.store'), ['archivo' => hu026Archivo(hu026Artefacto('2.0'))]);
        $nueva = VersionModelo::where('version', '2.0')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.modelos.activar', $nueva))
            ->assertRedirect(route('admin.modelos.show', $nueva));

        expect(VersionModelo::where('activo', true)->pluck('version')->all())->toBe(['2.0']);
    });

    it('PredictorService usa la versión recién activada sin redesplegar', function () {
        $this->actingAs($this->admin)->post(route('admin.modelos.store'), [
            'archivo' => hu026Archivo(hu026Artefacto('2.0')), 'activar' => '1',
        ]);

        expect(app(PredictorService::class)->versionActiva()->version)->toBe('2.0');
    });

    it('rechaza un archivo al que le faltan claves', function () {
        $datos = hu026Artefacto('2.0');
        unset($datos['coeficientes']);

        $this->actingAs($this->admin)
            ->post(route('admin.modelos.store'), ['archivo' => hu026Archivo($datos)])
            ->assertSessionHasErrors('archivo');

        expect(VersionModelo::where('version', '2.0')->exists())->toBeFalse();
    });

    it('rechaza un modelo que exige una variable que la aplicación no sabe calcular', function () {
        $datos = hu026Artefacto('2.0');
        $datos['orden_variables'][] = 'variable_inventada';
        foreach ($datos['coeficientes'] as $k => $coef) {
            $datos['coeficientes'][$k]['beta']['variable_inventada'] = 0.5;
        }

        $this->actingAs($this->admin)
            ->post(route('admin.modelos.store'), ['archivo' => hu026Archivo($datos)])
            ->assertSessionHasErrors('archivo');
    });

    it('rechaza coeficientes incompletos para una categoría', function () {
        $datos = hu026Artefacto('2.0');
        unset($datos['coeficientes']['2']['beta']['p07_frituras']);

        $this->actingAs($this->admin)
            ->post(route('admin.modelos.store'), ['archivo' => hu026Archivo($datos)])
            ->assertSessionHasErrors('archivo');
    });

    it('rechaza una versión duplicada', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.modelos.store'), ['archivo' => hu026Archivo(hu026Artefacto('1.0'))])
            ->assertSessionHasErrors('archivo');
    });

    it('no activa una versión almacenada con metadatos corruptos y conserva la vigente', function () {
        $corrupta = VersionModelo::create([
            'nombre' => 'Corrupta', 'version' => '9.9', 'activo' => false,
            'coeficientes' => [], 'metricas' => [], 'mapa_variables' => [],
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.modelos.activar', $corrupta))
            ->assertSessionHasErrors('version');

        expect($corrupta->fresh()->activo)->toBeFalse()
            ->and(VersionModelo::where('activo', true)->value('version'))->toBe('1.0');
    });

    it('muestra el detalle con coeficientes como razón de probabilidades', function () {
        $version = VersionModelo::where('version', '1.0')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.modelos.show', $version))
            ->assertOk()
            ->assertSee('Matriz de confusión')
            ->assertSee('Consumo de frituras');
    });

    it('registra en auditoría el registro y la activación de versiones', function () {
        $this->actingAs($this->admin)->post(route('admin.modelos.store'), [
            'archivo' => hu026Archivo(hu026Artefacto('2.0')), 'activar' => '1',
        ]);

        $eventos = Activity::inLog(ImportadorModeloService::LOG)->where('causer_id', $this->admin->id)->pluck('event')->all();

        expect($eventos)->toContain('registro_version')->toContain('activacion_version');
    });

    it('un profesional de salud no puede gestionar versiones', function () {
        $profesional = hu026Usuario('profesional_salud');
        $version = VersionModelo::where('version', '1.0')->firstOrFail();

        $this->actingAs($profesional)->get(route('admin.modelos.index'))->assertForbidden();
        $this->actingAs($profesional)->post(route('admin.modelos.activar', $version))->assertForbidden();
    });

    it('un estudiante no puede gestionar versiones', function () {
        $this->actingAs(hu026Usuario('estudiante'))
            ->get(route('admin.modelos.index'))
            ->assertForbidden();
    });

});
