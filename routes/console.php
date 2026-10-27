<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorio de la encuesta por jornadas: una vez al día, a media mañana
// en hora de Colombia. Requiere la línea de cron de `schedule:run` en el
// servidor (docs/DESPLIEGUE.md).
Schedule::command('gutguardian:enviar-recordatorios')
    ->dailyAt('09:00')
    ->timezone('America/Bogota')
    ->withoutOverlapping();
