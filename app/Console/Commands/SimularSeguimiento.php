<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Analitica\Services\EvaluacionService;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\Opcion;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Models\Respuesta;
use App\Modules\Usuarios\Models\Perfil;
use App\Modules\Usuarios\Models\Programa;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SOLO DESARROLLO. Simula encuestas completadas para un estudiante, con
 * fechas escalonadas hacia atrás, para ver funcionando el seguimiento
 * (HU-008/HU-010), el historial (HU-011) y las alertas (HU-012) sin tener
 * que diligenciar el instrumento varias veces a mano.
 *
 * Las respuestas siguen una trayectoria: el perfil de riesgo empieza en
 * --desde y se mueve hacia --hasta (0 = hábitos protectores y sin síntomas,
 * 1 = hábitos de riesgo y síntomas recientes), con algo de ruido. Cada
 * encuesta se evalúa con EvaluacionService, igual que en el flujo real.
 *
 * Son datos sintéticos: el comando se niega a correr en producción.
 */
class SimularSeguimiento extends Command
{
    protected $signature = 'gutguardian:simular-seguimiento
        {email : Correo de una cuenta con rol estudiante}
        {--encuestas=5 : Número de encuestas a simular (2 a 12)}
        {--dias=14 : Días entre una encuesta y la siguiente}
        {--desde=0.8 : Nivel de riesgo inicial (0 a 1)}
        {--hasta=0.2 : Nivel de riesgo final (0 a 1)}';

    protected $description = 'SOLO DESARROLLO: simula encuestas completadas de un estudiante para ver el seguimiento';

    private const HABITOS_PROTECTORES = ['P01', 'P02', 'P03', 'P10', 'P19'];

    private const HABITOS_DE_RIESGO = ['P04', 'P05', 'P06', 'P07'];

    public function handle(EvaluacionService $evaluaciones): int
    {
        if (app()->environment('production')) {
            $this->error('Este comando genera datos sintéticos y no puede ejecutarse en producción.');

            return self::FAILURE;
        }

        $estudiante = User::where('email', $this->argument('email'))->first();
        if (! $estudiante || ! $estudiante->hasRole('estudiante')) {
            $this->error('No existe una cuenta con rol estudiante con ese correo.');

            return self::FAILURE;
        }

        $n = max(2, min(12, (int) $this->option('encuestas')));
        $dias = max(1, (int) $this->option('dias'));
        $desde = $this->acotar((float) $this->option('desde'));
        $hasta = $this->acotar((float) $this->option('hasta'));

        $instrumento = Instrumento::where('activo', true)->firstOrFail();
        $preguntas = Pregunta::whereHas('seccion', fn ($q) => $q->where('instrumento_id', $instrumento->id))
            ->with(['opciones', 'items'])->get()->keyBy('codigo');
        $perfil = $estudiante->perfil ?? $this->crearPerfil($estudiante);

        $filas = [];
        for ($k = 0; $k < $n; $k++) {
            $nivel = $this->acotar($desde + ($hasta - $desde) * $k / ($n - 1) + mt_rand(-10, 10) / 100);
            $momento = now()->subDays($dias * ($n - 1 - $k))->setTime(mt_rand(8, 20), mt_rand(0, 59));

            $evaluacion = DB::transaction(function () use ($estudiante, $instrumento, $preguntas, $perfil, $nivel, $momento, $evaluaciones) {
                $d = Diligenciamiento::create([
                    'user_id' => $estudiante->id,
                    'instrumento_id' => $instrumento->id,
                    'estado' => 'completado',
                    'completado_at' => $momento,
                ]);
                $d->forceFill(['created_at' => $momento->copy()->subMinutes(mt_rand(6, 16)), 'updated_at' => $momento])->saveQuietly();

                $this->responder($d, $preguntas, $perfil, $nivel);

                return $evaluaciones->evaluar($d, $momento);
            });

            $filas[] = [$momento->format('d/m/Y'), number_format($nivel, 2), ['bajo', 'medio', 'alto'][$evaluacion->categoria],
                number_format($evaluacion->prob_0 * 100, 1).' / '.number_format($evaluacion->prob_1 * 100, 1).' / '.number_format($evaluacion->prob_2 * 100, 1)];
        }

        $this->table(['Fecha', 'Nivel simulado', 'Riesgo', 'Prob. bajo / medio / alto (%)'], $filas);
        $this->info("Se simularon {$n} encuestas para {$estudiante->email}. Datos sintéticos, solo para desarrollo.");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<string, Pregunta>  $preguntas
     */
    private function responder(Diligenciamiento $d, Collection $preguntas, Perfil $perfil, float $nivel): void
    {
        foreach ($preguntas as $codigo => $p) {
            match (true) {
                $codigo === 'SD1' => $this->porValor($d, $p, ['masculino' => 1, 'femenino' => 2][$perfil->genero] ?? 3),
                $codigo === 'SD2' => $this->porValor($d, $p, $this->rangoEdad($perfil->edad)),
                $codigo === 'SD3' => $this->porValor($d, $p, $this->opcionPrograma($p, $perfil)),
                $codigo === 'SD4' => $this->porValor($d, $p, min(10, max(1, (int) $perfil->semestre))),
                in_array($codigo, self::HABITOS_PROTECTORES, true) => $this->porValor($d, $p, $this->escala(1 - $nivel, 3)),
                in_array($codigo, self::HABITOS_DE_RIESGO, true) => $this->porValor($d, $p, $this->escala($nivel, 3)),
                $codigo === 'P08' => $this->porValor($d, $p, $nivel > 0.6 ? mt_rand(1, 2) : mt_rand(3, 4)),
                $codigo === 'P09' => $p->items->each(fn ($i) => $this->porValor($d, $p, $this->escala($nivel * 0.8, 3), $i->id)),
                // Síntomas: temporalidad 0–5 (5 = última semana), frecuencia 0–3.
                $codigo === 'P11' => $p->items->each(fn ($i) => $this->porValor($d, $p, $this->escala($nivel, 5), $i->id)),
                $codigo === 'P12' => $p->items->each(fn ($i) => $this->porValor($d, $p, $this->escala($nivel, 3), $i->id)),
                $codigo === 'P13' => $this->porValor($d, $p, 1 + $this->escala($nivel, 4)),
                $codigo === 'P16' => $p->items->each(fn ($i) => $this->porValor($d, $p, $this->escala($nivel * 0.6, 5), $i->id)),
                $codigo === 'P17' => $p->items->each(fn ($i) => $this->porValor($d, $p, $this->escala($nivel * 0.6, 3), $i->id)),
                in_array($codigo, ['P14', 'P15', 'P18', 'P20'], true) => $this->multiple($d, $p, $nivel),
                default => null,
            };
        }
    }

    /** Valor entero en [0, $maximo] alrededor de $nivel·$maximo, con ruido. */
    private function escala(float $nivel, int $maximo): int
    {
        return (int) max(0, min($maximo, round($nivel * $maximo + mt_rand(-7, 7) / 10)));
    }

    private function porValor(Diligenciamiento $d, Pregunta $p, int $valor, ?int $itemId = null): void
    {
        $opcion = $p->opciones->firstWhere('valor_numerico', $valor) ?? $p->opciones->first();

        Respuesta::create([
            'diligenciamiento_id' => $d->id,
            'pregunta_id' => $p->id,
            'item_pregunta_id' => $itemId,
            'opcion_id' => $opcion->id,
            'valor_numerico' => $opcion->valor_numerico,
        ]);
    }

    /** Selección múltiple: más opciones marcadas cuanto mayor el nivel; si ninguna, «Ninguna». */
    private function multiple(Diligenciamiento $d, Pregunta $p, float $nivel): void
    {
        $ninguna = $p->opciones->firstWhere('valor_numerico', 0);
        $candidatas = $p->opciones->where('valor_numerico', '>', 0)->values();
        $probabilidad = $p->codigo === 'P20' ? $nivel * 0.25 : $nivel * 0.45;

        $elegidas = $candidatas->filter(fn () => mt_rand(0, 100) / 100 < $probabilidad);
        if ($p->codigo === 'P18' && $nivel < 0.5) {
            // Quien tiene hábitos protectores suele practicar deporte.
            $elegidas = $elegidas->push($candidatas->first(fn (Opcion $o) => $o->etiqueta === 'Practica deporte'))->filter()->unique('id');
        }

        foreach ($elegidas->isEmpty() ? collect([$ninguna]) : $elegidas as $opcion) {
            Respuesta::create([
                'diligenciamiento_id' => $d->id,
                'pregunta_id' => $p->id,
                'opcion_id' => $opcion->id,
                'valor_numerico' => $opcion->valor_numerico,
            ]);
        }
    }

    private function crearPerfil(User $estudiante): Perfil
    {
        return Perfil::create([
            'user_id' => $estudiante->id,
            'genero' => ['masculino', 'femenino'][mt_rand(0, 1)],
            'edad' => mt_rand(18, 24),
            'programa_id' => Programa::inRandomOrder()->value('id'),
            'semestre' => mt_rand(1, 10),
        ]);
    }

    private function rangoEdad(?int $edad): int
    {
        return match (true) {
            $edad === null, $edad <= 18 => 1,
            $edad <= 20 => 2,
            $edad <= 22 => 3,
            $edad <= 25 => 4,
            default => 5,
        };
    }

    private function opcionPrograma(Pregunta $p, Perfil $perfil): int
    {
        $nombre = $perfil->programa?->nombre;
        $opcion = $p->opciones->first(fn (Opcion $o) => $nombre && str_starts_with($o->etiqueta, $nombre));

        return $opcion?->valor_numerico ?? $p->opciones->max('valor_numerico');
    }

    private function acotar(float $v): float
    {
        return max(0.0, min(1.0, $v));
    }
}
