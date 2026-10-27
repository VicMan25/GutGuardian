<?php

namespace App\Console\Commands;

use App\Modules\Encuestas\Notifications\RecordatorioJornada;
use App\Modules\Encuestas\Services\JornadaService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Recordatorio por correo de la encuesta por jornadas. Lo ejecuta el
 * programador de tareas de Laravel una vez al día (routes/console.php), así
 * que en el servidor basta con la línea de cron de `schedule:run`.
 *
 * Envía como máximo un correo por jornada pendiente y nunca a estudiantes
 * inactivos o que desactivaron los recordatorios en /perfil. Con `--simular`
 * solo lista a quién le escribiría, sin enviar ni marcar nada.
 */
class EnviarRecordatorios extends Command
{
    protected $signature = 'gutguardian:enviar-recordatorios {--simular : Muestra a quién se enviaría, sin enviar}';

    protected $description = 'Envía el recordatorio por correo de la siguiente jornada de la encuesta';

    public function handle(JornadaService $jornadas): int
    {
        $pendientes = $jornadas->pendientesDeRecordatorio();

        if ($pendientes->isEmpty()) {
            $this->info('No hay recordatorios pendientes hoy.');

            return self::SUCCESS;
        }

        $enviados = 0;
        $fallidos = 0;

        foreach ($pendientes as ['diligenciamiento' => $diligenciamiento, 'jornada' => $jornada, 'total' => $total]) {
            $usuario = $diligenciamiento->usuario;

            if ($this->option('simular')) {
                $this->line("  {$usuario->codigo_participante} — día {$jornada} de {$total}");

                continue;
            }

            try {
                $usuario->notify(new RecordatorioJornada($jornada, $total));
                $jornadas->marcarRecordatorioEnviado($diligenciamiento, $jornada);
                $enviados++;
            } catch (Throwable $e) {
                // Sin marcar: se reintenta en la ejecución de mañana.
                $fallidos++;
                $this->error("  {$usuario->codigo_participante}: {$e->getMessage()}");
            }
        }

        if ($this->option('simular')) {
            $this->info("Simulación: {$pendientes->count()} recordatorio(s) por enviar.");

            return self::SUCCESS;
        }

        $this->info("Recordatorios enviados: {$enviados}. Fallidos: {$fallidos}.");

        return $fallidos === 0 ? self::SUCCESS : self::FAILURE;
    }
}
