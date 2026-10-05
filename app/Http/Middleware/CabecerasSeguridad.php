<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras HTTP de seguridad para todas las respuestas web (Ley 1273 de
 * 2009 y Ley 1581 de 2012: medidas técnicas para proteger datos de salud).
 *
 * - X-Frame-Options / frame-ancestors: impide incrustar la app en otro sitio (clickjacking).
 * - X-Content-Type-Options: el navegador no reinterpreta el tipo de los archivos.
 * - Referrer-Policy: no filtra rutas internas (con ids de encuestas) a sitios externos.
 * - Permissions-Policy: la app no usa cámara, micrófono ni geolocalización.
 * - Strict-Transport-Security: solo sobre HTTPS, para que el navegador no vuelva a HTTP.
 *
 * No se fija una Content-Security-Policy completa: Alpine.js evalúa
 * expresiones en línea y exigiría 'unsafe-eval'; queda como mejora futura
 * (compilación CSP de Alpine).
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'");

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
