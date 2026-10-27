<?php

namespace App\Modules\Encuestas\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de recordatorio de la encuesta por jornadas. Solo avisa que la
 * siguiente parte está disponible: no incluye respuestas, síntomas ni nivel
 * de riesgo (Ley 1581 de 2012 — el correo puede leerse fuera del aplicativo).
 */
class RecordatorioJornada extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $jornada,
        public readonly int $total,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("GutGuardián: el día {$this->jornada} de tu encuesta está disponible")
            ->greeting('Hola, '.strtok(trim($notifiable->name), ' '))
            ->line("Ya puedes responder el día {$this->jornada} de {$this->total} de tu encuesta de hábitos y bienestar digestivo. Te tomará pocos minutos.")
            ->line('Tus respuestas anteriores están guardadas y la encuesta no vence: puedes continuar cuando quieras.')
            ->action('Continuar la encuesta', route('encuesta.iniciar'))
            ->line('Si prefieres no recibir estos correos, puedes desactivarlos en tu perfil: '.route('perfil.edit'))
            ->salutation('Equipo GutGuardián — Universidad Mariana');
    }
}
