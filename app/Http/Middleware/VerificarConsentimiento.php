<?php

namespace App\Http\Middleware;

use App\Modules\Auth\Models\Consentimiento;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarConsentimiento
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! Consentimiento::where('user_id', $user->id)->exists()) {
            return redirect()->route('consentimiento.show');
        }

        return $next($request);
    }
}
