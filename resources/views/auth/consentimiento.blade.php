<x-layouts.publico titulo="Consentimiento informado">

    <h1 class="font-display text-xl font-medium text-gg-tinta mb-2">Consentimiento informado</h1>
    <p class="text-sm text-gg-tinta-suave mb-6">
        Antes de continuar, lee detenidamente la siguiente información.
    </p>

    <div class="space-y-4 text-sm text-gg-tinta leading-relaxed max-h-72 overflow-y-auto
                border border-gg-borde rounded-control p-4 mb-6">

        <p class="font-medium">Estudio: Hábitos alimentarios y riesgo digestivo en estudiantes de la Universidad Mariana</p>

        <p>
            Esta aplicación forma parte de un trabajo de grado interdisciplinar en el que
            se recopilan datos sobre tus hábitos alimentarios y sintomatología gastrointestinal
            con el fin de clasificar tu nivel de riesgo digestivo mediante un modelo estadístico.
        </p>

        <p class="font-medium">¿Qué implica participar?</p>
        <ul class="list-disc list-inside space-y-1 text-gg-tinta-suave">
            <li>Responder una encuesta de 20 preguntas sobre alimentación y salud digestiva.</li>
            <li>Tus respuestas serán almacenadas de forma segura y tratadas de manera confidencial.</li>
            <li>Puedes retirarte del estudio en cualquier momento sin consecuencias.</li>
        </ul>

        <p class="font-medium">Uso de tus datos (Ley 1581 de 2012)</p>
        <p class="text-gg-tinta-suave">
            Tus datos serán utilizados exclusivamente con fines académicos e investigativos.
            No serán compartidos con terceros ni usados con fines comerciales. Tienes derecho
            a acceder, actualizar y rectificar tu información en cualquier momento.
            Los datos se conservarán durante el tiempo establecido en la Resolución 1995 de 1999
            (mínimo 5 años).
        </p>

        <p class="font-medium">Limitación importante</p>
        <div class="gg-aviso-nodiag rounded-control p-3">
            <p>
                Esta herramienta <strong>no realiza diagnóstico clínico</strong> y no sustituye
                la consulta con un profesional de salud. El resultado es una orientación de
                autocuidado, no un diagnóstico médico. (Resolución 3100 de 2019 — Software
                como Dispositivo Médico.)
            </p>
        </div>

        <p class="font-medium">Responsable</p>
        <p class="text-gg-tinta-suave">
            Programa de Ingeniería de Sistemas, Enfermería y Nutrición y Dietética —
            Universidad Mariana, Pasto, Colombia.
        </p>

        <p class="text-2xs text-gg-tinta-suave">Versión 1.0 · Vigente desde agosto 2026</p>
    </div>

    @if($errors->has('acepto'))
        <x-alerta tipo="error" class="mb-4">{{ $errors->first('acepto') }}</x-alerta>
    @endif

    <form method="POST" action="{{ route('consentimiento.store') }}">
        @csrf

        <label class="flex items-start gap-3 cursor-pointer mb-6 group">
            <input
                type="checkbox"
                name="acepto"
                value="1"
                class="mt-0.5 rounded border-gg-borde text-gg-primario focus:ring-gg-primario shrink-0"
            >
            <span class="text-sm text-gg-tinta">
                He leído y entendido la información anterior. Acepto participar voluntariamente
                en el estudio y autorizo el tratamiento de mis datos según lo descrito.
            </span>
        </label>

        <x-boton variante="primario" type="submit" class="w-full justify-center">
            Acepto y continuar
        </x-boton>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <x-boton variante="fantasma" type="submit" class="w-full justify-center text-gg-tinta-suave">
            No acepto — cerrar sesión
        </x-boton>
    </form>

</x-layouts.publico>
