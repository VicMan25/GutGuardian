<?php

namespace App\Providers;

use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Panel\Models\Alerta;
use App\Policies\AlertaPolicy;
use App\Policies\DiligenciamientoPolicy;
use App\Policies\EvaluacionRiesgoPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Diligenciamiento::class, DiligenciamientoPolicy::class);
        Gate::policy(EvaluacionRiesgo::class, EvaluacionRiesgoPolicy::class);
        Gate::policy(Alerta::class, AlertaPolicy::class);

        // Ley 1273 de 2009 / CLAUDE.md §9: HTTPS obligatorio fuera de desarrollo.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
