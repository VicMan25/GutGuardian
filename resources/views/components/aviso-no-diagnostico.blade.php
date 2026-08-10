{{--
    Aviso de no-diagnóstico — componente obligatorio junto a todo resultado de riesgo.
    Exigido por la Resolución 3100 de 2019 (Software como Dispositivo Médico, SaMD).

    Este componente NO tiene props ni puede ser modificado sin autorización del equipo
    de Enfermería. El texto fue validado por el comité de ética del trabajo de grado.

    NO mover a footer. Debe aparecer visible en capturas de pantalla del resultado.
--}}
<aside class="gg-aviso-nodiag p-4" aria-label="Aviso legal importante">
    <div class="flex gap-3">
        <svg class="w-4 h-4 shrink-0 mt-0.5 text-gg-primario" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-xs text-gg-tinta leading-relaxed">
            <p>
                <strong class="font-medium">Esta herramienta no realiza diagnóstico clínico</strong>
                ni reemplaza la consulta con un profesional de la salud.
                Identifica patrones de hábitos alimentarios y síntomas gastrointestinales
                con fines de autocuidado y tamizaje académico.
            </p>
            <p class="mt-1.5 text-gg-tinta-suave">
                Si presenta síntomas persistentes, consulte a un médico o profesional de salud.
                <span class="whitespace-nowrap">Resolución 3100 de 2019 · Colombia.</span>
            </p>
        </div>
    </div>
</aside>
